<?php

class Encryption
{
    private static $secretKey = 'my-secret-key';
    private static $secretIv = 'my-secret-iv';

    public static function encrypt($value)
    {
        $key = hash('sha256', self::$secretKey);
        $iv  = substr(hash('sha256', self::$secretIv), 0, 16);
        return base64_encode(openssl_encrypt($value, "AES-256-CBC", $key, 0, $iv));
    }

    public static function decrypt($encryptedValue)
    {
        $key = hash('sha256', self::$secretKey);
        $iv  = substr(hash('sha256', self::$secretIv), 0, 16);
        return openssl_decrypt(base64_decode($encryptedValue), "AES-256-CBC", $key, 0, $iv);
    }
}
