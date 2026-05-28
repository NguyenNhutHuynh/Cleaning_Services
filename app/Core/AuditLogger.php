<?php

declare(strict_types=1);

namespace App\Core;

final class AuditLogger
{
    public static function write(string $action, array $context = []): void
    {
        $entry = [
            'time' => date('c'),
            'action' => $action,
            'context' => $context,
        ];

        $logFile = self::logFile();
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        @file_put_contents($logFile, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private static function logFile(): string
    {
        return dirname(__DIR__) . '/Logs/audit.log';
    }
}