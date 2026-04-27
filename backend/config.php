<?php
declare(strict_types=1);

// Database configuration. In production, prefer environment variables.
define('DB_HOST', getenv('NK_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('NK_DB_PORT') ?: '3306');
define('DB_NAME', getenv('NK_DB_NAME') ?: 'nk_flower_dreams');
define('DB_USER', getenv('NK_DB_USER') ?: 'root');
define('DB_PASS', getenv('NK_DB_PASS') ?: '');

// Session security settings.
define('SESSION_NAME', 'NKFDSESSID');
define('SESSION_IDLE_TIMEOUT', 1800); // 30 minutes
define('SESSION_REGEN_INTERVAL', 300); // 5 minutes

define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCK_MINUTES', 15);

define('UPLOAD_DIR', __DIR__ . '/../images/uploads');
define('UPLOAD_URL_BASE', 'images/uploads');
define('MAX_UPLOAD_BYTES', 10 * 1024 * 1024); // 10MB

const ALLOWED_IMAGE_MIME = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];
