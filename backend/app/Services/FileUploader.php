<?php

/**
 * Secure file upload handler with extension and magic-byte validation.
 *
 * Uploaded files are:
 *  1. Checked against a size limit
 *  2. Validated by file extension whitelist
 *  3. Verified by magic bytes (file header) to prevent extension spoofing
 *  4. Renamed with a cryptographically random hex name
 *  5. Stored outside the web document root
 */
class FileUploader
{
    /** @var string Absolute path to the upload directory */
    private string $directory;

    /** @var int Maximum allowed file size in bytes */
    private int $maxSize;

    /** @var string[] Allowed lowercase file extensions */
    private array $allowedExtensions;

    /**
     * Expected file header bytes per extension.
     * Used to detect files that have been renamed to bypass extension filters.
     */
    private const MAGIC_BYTES = [
        'pdf'  => '%PDF',
        'doc'  => "\xD0\xCF\x11\xE0",
        'docx' => "PK\x03\x04",
    ];

    /**
     * @param array $config  Keys: directory, max_size, allowed_extensions
     */
    public function __construct(array $config)
    {
        $this->directory         = $config['directory'];
        $this->maxSize           = $config['max_size'];
        $this->allowedExtensions = $config['allowed_extensions'];
    }

    /**
     * Process and store an uploaded file.
     *
     * @param  array       $file  PHP $_FILES entry for the uploaded file
     * @return string|null        Generated filename on success, null if no file
     * @throws RuntimeException   On validation failure or save error
     */
    public function upload(array $file): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        if ($file['size'] > $this->maxSize) {
            throw new RuntimeException('File exceeds maximum size');
        }

        // Validate extension against whitelist
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions, true)) {
            SecurityLogger::log('blocked_file_type', ['ext' => $extension, 'name' => $file['name']]);
            throw new RuntimeException('File type not allowed');
        }

        // Verify that file contents match the declared extension
        $this->verifyMagicBytes($file['tmp_name'], $extension);

        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }

        // Generate a random, non-guessable filename (32 hex chars)
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $this->directory . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Failed to save uploaded file');
        }

        return $filename;
    }

    /**
     * Compare the file's actual header bytes against expected magic bytes.
     *
     * Skips the check for extensions without a known signature.
     * Throws on mismatch to prevent disguised malicious files.
     */
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
