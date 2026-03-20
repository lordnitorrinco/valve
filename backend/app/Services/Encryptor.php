<?php

class Encryptor
{
    private string $key;
    private const CIPHER = 'aes-256-cbc';

    public function __construct(string $key)
    {
        $this->key = hash('sha256', $key, true);
    }

    public function encrypt(string $plaintext): string
    {
        if ($plaintext === '') return '';

        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($plaintext, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    public function decrypt(string $ciphertext): string
    {
        if ($ciphertext === '') return '';

        $data = base64_decode($ciphertext, true);
        if ($data === false || strlen($data) < 17) return '';

        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        $result = openssl_decrypt($encrypted, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv);

        return $result !== false ? $result : '';
    }
}
