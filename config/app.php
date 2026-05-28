<?php

return [
    'db' => [
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => (int)(getenv('DB_PORT') ?: 3306),
    'name' => getenv('DB_NAME') ?: 'cleaning_db',
    'user' => getenv('DB_USER') ?: 'cleaning_user',
    'pass' => getenv('DB_PASS') ?: 'cleaning_pass',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
  ],
  'app' => [
    'base_url' => getenv('APP_BASE_URL') ?: 'https://suasively-metaphoric-gearldine.ngrok-free.dev',
    'session_name' => getenv('APP_SESSION_NAME') ?: 'CLEANINGSESSID',
  ],
  'payos' => [
    'client_id' => getenv('PAYOS_CLIENT_ID') ?: null,
    'api_key' => getenv('PAYOS_API_KEY') ?: null,
    // checksum_key is sensitive; prefer setting PAYOS_CHECKSUM_KEY in environment
    'checksum_key' => getenv('PAYOS_CHECKSUM_KEY') ?: null,
    'webhook_url' => getenv('PAYOS_WEBHOOK_URL') ?: 'https://suasively-metaphoric-gearldine.ngrok-free.dev/webhook.php',
    'bank_account_number' => getenv('PAYOS_BANK_ACCOUNT') ?: null,
    'bank_account_name' => getenv('PAYOS_BANK_ACCOUNT_NAME') ?: null,
  ],
  'admin' => [
    'login_key' => getenv('ADMIN_LOGIN_KEY') ?: 'admin-secret-key-040803',
  ],
];