<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

class EncryptorTest extends TestCase
{
    private Encryptor $encryptor;

    protected function setUp(): void
    {
        $this->encryptor = new Encryptor('test-secret-key-for-unit-tests');
    }

    #[Test]
    public function encrypt_and_decrypt_roundtrip(): void
    {
        $plaintext = 'pablo@example.com';
        $encrypted = $this->encryptor->encrypt($plaintext);
        $decrypted = $this->encryptor->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

    #[Test]
    public function encrypted_value_differs_from_plaintext(): void
    {
        $plaintext = 'secret@email.com';
        $encrypted = $this->encryptor->encrypt($plaintext);

        $this->assertNotEquals($plaintext, $encrypted);
    }

    #[Test]
    public function each_encryption_produces_different_ciphertext(): void
    {
        $plaintext = 'same-value';
        $encrypted1 = $this->encryptor->encrypt($plaintext);
        $encrypted2 = $this->encryptor->encrypt($plaintext);

        $this->assertNotEquals($encrypted1, $encrypted2);
    }

    #[Test]
    public function encrypted_output_is_valid_base64(): void
    {
        $encrypted = $this->encryptor->encrypt('test');
        $decoded = base64_decode($encrypted, true);

        $this->assertNotFalse($decoded);
    }

    #[Test]
    public function empty_string_returns_empty_string(): void
    {
        $this->assertEquals('', $this->encryptor->encrypt(''));
        $this->assertEquals('', $this->encryptor->decrypt(''));
    }

    #[Test]
    public function decrypt_with_wrong_key_returns_empty(): void
    {
        $encrypted = $this->encryptor->encrypt('secret data');
        $wrongEncryptor = new Encryptor('wrong-key-entirely-different');
        $result = $wrongEncryptor->decrypt($encrypted);

        $this->assertNotEquals('secret data', $result);
    }

    #[Test]
    public function decrypt_invalid_base64_returns_empty(): void
    {
        $result = $this->encryptor->decrypt('!!!invalid!!!');
        $this->assertEquals('', $result);
    }

    #[Test]
    public function decrypt_too_short_data_returns_empty(): void
    {
        $result = $this->encryptor->decrypt(base64_encode('short'));
        $this->assertEquals('', $result);
    }

    #[Test]
    public function handles_unicode_characters(): void
    {
        $plaintext = 'Ñoño García 你好世界';
        $encrypted = $this->encryptor->encrypt($plaintext);
        $decrypted = $this->encryptor->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

    #[Test]
    public function handles_long_strings(): void
    {
        $plaintext = str_repeat('a', 10000);
        $encrypted = $this->encryptor->encrypt($plaintext);
        $decrypted = $this->encryptor->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

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
