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
}