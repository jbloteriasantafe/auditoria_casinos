<?php

namespace App\Services;

use RuntimeException;

/**
 * Client for the CMS-protected API. PHP 7.0+, ext-openssl, ext-curl.
 * No framework dependencies (the config file is Laravel's).
 *
 * Request (sign, then encrypt):
 *   entity  = X-API-Origin / Destination / Iat / Jti / Content-Type ..., blank line, body
 *   signed  = CMS SignedData (DER) over the entity, with the content and our certificate inside
 *   HTTP    = CMS EnvelopedData (PEM) of `signed`, for the API's certificate
 *
 * Response: the same with roles swapped. It is accepted only if the signer's
 * certificate is the API's (pinned by fingerprint), Origin / Destination match,
 * Iat is fresh, and X-API-Request-Iat / X-API-Request-Jti echo this request.
 *
 * PHP 7 has no openssl_cms_* (they arrived in PHP 8.0). openssl_pkcs7_* build
 * the same ASN.1 structures, but read and write S/MIME (MIME headers + base64)
 * instead of raw DER / PEM, so this class converts between the two.
 */
class CmsApiClient
{
    const FRESHNESS_WINDOW = 300;
    const MAX_HEADER_BYTES = 16384;
    const HEADER_NAME      = '/^[A-Za-z0-9!#$%&\'*+.^_`|~-]+$/';
    const ENVELOPE_TYPE    = 'application/pkcs7-mime; smime-type=enveloped-data';

    private $baseUri;
    private $timeout;
    private $connectTimeout;
    private $caBundle;

    private $cert;
    private $key;
    private $apiCert;

    private $certPath;
    private $keyPath;
    private $apiCertPath;
    private $passphrase;
    private $opensslBin;

    private $identity;
    private $apiIdentity;
    private $apiFingerprint;

    /**
     * @param array $config base_uri, cert, key, passphrase, api_cert,
     *                      timeout, connect_timeout, ca_bundle, openssl_bin
     */
    public function __construct(array $config)
    {
        if (! extension_loaded('curl')) {
            throw new RuntimeException('ext-curl is required.');
        }

        foreach (['base_uri', 'cert', 'key', 'api_cert'] as $required) {
            if (empty($config[$required])) {
                throw new RuntimeException("cms_api.$required is not configured.");
            }
        }

        $this->baseUri        = rtrim($config['base_uri'], '/');
        $this->timeout        = isset($config['timeout']) ? (int) $config['timeout'] : 60;
        $this->connectTimeout = isset($config['connect_timeout']) ? (int) $config['connect_timeout'] : 10;
        $this->caBundle       = empty($config['ca_bundle']) ? null : $config['ca_bundle'];

        $this->certPath    = $config['cert'];
        $this->keyPath     = $config['key'];
        $this->apiCertPath = $config['api_cert'];
        $this->passphrase  = isset($config['passphrase']) ? (string) $config['passphrase'] : '';
        $this->opensslBin  = empty($config['openssl_bin']) ? null : $config['openssl_bin'];

        $this->cert    = $this->readCertificate($this->certPath);
        $this->apiCert = $this->readCertificate($this->apiCertPath);

        $this->key = openssl_pkey_get_private('file://'.$this->keyPath, $this->passphrase);
        if ($this->key === false) {
            throw new RuntimeException('Cannot read the client private key: '.$this->opensslErrors());
        }
        if (! openssl_x509_check_private_key($this->cert, $this->key)) {
            throw new RuntimeException('The client private key does not match the client certificate.');
        }

        $this->identity       = $this->commonName($this->cert);
        $this->apiIdentity    = $this->commonName($this->apiCert);
        $this->apiFingerprint = $this->fingerprint($this->apiCert);
    }

    /** Our CN: what goes in X-API-Origin */
    public function identity()
    {
        return $this->identity;
    }

    public function get($uri, array $options = [])
    {
        return $this->request('GET', $uri, $options);
    }

