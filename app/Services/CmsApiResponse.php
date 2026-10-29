<?php

namespace App\Services;

use RuntimeException;

class CmsApiResponse
{
    /** @var int */
    public $status;

    /**
     * Signed entity headers, lowercase name => list of values.
     * When !$verified these are the transport headers.
     *
     * @var array
     */
    public $headers;

    /** @var string */
    public $body;

    /**
     * true: decrypted, signed by the API, and bound to this very request.
     * false: came in clear (204, or an error from before the API identified us).
     *
     * @var bool
     */
    public $verified;

    /** @var array HTTP headers of the envelope, lowercase name => list of values */
    public $transportHeaders;

    public function __construct($status, array $headers, $body, $verified, array $transportHeaders)
    {
        $this->status           = (int) $status;
        $this->headers          = $headers;
        $this->body             = (string) $body;
        $this->verified         = (bool) $verified;
        $this->transportHeaders = $transportHeaders;
    }

    public function successful()
    {
        return $this->status >= 200 && $this->status < 300
            && ($this->verified || $this->status === 204);
    }

    public function header($name)
    {
        $name = strtolower($name);

        return isset($this->headers[$name][0]) ? $this->headers[$name][0] : null;
    }

    /** @return mixed decoded JSON as arrays, null for an empty body */
    public function json()
    {
        if ($this->body === '') {
            return null;
        }

        $data = json_decode($this->body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return $data;
    }

    /** File name from Content-Disposition, if any */
    public function filename()
    {
        $disposition = (string) $this->header('Content-Disposition');

        if (preg_match("/filename\\*=UTF-8''([^;]+)/i", $disposition, $m)) {
            return basename(rawurldecode(trim($m[1])));
        }
        if (preg_match('/filename="?([^";]+)"?/i', $disposition, $m)) {
            return basename(trim($m[1]));
        }

        return null;
    }

    /** MIME from Content-Type, if any */
    public function bodyType()
    {
        $type = $this->header('Content-Type');
        return $type !== null? trim((string) $type) : null;
    }
}