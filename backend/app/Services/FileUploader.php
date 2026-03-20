<?php

class FileUploader
{
    private string $directory;
    private int $maxSize;
    private array $allowedExtensions;

    private const MAGIC_BYTES = [
        'pdf'  => '%PDF',
        'doc'  => "\xD0\xCF\x11\xE0",
        'docx' => "PK\x03\x04",
    ];

    public function __construct(array $config)
    {
        $this->directory         = $config['directory'];
        $this->maxSize           = $config['max_size'];
        $this->allowedExtensions = $config['allowed_extensions'];
    }

    public function upload(array $file): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        if ($file['size'] > $this->maxSize) {
            throw new RuntimeException('File exceeds maximum size');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions, true)) {
            SecurityLogger::log('blocked_file_type', ['ext' => $extension, 'name' => $file['name']]);
            throw new RuntimeException('File type not allowed');
        }

        $this->verifyMagicBytes($file['tmp_name'], $extension);

        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $this->directory . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Failed to save uploaded file');
        }

        return $filename;
    }

    private function verifyMagicBytes(string $tmpPath, string $extension): void
    {
        if (!isset(self::MAGIC_BYTES[$extension])) return;

        $expected = self::MAGIC_BYTES[$extension];
        $handle = fopen($tmpPath, 'rb');
        $header = fread($handle, strlen($expected));
        fclose($handle);

        if ($header !== $expected) {
            SecurityLogger::log('magic_bytes_mismatch', [
                'ext'      => $extension,
                'expected' => bin2hex($expected),
                'got'      => bin2hex($header),
            ]);
            throw new RuntimeException('File content does not match extension');
        }
    }
}