    public function post($uri, array $options = [])
    {
        return $this->request('POST', $uri, $options);
    }

    public function put($uri, array $options = [])
    {
        return $this->request('PUT', $uri, $options);
    }

    public function patch($uri, array $options = [])
    {
        return $this->request('PATCH', $uri, $options);
    }

    public function delete($uri, array $options = [])
    {
        return $this->request('DELETE', $uri, $options);
    }

    /**
     * Signs, encrypts and sends a request; opens and authenticates the response.
     *
     * $uri is appended to base_uri. The API ignores the query string: GET
     * parameters go in 'json' (or 'form_params') like any other.
     *
     * $options, the body (at most one):
     *   'json'        => array                         application/json
     *   'form_params' => array                         application/x-www-form-urlencoded
     *   'multipart'   => [['name' => 'x', 'contents' => 'string or stream'],
     *                     ['name' => 'f', 'contents' => fopen(...), 'filename' => 'a.pdf',
     *                      'content_type' => 'application/pdf'], ...]
     *                    multipart/form-data INSIDE the envelope (one signature covers
     *                    all); works with any method
     *   'body'        => string, 'content_type' => string     anything else, raw
     * combinable with any of them:
     *   'attachments' => ['field' => '/path/file.pdf', 'other' => ['path' => ..., 'filename' => ...]]
     *                    POST only: each file signed and encrypted on its own and sent
     *                    in the transport multipart; field names must not repeat body keys
     *   'headers'     => ['Content-Language' => 'es']    extra signed entity headers
     *
     * @return CmsApiResponse
     */
    public function request($method, $uri, array $options = [])
    {
        $prepared = $this->prepare($method, $options);

        list($status, $headers, $raw) = $this->send(
            $prepared['method'],
            $uri,
            $prepared['headers'],
            $prepared['body']
        );

        return $this->open($status, $headers, $raw, $prepared['iat'], $prepared['jti']);
    }

    /**
     * Builds the signed and encrypted request without sending it.
     *
     * @return array ['method' => string, 'iat' => string, 'jti' => string,
     *                'headers' => HTTP headers (name => value), 'body' => HTTP body]
     */
    public function prepare($method, array $options = [])
    {
        $method = strtoupper($method);
        if (! in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            throw new RuntimeException("Method not supported by the API: $method");
        }

        $files = isset($options['attachments']) ? $options['attachments'] : [];

        // PHP fills $_FILES / $_POST from a multipart body only on POST
        if ($files && $method !== 'POST') {
            throw new RuntimeException(
                "Attachments need POST (PHP parses multipart uploads only on POST). For $method use 'multipart'."
            );
        }

        list($contentType, $body) = $this->buildBody($options);

        $iat = (string) time();
        $jti = bin2hex(random_bytes(16));

        $headers = [
            'X-API-Origin'      => $this->identity,
            'X-API-Destination' => $this->apiIdentity,
            'X-API-Iat'         => $iat,
            'X-API-Jti'         => $jti,
            'Content-Type'      => $contentType,
        ];

        $extra = isset($options['headers']) ? $options['headers'] : [];
        foreach ($extra as $name => $value) {
            $lower = strtolower($name);
            if (strpos($lower, 'x-api-') === 0 || $lower === 'content-type' || $lower === 'attachment') {
                throw new RuntimeException("Header [$name] is set by the client itself.");
            }
            $headers[$name] = $value;
        }

        // Attachments travel outside the envelope, each one signed and encrypted
        // on its own; the signed entity binds them by the digest of their ciphertext.
        $parts    = [];
        $declared = [];
        foreach ($files as $field => $file) {
            $field = (string) $field;

            // PHP turns "." and " " into "_" in upload field names, and "a[]" into an
            // array: the API only accepts plain names.
            if (! preg_match('/^[A-Za-z0-9_-]+$/', $field) || $field === 'payload') {
                throw new RuntimeException("Invalid attachment field name [$field].");
            }

            $path     = is_array($file) ? $file['path'] : $file;
            $filename = is_array($file) && isset($file['filename']) ? $file['filename'] : basename($path);

            if (! is_file($path) || ! is_readable($path)) {
                throw new RuntimeException("Cannot read attachment [$field]: $path");
            }

            $sealed = $this->encrypt($this->signPath($path));

            $declared[] = 'field="'.$field.'"; sha256="'.hash('sha256', $sealed).'"';
            $parts[]    = [
                'name'         => $field,
                'contents'     => $sealed,
                'filename'     => $this->safeFilename($filename).'.p7m',
                'content_type' => self::ENVELOPE_TYPE,
            ];
        }
        if ($declared) {
            $headers['Attachment'] = $declared;
        }

        $envelope = $this->derToPem($this->encrypt($this->sign($this->entity($headers, $body))));

        // Content-* are replaced by the API with the signed ones, Accept is not:
        // it makes Laravel render validation errors and exceptions as JSON
        $http = ['Accept' => 'application/json'];

        if ($parts) {
            array_unshift($parts, ['name' => 'payload', 'contents' => $envelope]);
            list($http['Content-Type'], $httpBody) = $this->buildMultipart($parts);
        }
        else {
            $http['Content-Type'] = self::ENVELOPE_TYPE;
            $httpBody = $envelope;
        }

        return ['method' => $method, 'iat' => $iat, 'jti' => $jti, 'headers' => $http, 'body' => $httpBody];
    }

