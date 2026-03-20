<?php

/**
 * AES-256-CBC encryption service for protecting PII at rest.
 *
 * Used to encrypt sensitive fields (email, phone) before storing
 * them in the database. Each encryption uses a random IV, so the
 * same plaintext produces different ciphertexts every time.
 */
class Encryptor
{
    /** @var string Binary SHA-256 hash of the user-provided key */
    private string $key;

    private const CIPHER = 'aes-256-cbc';

    /**
     * @param string $key  Encryption key from environment (hashed to 32 bytes)
     */
    public function __construct(string $key)
    {
        $this->key = hash('sha256', $key, true);
    }

    /**
     * Encrypt a plaintext string.
     *
     * Output format: base64( IV [16 bytes] + ciphertext )
     *
     * @param  string $plaintext  The value to encrypt
     * @return string             Base64-encoded IV+ciphertext, or empty if input is empty
     */
    public function encrypt(string $plaintext): string
    {
        if ($plaintext === '') return '';

        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($plaintext, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt a ciphertext string back to plaintext.
     *
     * Expects the format produced by encrypt(): base64( IV + ciphertext ).
     * Returns empty string on invalid input or decryption failure.
     *
     * @param  string $ciphertext  Base64-encoded value from the database
     * @return string              Original plaintext, or empty on failure
     */
    public function decrypt(string $ciphertext): string
    {
        if ($ciphertext === '') return '';

        $data = base64_decode($ciphertext, true);
        // Minimum length: 16-byte IV + at least 1 byte of ciphertext
        if ($data === false || strlen($data) < 17) return '';

        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        $result = openssl_decrypt($encrypted, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv);

        return $result !== false ? $result : '';
    }
}
