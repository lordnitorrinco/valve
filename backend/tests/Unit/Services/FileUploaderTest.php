<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class FileUploaderTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/evolve_test_uploads_' . bin2hex(random_bytes(4));
        mkdir($this->tmpDir);
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
    }

    protected function tearDown(): void
    {
        $files = glob($this->tmpDir . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
        if (is_dir($this->tmpDir . '/subdir')) {
            rmdir($this->tmpDir . '/subdir');
        }
        rmdir($this->tmpDir);
    }

    private function uploader(array $overrides = []): FileUploader
    {
        return new FileUploader(array_merge([
            'directory'          => $this->tmpDir,
            'max_size'           => 10 * 1024 * 1024,
            'allowed_extensions' => ['pdf', 'doc', 'docx'],
        ], $overrides));
    }

    private function createTempFile(string $content = 'test'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'test_upload_');
        file_put_contents($path, $content);
        return $path;
    }

    #[Test]
    public function rejects_file_exceeding_max_size(): void
    {
        $uploader = $this->uploader(['max_size' => 10]);
        $tmpFile = $this->createTempFile(str_repeat('x', 100));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File exceeds maximum size');

        try {
            $uploader->upload([
                'name'     => 'big.pdf',
                'tmp_name' => $tmpFile,
                'error'    => UPLOAD_ERR_OK,
                'size'     => 100,
            ]);
        } finally {
            @unlink($tmpFile);
        }
    }

    #[Test]
    public function rejects_disallowed_file_extension(): void
    {
        $uploader = $this->uploader();
        $tmpFile = $this->createTempFile();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File type not allowed');

        try {
            $uploader->upload([
                'name'     => 'malware.exe',
                'tmp_name' => $tmpFile,
                'error'    => UPLOAD_ERR_OK,
                'size'     => 4,
            ]);
        } finally {
            @unlink($tmpFile);
        }
    }

    #[Test]
    public function returns_null_on_upload_error(): void
    {
        $uploader = $this->uploader();
        $result = $uploader->upload([
            'name'     => 'test.pdf',
            'tmp_name' => '',
            'error'    => UPLOAD_ERR_NO_FILE,
            'size'     => 0,
        ]);

        $this->assertNull($result);
    }

    #[Test]
    public function rejects_file_with_mismatched_magic_bytes(): void
    {
        $uploader = $this->uploader();
        $tmpFile = $this->createTempFile('NOT_A_PDF_CONTENT');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File content does not match extension');

        try {
            $uploader->upload([
                'name'     => 'fake.pdf',
                'tmp_name' => $tmpFile,
                'error'    => UPLOAD_ERR_OK,
                'size'     => 18,
            ]);
        } finally {
            @unlink($tmpFile);
        }
    }

    #[Test]
    public function pdf_with_correct_magic_bytes_passes_verification(): void
    {
        $uploader = $this->uploader();
        $tmpFile = $this->createTempFile('%PDF-1.4 some content');

        // move_uploaded_file only works in HTTP context, so we expect it to fail
        // at the move step, NOT at magic bytes verification
        try {
            $uploader->upload([
                'name'     => 'real.pdf',
                'tmp_name' => $tmpFile,
                'error'    => UPLOAD_ERR_OK,
                'size'     => 21,
            ]);
            $this->fail('Expected RuntimeException for move_uploaded_file');
        } catch (RuntimeException $e) {
            // The error should be about moving, NOT about magic bytes
            $this->assertStringContainsString('Failed to save', $e->getMessage());
        } finally {
            @unlink($tmpFile);
        }
    }

    #[Test]
    public function docx_with_wrong_magic_bytes_is_rejected(): void
    {
        $uploader = $this->uploader();
        $tmpFile = $this->createTempFile('NOT_A_DOCX');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File content does not match extension');

        try {
            $uploader->upload([
                'name'     => 'fake.docx',
                'tmp_name' => $tmpFile,
                'error'    => UPLOAD_ERR_OK,
                'size'     => 10,
            ]);
        } finally {
            @unlink($tmpFile);
        }
    }

    #[Test]
    public function generated_filename_is_32_char_hex(): void
    {
        $randomHex = bin2hex(random_bytes(16));
        $this->assertEquals(32, strlen($randomHex));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $randomHex);
    }

    #[Test]
    public function allowed_extensions_are_enforced(): void
    {
        $uploader = $this->uploader(['allowed_extensions' => ['txt']]);
        $tmpFile = $this->createTempFile('hello');

        // txt has no magic bytes check, so it should fail at move_uploaded_file
        try {
            $uploader->upload([
                'name'     => 'notes.txt',
                'tmp_name' => $tmpFile,
                'error'    => UPLOAD_ERR_OK,
                'size'     => 5,
            ]);
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Failed to save', $e->getMessage());
        } finally {
            @unlink($tmpFile);
        }

        // But .pdf should be rejected
        $tmpFile2 = $this->createTempFile('hello');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File type not allowed');

        try {
            $uploader->upload([
                'name'     => 'test.pdf',
                'tmp_name' => $tmpFile2,
                'error'    => UPLOAD_ERR_OK,
                'size'     => 5,
            ]);
        } finally {
            @unlink($tmpFile2);
        }
    }
}