    /**
     * Opens and authenticates a response of the API.
     *
     * @param int    $status
     * @param array  $transportHeaders name => value or list of values
     * @param string $raw              HTTP body
     * @param string $iat              X-API-Iat of the request
     * @param string $jti              X-API-Jti of the request
     * @return CmsApiResponse
     */
    public function open($status, array $transportHeaders, $raw, $iat, $jti)
    {
        $status    = (int) $status;
        $transport = [];
        foreach ($transportHeaders as $name => $values) {
            $transport[strtolower($name)] = array_values((array) $values);
        }

        $contentType = isset($transport['content-type'][0]) ? $transport['content-type'][0] : '';

        if (! $this->isEnvelopeType($contentType)) {
            // In clear only come 204 / 304 (no body) and the errors from before the
            // API identified the signer. Nothing in them is authenticated, so any
            // other response without an envelope is refused.
            if ($status === 204 || $status === 304 || $status >= 400) {
                return new CmsApiResponse($status, $transport, (string) $raw, false, $transport);
            }
            throw new RuntimeException("The API answered $status without an envelope.");
        }

        // 1. Decrypt with our key, 2. the signature must be the API's,
        // 3. only then trust the signed headers
        $entity = $this->verifyFromApi($this->decrypt($this->pemToDer($raw)));

        list($headers, $body) = $this->parseEntity($entity);
        $this->checkResponseHeaders($headers, $iat, $jti);

        return new CmsApiResponse($status, $headers, $body, true, $transport);
    }

    // ------------------------------------------------------------------------
    // HTTP
    // ------------------------------------------------------------------------

    /**
     * @param array $headers name => value
     * @return array [status, headers (lowercase name => list of values), body]
     */
    private function send($method, $uri, array $headers, $body)
    {
        $url = $this->baseUri.'/'.ltrim($uri, '/');

        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = $name.': '.$value;
        }
        // Without this, curl waits for "100 Continue" before sending bodies over 1 kB
        $lines[] = 'Expect:';
        $lines[] = 'API-Key: '.config('cms_api.api_gateway_key','');

