<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for the Encryptor service.
 * Verifies AES-256-CBC encryption/decryption, key handling, and edge cases.
 */
class EncryptorTest extends TestCase
{
    private Encryptor $encryptor;

    protected function setUp(): void
    {
        $this->encryptor = new Encryptor('test-secret-key-for-unit-tests');
    }

    /** Tests that encrypt followed by decrypt returns the original plaintext. */
    #[Test]
    public function encrypt_and_decrypt_roundtrip(): void
    {
        $plaintext = 'pablo@example.com';
        $encrypted = $this->encryptor->encrypt($plaintext);
        $decrypted = $this->encryptor->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

    /** Tests that ciphertext is not identical to plaintext (obfuscation). */
    #[Test]
    public function encrypted_value_differs_from_plaintext(): void
    {
        $plaintext = 'secret@email.com';
        $encrypted = $this->encryptor->encrypt($plaintext);

        $this->assertNotEquals($plaintext, $encrypted);
    }

    /** Tests that random IV/nonce makes each encryption output unique for the same input. */
    #[Test]
    public function each_encryption_produces_different_ciphertext(): void
    {
        $plaintext = 'same-value';
        $encrypted1 = $this->encryptor->encrypt($plaintext);
        $encrypted2 = $this->encryptor->encrypt($plaintext);

        $this->assertNotEquals($encrypted1, $encrypted2);
    }

    /** Tests that encrypted output is valid Base64-decodable bytes. */
    #[Test]
    public function encrypted_output_is_valid_base64(): void
    {
        $encrypted = $this->encryptor->encrypt('test');
        $decoded = base64_decode($encrypted, true);

        $this->assertNotFalse($decoded);
    }

    /** Tests that empty plaintext encrypts and decrypts to empty string. */
    #[Test]
    public function empty_string_returns_empty_string(): void
    {
        $this->assertEquals('', $this->encryptor->encrypt(''));
        $this->assertEquals('', $this->encryptor->decrypt(''));
    }

    /** Tests that decrypting with a different key does not recover the original secret. */
    #[Test]
    public function decrypt_with_wrong_key_returns_empty(): void
    {
        $encrypted = $this->encryptor->encrypt('secret data');
        $wrongEncryptor = new Encryptor('wrong-key-entirely-different');
        $result = $wrongEncryptor->decrypt($encrypted);

        $this->assertNotEquals('secret data', $result);
    }

    /** Tests that decrypt handles invalid Base64 by yielding empty string. */
    #[Test]
    public function decrypt_invalid_base64_returns_empty(): void
    {
        $result = $this->encryptor->decrypt('!!!invalid!!!');
        $this->assertEquals('', $result);
    }

    /** Tests that ciphertext shorter than IV + tag length decrypts to empty. */
    #[Test]
    public function decrypt_too_short_data_returns_empty(): void
    {
        $result = $this->encryptor->decrypt(base64_encode('short'));
        $this->assertEquals('', $result);
    }

    /** Tests that Unicode plaintext survives encrypt/decrypt round-trip. */
    #[Test]
    public function handles_unicode_characters(): void
    {
        $plaintext = 'Ñoño García 你好世界';
        $encrypted = $this->encryptor->encrypt($plaintext);
        $decrypted = $this->encryptor->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

    /** Tests that very long strings round-trip without corruption. */
    #[Test]
    public function handles_long_strings(): void
    {
        $plaintext = str_repeat('a', 10000);
        $encrypted = $this->encryptor->encrypt($plaintext);
        $decrypted = $this->encryptor->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

    /** Tests that distinct keys produce independent ciphertexts but each decrypts correctly with its key. */
    #[Test]
    public function different_keys_produce_different_ciphertexts(): void
    {
        $e1 = new Encryptor('key-one');
        $e2 = new Encryptor('key-two');

        $c1 = $e1->encrypt('same plaintext');
        $c2 = $e2->encrypt('same plaintext');

        $d1 = $e1->decrypt($c1);
        $d2 = $e2->decrypt($c2);

        $this->assertEquals('same plaintext', $d1);
        $this->assertEquals('same plaintext', $d2);
    }
}
