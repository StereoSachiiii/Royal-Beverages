<?php
declare(strict_types=1);

namespace App\Admin\Services;

use finfo;
use RuntimeException;
use InvalidArgumentException;

class ImageService
{
    private string $baseDir;
    private string $baseUrl;
    private int $maxSizeBytes;
    private array $allowedMimeTypes;

    public function __construct(
        ?string $baseDir = null,
        ?string $baseUrl = null,
        int $maxSizeBytes = 5_000_000,
        array $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
    ) {
        $this->baseDir = rtrim($baseDir ?? (__DIR__ . '/../../storage'), DIRECTORY_SEPARATOR);
        
        // Use the globally defined WEB_ROOT to build the relative storage path
        $root = defined('WEB_ROOT') ? WEB_ROOT : '/';
        $this->baseUrl = rtrim($baseUrl ?? (rtrim($root, '/') . '/storage'), '/');
        
        $this->maxSizeBytes = $maxSizeBytes;
        $this->allowedMimeTypes = $allowedMimeTypes;
    }

    /**
     * @param string $entity   Logical entity name, e.g. "user", "product"
     * @param array  $file     One entry from $_FILES
     *
     * @return array{url: string, original_name: string, mime: string, size: int}
     */
    public function upload(string $entity, array $file): array
    {
        $entity = trim(strtolower($entity));
        if ($entity === '') {
            throw new InvalidArgumentException('Missing entity for image upload');
        }

        if (!isset($file['error'], $file['size'], $file['tmp_name'], $file['name'])) {
            throw new InvalidArgumentException('Invalid file upload payload');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException($this->mapUploadError($file['error']));
        }

        if ($file['size'] <= 0) {
            throw new RuntimeException('Uploaded file is empty');
        }

        if ($file['size'] > $this->maxSizeBytes) {
            throw new RuntimeException('Image is too large');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
        if (!in_array($mime, $this->allowedMimeTypes, true)) {
            throw new RuntimeException('Unsupported image type');
        }

        $ext = $this->guessExtension($mime, $file['name']);

        $dir = $this->baseDir . DIRECTORY_SEPARATOR . $entity . DIRECTORY_SEPARATOR . 'images';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Failed to create image directory');
        }

        $hash = bin2hex(random_bytes(16));
        $fileName = sprintf('%s_%s.%s', $entity, $hash, $ext);
        $destPath = $dir . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new RuntimeException('Failed to store uploaded image');
        }

        $relativePath = sprintf('%s/%s/images/%s', $this->baseUrl, $entity, $fileName);

        return [
            'url'           => $relativePath,
            'original_name' => (string) $file['name'],
            'mime'          => $mime,
            'size'          => (int) $file['size'],
        ];
    }

    private function mapUploadError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Uploaded image exceeds maximum size',
            UPLOAD_ERR_PARTIAL                      => 'Image was only partially uploaded',
            UPLOAD_ERR_NO_FILE                      => 'No image file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR                   => 'Missing temporary folder for uploads',
            UPLOAD_ERR_CANT_WRITE                   => 'Failed to write uploaded image to disk',
            UPLOAD_ERR_EXTENSION                    => 'A PHP extension stopped the file upload',
            default                                 => 'Unknown upload error',
        };
    }

    private function guessExtension(string $mime, string $originalName): string
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext !== '') {
            return $ext;
        }

        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => 'bin',
        };
    }
}
