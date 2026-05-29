<?php

declare(strict_types=1);

namespace App\Core;

final class UploadHelper
{
    private const MAX_IMAGE_SIZE = 5242880;
    private const ALLOWED_MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    public static function validateImageUpload(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Upload failed'];
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        $originalName = (string)($file['name'] ?? '');
        $size = (int)($file['size'] ?? 0);

        if ($tmpPath === '' || $originalName === '' || $size <= 0) {
            return ['success' => false, 'error' => 'File is empty or invalid'];
        }

        if ($size > self::MAX_IMAGE_SIZE) {
            return ['success' => false, 'error' => 'File too large'];
        }

        if (!is_uploaded_file($tmpPath)) {
            return ['success' => false, 'error' => 'Invalid uploaded file'];
        }

        $extension = strtolower(trim(pathinfo($originalName, PATHINFO_EXTENSION)));
        if ($extension === '') {
            return ['success' => false, 'error' => 'Invalid image extension'];
        }

        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        if (!in_array($extension, ['jpg', 'png', 'webp'], true)) {
            return ['success' => false, 'error' => 'Invalid image extension'];
        }

        // Kiểm tra magic bytes (header) để tránh giả mạo phần mở rộng
        if (!self::hasValidMagicBytes($tmpPath, $extension)) {
            return ['success' => false, 'error' => 'File header (magic bytes) không hợp lệ cho loại ảnh'];
        }

        $mime = self::detectMime($tmpPath);
        if ($mime === '') {
            return ['success' => false, 'error' => 'Unable to detect image MIME'];
        }

        $expectedMime = self::ALLOWED_MIME_TYPES[$extension] ?? null;
        if ($expectedMime === null || $mime !== $expectedMime) {
            return ['success' => false, 'error' => 'Extension and MIME mismatch'];
        }

        return [
            'success' => true,
            'extension' => $extension,
            'mime' => $mime,
        ];
    }

    public static function uploadImage(array $file, string $targetDir, string $publicPathPrefix = 'uploads'): array
    {
        $validation = self::validateImageUpload($file);
        if (!($validation['success'] ?? false)) {
            return $validation;
        }

        if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            return ['success' => false, 'error' => 'Cannot create upload directory'];
        }

        $extension = strtolower((string)$validation['extension']);
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $targetPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file((string)$file['tmp_name'], $targetPath)) {
            return ['success' => false, 'error' => 'Cannot move uploaded file'];
        }

        $normalizedPrefix = trim($publicPathPrefix, '/');
        $directoryName = basename(rtrim($targetDir, '/\\'));
        $path = ($normalizedPrefix !== '' ? $normalizedPrefix . '/' : '') . $directoryName . '/' . $filename;

        return [
            'success' => true,
            'path' => $path,
            'filename' => $filename,
            'extension' => $extension,
            'mime' => (string)$validation['mime'],
        ];
    }

    private static function detectMime(string $tmpPath): string
    {
        $mime = '';

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_file($finfo, $tmpPath);
                finfo_close($finfo);
                if (is_string($detected)) {
                    $mime = trim($detected);
                }
            }
        }

        if ($mime === '' && function_exists('mime_content_type')) {
            $detected = mime_content_type($tmpPath);
            if (is_string($detected)) {
                $mime = trim($detected);
            }
        }

        return strtolower($mime);
    }

    /**
     * Kiểm tra magic bytes cho các định dạng ảnh phổ biến.
     */
    private static function hasValidMagicBytes(string $tmpPath, string $extension): bool
    {
        if (!is_readable($tmpPath)) {
            return false;
        }

        $extension = strtolower($extension);
        $fh = fopen($tmpPath, 'rb');
        if ($fh === false) {
            return false;
        }

        $bytes = fread($fh, 12);
        fclose($fh);
        if ($bytes === false || $bytes === '') {
            return false;
        }

        $hex = bin2hex($bytes);

        switch ($extension) {
            case 'jpg':
                // JPEG: starts with FF D8
                return stripos($hex, 'ffd8') === 0;
            case 'png':
                // PNG: 89 50 4E 47 0D 0A 1A 0A
                return stripos($hex, '89504e470d0a1a0a') === 0;
            case 'webp':
                // WebP: RIFF....WEBP -> ASCII: '52494646' at start and '57454250' at offset 8
                // check first 12 bytes
                $start = substr($hex, 0, 8);
                $riff = strtolower($start) === '52494646';
                $fourcc = substr($hex, 16, 8);
                $webp = strtolower($fourcc) === '57454250';
                return $riff && $webp;
            default:
                return false;
        }
    }
}