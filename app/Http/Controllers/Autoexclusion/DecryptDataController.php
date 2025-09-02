<?php

namespace App\Http\Controllers\Autoexclusion;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Se encarga de encriptar para poder enviarlo al sistema SEVA, que teoricamente 
 * deberia estar hosteado fuera de la intranet
 */
class DecryptDataController
{
    private static function init()
    {
        $chiper = env('APP_CIPHER', 'AES-256-CBC');
        $keyTest = env('APP_CRYPT');
        
        $key = "";
        if (substr($keyTest, 0, 7) === 'base64:') {
            $keyTest = substr($keyTest, 7);
            $key = base64_decode($keyTest);
        } else {
            $key = hex2bin($keyTest);
        }

        if ($key === false) {
            throw new \Exception('Invalid encryption key');
        }
        return ["chiper" => $chiper , "key" => $key];
    }

    public static function decrypt($encryptedData)
    {
        $enc = self::init();
        $data = base64_decode($encryptedData);
        $iv = substr($data,0,16);
        $hash = substr($data, -32);
        $encryptedDataString = substr($data,16, strlen($data)- 32 - 16); // no es start y end sino start, length
        $decryptedData = openssl_decrypt($encryptedDataString,  $enc["chiper"],  $enc["key"], OPENSSL_RAW_DATA, $iv);
        if($decryptedData === false){
            throw new \Exception('Decryption failed');
        }
        $recalculatedHash = hash('sha256', $decryptedData, true);
        if (!hash_equals($hash, $recalculatedHash)) {
            throw new Exception('Hash verification failed');
        }
        return json_decode($decryptedData, true);
    }

    public static function encrypt($data)
    {
        $enc = self::init();
        $iv = random_bytes(16);
        $encryptedData = openssl_encrypt($data, $enc["chiper"], $enc["key"], OPENSSL_RAW_DATA, $iv);
        $hash = hash('sha256', $data, true);
        if ($encryptedData === false){
            throw new \Exception('Encryption failed');
        }
        return base64_encode($iv . $encryptedData. $hash);
    }

}