        $responseHeaders = [];

        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('curl_init failed.');
        }
        
        $options = [
            // CUSTOMREQUEST + POSTFIELDS: the body also goes with GET and DELETE,
            // which this API needs (everything travels signed in the body)
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $lines,
            CURLOPT_RETURNTRANSFER => true,
            // Following a redirect would resend the envelope (or drop it) somewhere else
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADERFUNCTION => function ($ch, $line) use (&$responseHeaders) {
                $trimmed = trim($line);

                if (stripos($trimmed, 'HTTP/') === 0) {
                    // A new status line (after a 1xx): drop the previous headers
                    $responseHeaders = [];
                }
                elseif (($colon = strpos($trimmed, ':')) !== false) {
                    $name = strtolower(trim(substr($trimmed, 0, $colon)));
                    $responseHeaders[$name][] = trim(substr($trimmed, $colon + 1));
                }

                return strlen($line);
            },
        ];

        $proxy_url  = config('cms_api.https_proxy',null);
        $proxy_port = config('cms_api.https_proxy_port',null);
        if($proxy_url !== null && $proxy_port !== null){
            $options[CURLOPT_PROXY] = $proxy_url;
            $options[CURLOPT_PROXYPORT] = $proxy_port;
        }
        else{
            $options[CURLOPT_PROXY] = ''; 
            $options[CURLOPT_PROXYPORT] = ''; 
        }

        if ($this->caBundle !== null) {
            $options[CURLOPT_CAINFO] = $this->caBundle;
        }

        curl_setopt_array($ch, $options);

        $raw = curl_exec($ch);

        if ($raw === false) {
            $error = curl_errno($ch).' '.curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("$method $url failed: cURL error $error");
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, $responseHeaders, (string) $raw];
    }

    // ------------------------------------------------------------------------
    // Entities
    // ------------------------------------------------------------------------

    /**
     * @return array [content type, body]
     */
    private function buildBody(array $options)
    {
        $given = array_intersect(['json', 'form_params', 'multipart', 'body'], array_keys($options));
        if (count($given) > 1) {
            throw new RuntimeException('Only one of json, form_params, multipart or body.');
        }

        if (array_key_exists('json', $options)) {
            $json = json_encode($options['json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                throw new RuntimeException('json_encode failed: '.json_last_error_msg());
            }
            return ['application/json', $json];
        }

        if (array_key_exists('form_params', $options)) {
            return ['application/x-www-form-urlencoded', http_build_query($options['form_params'], '', '&')];
        }

        if (array_key_exists('multipart', $options)) {
            return $this->buildMultipart($options['multipart']);
        }

        if (array_key_exists('body', $options)) {
            $type = isset($options['content_type']) ? $options['content_type'] : 'application/octet-stream';
            return [$type, (string) $options['body']];
        }

        // No body (GET / DELETE without parameters): still a signed entity
        return ['application/json', ''];
    }

    /**
     * multipart/form-data (RFC 7578), used both inside the envelope and for the
     * transport (payload + attachments).
     *
     * @return array [content type, body]
     */
    private function buildMultipart(array $parts)
    {
        $boundary = '----cms'.bin2hex(random_bytes(16));
        $body     = '';

        foreach ($parts as $part) {
            if (! isset($part['name']) || ! array_key_exists('contents', $part)) {
                throw new RuntimeException('Each multipart part needs name and contents.');
            }

            $contents = is_resource($part['contents'])
                ? stream_get_contents($part['contents'])
                : (string) $part['contents'];

            $headers = 'Content-Disposition: form-data; name="'.$this->quotable($part['name']).'"';

            if (isset($part['filename'])) {
                $headers .= '; filename="'.$this->safeFilename($part['filename']).'"';
                $type     = isset($part['content_type']) ? $part['content_type'] : 'application/octet-stream';
                $headers .= "\r\nContent-Type: ".str_replace(["\r", "\n"], '', $type);
            }

            $body .= '--'.$boundary."\r\n".$headers."\r\n\r\n".$contents."\r\n";
        }

        $body .= '--'.$boundary."--\r\n";

        return ['multipart/form-data; boundary='.$boundary, $body];
    }

    /**
     * MIME entity: headers (validated names, no CR/LF in values), blank line, body.
     *
     * @param array $headers name => string | list of strings
     */
    public function entity(array $headers, $body)
    {
        $block = '';
        foreach ($headers as $name => $values) {
            if (! preg_match(self::HEADER_NAME, (string) $name)) {
                throw new RuntimeException("Invalid header name [$name].");
            }
            foreach ((array) $values as $value) {
                $block .= $name.': '.str_replace(["\r", "\n"], '', (string) $value)."\r\n";
            }
        }

        return $block."\r\n".$body;
    }

    /**
     * Splits headers and body at the first blank line, like the API does.
     *
     * @return array [headers (lowercase name => list of values), body]
     */
    public function parseEntity($block)
    {
        if (! preg_match('/\r?\n\r?\n/', $block, $m, PREG_OFFSET_CAPTURE)) {
            throw new RuntimeException('Entity without a blank line between headers and body.');
        }

        $separator = $m[0][0];
        $offset    = $m[0][1];

        if ($offset > self::MAX_HEADER_BYTES) {
            throw new RuntimeException('Header block too large.');
        }

        $headers = [];
        foreach (preg_split('/\r?\n/', substr($block, 0, $offset)) as $line) {
            $colon = strpos($line, ':');
            $name  = $colon === false ? '' : substr($line, 0, $colon);

            if (! preg_match(self::HEADER_NAME, $name)) {
                throw new RuntimeException('Malformed header line.');
            }

            $headers[strtolower($name)][] = trim(substr($line, $colon + 1), " \t");
        }

        return [$headers, (string) substr($block, $offset + strlen($separator))];
    }

    /** Origin = API, Destination = us, fresh Iat, and bound to this request */
    private function checkResponseHeaders(array $headers, $iat, $jti)
    {
        $expected = [
            'x-api-origin'      => $this->apiIdentity,
            'x-api-destination' => $this->identity,
            'x-api-request-iat' => (string) $iat,
            'x-api-request-jti' => (string) $jti,
        ];

        foreach ($expected as $name => $value) {
            $got = isset($headers[$name][0]) ? $headers[$name][0] : null;
            if ($got === null || ! hash_equals($value, $got)) {
                throw new RuntimeException("Response header $name does not match.");
            }
        }

        $responseIat = isset($headers['x-api-iat'][0]) ? $headers['x-api-iat'][0] : '';
        if (! ctype_digit($responseIat) || abs((int) $responseIat - time()) > self::FRESHNESS_WINDOW) {
            throw new RuntimeException('Response X-API-Iat missing or out of range.');
        }
    }

    // ------------------------------------------------------------------------
    // CMS through openssl_pkcs7_*
    // ------------------------------------------------------------------------

    /** Signs a string; returns CMS SignedData in DER */
    private function sign($content)
    {
        return $this->withTempFiles(1, function ($in) use ($content) {
            $this->write($in, $content);

            return $this->signPath($in);
        });
    }

    /** Signs a file (content and our certificate inside); returns DER */
    private function signPath($in)
    {
        if ($this->opensslBin !== null) {
            return $this->signPathWithCli($in);
        }

        return $this->withTempFiles(1, function ($out) use ($in) {
            // PKCS7_BINARY without PKCS7_DETACHED: opaque signature, content copied
            // byte for byte, signer certificate included (the API identifies us by it)
            if (openssl_pkcs7_sign($in, $out, $this->cert, $this->key, [], PKCS7_BINARY) !== true) {
                throw new RuntimeException('Signing failed: '.$this->opensslErrors());
            }

            return $this->smimeToDer($this->read($out));
        });
    }

    /**
     * Same as signPath() with the openssl binary, to force SHA-256: PHP 7 can't
     * choose the digest, and with OpenSSL 1.0.x openssl_pkcs7_sign() uses SHA-1.
     */
    private function signPathWithCli($in)
    {
        return $this->withTempFiles(2, function ($out, $passFile) use ($in) {
            $command = escapeshellarg($this->opensslBin)
                .' cms -sign -binary -nodetach -md sha256 -outform DER'
                .' -signer '.escapeshellarg($this->certPath)
                .' -inkey '.escapeshellarg($this->keyPath)
                .' -in '.escapeshellarg($in)
                .' -out '.escapeshellarg($out);

            // In a file (0600) and not on the command line, where `ps` would show it
            if ($this->passphrase !== '') {
                $this->write($passFile, $this->passphrase);
                $command .= ' -passin '.escapeshellarg('file:'.$passFile);
            }

            exec($command.' 2>&1', $output, $code);
            if ($code !== 0) {
                throw new RuntimeException('openssl cms -sign failed: '.implode(' ', $output));
            }

            return $this->read($out);
        });
    }

    /** Encrypts for the API (AES-256-CBC); returns CMS EnvelopedData in DER */
    private function encrypt($content)
    {
        return $this->withTempFiles(2, function ($in, $out) use ($content) {
            $this->write($in, $content);

            // Without the cipher PHP 7 defaults to RC2-40
            $ok = openssl_pkcs7_encrypt($in, $out, $this->apiCert, [], PKCS7_BINARY, OPENSSL_CIPHER_AES_256_CBC);
            if ($ok !== true) {
                throw new RuntimeException('Encryption failed: '.$this->opensslErrors());
            }

            return $this->smimeToDer($this->read($out));
        });
    }

    /** Decrypts an envelope (DER) addressed to us; returns its content */
    private function decrypt($envelope)
    {
        return $this->withTempFiles(2, function ($in, $out) use ($envelope) {
            $this->write($in, $this->derToSmime($envelope, 'enveloped-data'));

            if (openssl_pkcs7_decrypt($in, $out, $this->cert, $this->key) !== true) {
                throw new RuntimeException('Decryption failed: '.$this->opensslErrors());
            }

            return $this->read($out);
        });
    }

    /**
     * Verifies a SignedData (DER) and that the API signed it; returns the content.
     *
     * PKCS7_NOVERIFY skips only the chain (self-signed certificates); the
     * signature itself is checked, and who signed is decided by fingerprint.
     */
    private function verifyFromApi($signed)
    {
        return $this->withTempFiles(3, function ($in, $signers, $out) use ($signed) {
            $this->write($in, $this->derToSmime($signed, 'signed-data'));

            // extracerts = the API certificate: PHP 7 doesn't accept null there
            // and still write the content
            $ok = openssl_pkcs7_verify(
                $in,
                PKCS7_NOVERIFY | PKCS7_BINARY,
                $signers,
                [],
                $this->apiCertPath,
                $out
            );
            if ($ok !== true) {
                throw new RuntimeException('Invalid response signature: '.$this->opensslErrors());
            }

            $pem = $this->read($signers);
            if (substr_count($pem, '-----BEGIN CERTIFICATE-----') !== 1) {
                throw new RuntimeException('Expected exactly one signer.');
            }

            $signer = openssl_x509_read($pem);
            if ($signer === false || ! hash_equals($this->apiFingerprint, $this->fingerprint($signer))) {
                throw new RuntimeException('The response was not signed by the API.');
            }

            return $this->read($out);
        });
    }

    // ------------------------------------------------------------------------
    // Encodings: openssl_pkcs7_* speak S/MIME, the API speaks DER / PEM
    // ------------------------------------------------------------------------

    private function smimeToDer($smime)
    {
        if (! preg_match('/\r?\n\r?\n/', $smime, $m, PREG_OFFSET_CAPTURE)) {
            throw new RuntimeException('Unexpected S/MIME output from OpenSSL.');
        }

        $base64 = substr($smime, $m[0][1] + strlen($m[0][0]));
        $der    = base64_decode(preg_replace('/\s+/', '', $base64), true);

        if ($der === false || $der === '') {
            throw new RuntimeException('Unexpected S/MIME output from OpenSSL.');
        }

        return $der;
    }

    private function derToSmime($der, $smimeType)
    {
        return "MIME-Version: 1.0\n"
            ."Content-Disposition: attachment; filename=\"smime.p7m\"\n"
            ."Content-Type: application/pkcs7-mime; smime-type=$smimeType; name=\"smime.p7m\"\n"
            ."Content-Transfer-Encoding: base64\n"
            ."\n"
            .chunk_split(base64_encode($der), 64, "\n");
    }

    private function pemToDer($pem)
    {
        if (! preg_match('/-----BEGIN (CMS|PKCS7)-----(.+?)-----END \1-----/s', $pem, $m)) {
            throw new RuntimeException('The response is not a PEM envelope.');
        }

        $der = base64_decode(preg_replace('/\s+/', '', $m[2]), true);
        if ($der === false || $der === '') {
            throw new RuntimeException('The response is not a PEM envelope.');
        }

        return $der;
    }

    private function derToPem($der)
    {
        return "-----BEGIN CMS-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END CMS-----\n";
    }

    // ------------------------------------------------------------------------
    // Utilities
    // ------------------------------------------------------------------------

    private function isEnvelopeType($contentType)
    {
        $mime = function ($type) {
            $parts = explode(';', $type, 2);

            return strtolower(trim($parts[0]));
        };

        return $mime($contentType) === $mime(self::ENVELOPE_TYPE);
    }

    private function readCertificate($path)
    {
        $pem = @file_get_contents($path);
        if ($pem === false) {
            throw new RuntimeException("Cannot read certificate $path");
        }

        $cert = openssl_x509_read($pem);
        if ($cert === false) {
            throw new RuntimeException("Malformed certificate $path: ".$this->opensslErrors());
        }

        return $cert;
    }

    private function commonName($cert)
    {
        $info = openssl_x509_parse($cert);
        $cn   = isset($info['subject']['CN']) ? $info['subject']['CN'] : null;

        // Several CNs come as an array: ambiguous, like on the API
        if (! is_string($cn) || $cn === '') {
            throw new RuntimeException('Certificate without a single CN.');
        }

        return $cn;
    }

    private function fingerprint($cert)
    {
        $fingerprint = openssl_x509_fingerprint($cert, 'sha256');
        if ($fingerprint === false) {
            throw new RuntimeException('Cannot compute fingerprint: '.$this->opensslErrors());
        }

        return strtolower($fingerprint);
    }

    private function quotable($value)
    {
        $value = (string) $value;
        if ($value === '' || preg_match('/["\\\\\r\n]/', $value)) {
            throw new RuntimeException("Invalid multipart name [$value].");
        }

        return $value;
    }

    private function safeFilename($filename)
    {
        $name = str_replace(['"', '\\', "\r", "\n"], '', basename((string) $filename));

        return $name === '' ? 'file' : $name;
    }

    /** Runs $fn with $count empty temp files (0600), deleted afterwards */
    private function withTempFiles($count, callable $fn)
    {
        $paths = [];
        try {
            for ($i = 0; $i < $count; $i++) {
                $path = tempnam(sys_get_temp_dir(), 'cms');
                if ($path === false) {
                    throw new RuntimeException('Cannot create a temp file.');
                }
                $paths[] = $path;
            }

            return call_user_func_array($fn, $paths);
        }
        finally {
            foreach ($paths as $path) {
                @unlink($path);
            }
        }
    }

    private function read($path)
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException("Cannot read temp file $path");
        }

        return $content;
    }

    private function write($path, $content)
    {
        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException("Cannot write temp file $path");
        }
    }

    private function opensslErrors()
    {
        $errors = [];
        while ($e = openssl_error_string()) {
            $errors[] = $e;
        }

        return implode('; ', $errors) ?: 'no detail';
    }
}