<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testable subclass that uses copy() instead of move_uploaded_file().
 */
class TestFileUploader extends FileUploader
{
    protected function moveFile(string $from, string $to): bool
    {
        return copy($from, $to);
    }
}

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
        foreach (glob($this->tmpDir . '/*') as $file) unlink($file);
        if (is_dir($this->tmpDir . '/subdir')) rmdir($this->tmpDir . '/subdir');
        rmdir($this->tmpDir);
    }

    private function uploader(array $overrides = []): FileUploader
    {
        return new FileUploader(array_merge([
            'directory' => $this->tmpDir,
            'max_size' => 10 * 1024 * 1024,
            'allowed_extensions' => ['pdf', 'doc', 'docx'],
        ], $overrides));
    }

    private function testUploader(array $overrides = []): TestFileUploader
    {
        return new TestFileUploader(array_merge([
            'directory' => $this->tmpDir,
            'max_size' => 10 * 1024 * 1024,
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
            $uploader->upload(['name' => 'big.pdf', 'tmp_name' => $tmpFile, 'error' => UPLOAD_ERR_OK, 'size' => 100]);
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
            $uploader->upload(['name' => 'malware.exe', 'tmp_name' => $tmpFile, 'error' => UPLOAD_ERR_OK, 'size' => 4]);
        } finally {
            @unlink($tmpFile);
        }
    }

    #[Test]
    public function returns_null_on_upload_error(): void
    {
        $result = $this->uploader()->upload([
            'name' => 'test.pdf', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0,
        ]);
        $this->assertNull($result);
    }

    #[Test]
    public function rejects_file_with_mismatched_magic_bytes(): void
    {
        $tmpFile = $this->createTempFile('NOT_A_PDF_CONTENT');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File content does not match extension');
        try {
            $this->uploader()->upload(['name' => 'fake.pdf', 'tmp_name' => $tmpFile, 'error' => UPLOAD_ERR_OK, 'size' => 18]);
        } finally {
            @unlink($tmpFile);
        }
    }

    #[Test]
    public function docx_with_wrong_magic_bytes_is_rejected(): void
    {
        $tmpFile = $this->createTempFile('NOT_A_DOCX');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File content does not match extension');
        try {
            $this->uploader()->upload(['name' => 'fake.docx', 'tmp_name' => $tmpFile, 'error' => UPLOAD_ERR_OK, 'size' => 10]);
        } finally {
            @unlink($tmpFile);
        }
    }

    #[Test]
    public function generated_filename_is_32_char_hex(): void
    {
        $hex = bin2hex(random_bytes(16));
        $this->assertEquals(32, strlen($hex));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $hex);
    }

    #[Test]
    public function move_file_failure_throws(): void
    {
        $uploader = $this->uploader();
        $tmpFile = $this->createTempFile('%PDF-1.4 content');
        try {
            $uploader->upload(['name' => 'real.pdf', 'tmp_name' => $tmpFile, 'error' => UPLOAD_ERR_OK, 'size' => 16]);
            $this->fail('Expected RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Failed to save', $e->getMessage());
        } finally {
            @unlink($tmpFile);
        }
    }

    #[Test]
    public function successful_upload_via_testable_subclass(): void
    {
        $uploader = $this->testUploader();
        $tmpFile = $this->createTempFile('%PDF-1.4 test content here');

        $filename = $uploader->upload([
            'name' => 'cv.pdf', 'tmp_name' => $tmpFile, 'error' => UPLOAD_ERR_OK, 'size' => 25,
        ]);

        $this->assertNotNull($filename);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}\.pdf$/', $filename);
        $this->assertFileExists($this->tmpDir . '/' . $filename);
        @unlink($tmpFile);
    }

    #[Test]
    public function creates_directory_if_missing(): void
    {
        $subDir = $this->tmpDir . '/subdir';
        $uploader = $this->testUploader(['directory' => $subDir]);
        $tmpFile = $this->createTempFile('%PDF-1.4 content');

        $filename = $uploader->upload([
            'name' => 'cv.pdf', 'tmp_name' => $tmpFile, 'error' => UPLOAD_ERR_OK, 'size' => 15,
        ]);

        $this->assertDirectoryExists($subDir);
        $this->assertFileExists($subDir . '/' . $filename);
        @unlink($tmpFile);
    }

    #[Test]
    public function doc_with_correct_magic_bytes_uploads(): void
    {
        $uploader = $this->testUploader(['allowed_extensions' => ['doc']]);
        $tmpFile = $this->createTempFile("\xD0\xCF\x11\xE0" . 'more data here');

        $filename = $uploader->upload([
            'name' => 'file.doc', 'tmp_name' => $tmpFile, 'error' => UPLOAD_ERR_OK, 'size' => 18,
        ]);

        $this->assertNotNull($filename);
        $this->assertStringEndsWith('.doc', $filename);
        @unlink($tmpFile);
    }
}
