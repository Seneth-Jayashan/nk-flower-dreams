<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function client_ip(): string
{
    return isset($_SERVER['REMOTE_ADDR']) ? substr($_SERVER['REMOTE_ADDR'], 0, 45) : 'unknown';
}

function csrf_token(string $scope = 'default'): string
{
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }

    if (empty($_SESSION['csrf_tokens'][$scope])) {
        $_SESSION['csrf_tokens'][$scope] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_tokens'][$scope];
}

function csrf_field(string $scope = 'default'): string
{
    $token = csrf_token($scope);
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

function verify_csrf(?string $token, string $scope = 'default'): bool
{
    $saved = $_SESSION['csrf_tokens'][$scope] ?? '';
    return is_string($token) && $saved !== '' && hash_equals($saved, $token);
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }

    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $message = (string) $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $message;
}

function enforce_session_security(): void
{
    if (empty($_SESSION['admin_id'])) {
        return;
    }

    $now = time();

    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = $now;
    }

    if (($now - (int) $_SESSION['last_activity']) > SESSION_IDLE_TIMEOUT) {
        session_unset();
        session_destroy();
        redirect('login.php?expired=1');
    }

    $_SESSION['last_activity'] = $now;

    if (!isset($_SESSION['last_regen'])) {
        $_SESSION['last_regen'] = $now;
    }

    if (($now - (int) $_SESSION['last_regen']) > SESSION_REGEN_INTERVAL) {
        session_regenerate_id(true);
        $_SESSION['last_regen'] = $now;
    }
}

function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_admin_login(): void
{
    enforce_session_security();

    if (!is_admin_logged_in()) {
        redirect('login.php');
    }
}

function is_login_blocked(PDO $pdo, string $email, string $ip): bool
{
        $lockMinutes = (int) LOGIN_LOCK_MINUTES;
    $sql = 'SELECT COUNT(*) AS total
            FROM login_attempts
                        WHERE attempted_at >= (NOW() - INTERVAL ' . $lockMinutes . ' MINUTE)
              AND success = 0
              AND (ip_address = :ip OR email = :email)';

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':ip', $ip);
    $stmt->bindValue(':email', $email);
    $stmt->execute();

    $result = $stmt->fetch();
    $count = (int) ($result['total'] ?? 0);

    return $count >= LOGIN_MAX_ATTEMPTS;
}

function record_login_attempt(PDO $pdo, string $email, string $ip, bool $success): void
{
    $stmt = $pdo->prepare('INSERT INTO login_attempts (email, ip_address, success) VALUES (:email, :ip, :success)');
    $stmt->execute([
        ':email' => $email,
        ':ip' => $ip,
        ':success' => $success ? 1 : 0,
    ]);
}

function clear_failed_attempts(PDO $pdo, string $email, string $ip): void
{
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE success = 0 AND (email = :email OR ip_address = :ip)');
    $stmt->execute([
        ':email' => $email,
        ':ip' => $ip,
    ]);
}

function authenticate_admin(PDO $pdo, string $email, string $password): bool
{
    $stmt = $pdo->prepare('SELECT id, full_name, password_hash FROM admins WHERE email = :email AND is_active = 1 LIMIT 1');
    $stmt->execute([':email' => $email]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, (string) $admin['password_hash'])) {
        return false;
    }

    if (password_needs_rehash((string) $admin['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $rehashStmt = $pdo->prepare('UPDATE admins SET password_hash = :hash WHERE id = :id');
        $rehashStmt->execute([
            ':hash' => $newHash,
            ':id' => (int) $admin['id'],
        ]);
    }

    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];
    $_SESSION['admin_name'] = (string) $admin['full_name'];
    $_SESSION['last_activity'] = time();
    $_SESSION['last_regen'] = time();

    return true;
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');

    if ($text === '') {
        $text = 'product-' . bin2hex(random_bytes(3));
    }

    return $text;
}

function validate_price(string $input): ?string
{
    $input = trim($input);
    if ($input === '') {
        return null;
    }

    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $input)) {
        return null;
    }

    return $input;
}

function process_product_image_upload(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed.');
    }

    if (($file['size'] ?? 0) > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('Image is too large. Maximum size is 3MB.');
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException('Invalid image upload.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmpPath);

    if (!isset(ALLOWED_IMAGE_MIME[$mime])) {
        throw new RuntimeException('Unsupported image type. Use JPG, PNG, or WEBP.');
    }

    $extension = ALLOWED_IMAGE_MIME[$mime];
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true) && !is_dir(UPLOAD_DIR)) {
        throw new RuntimeException('Unable to create upload directory.');
    }

    $destination = UPLOAD_DIR . '/' . $filename;
    if (!move_uploaded_file($tmpPath, $destination)) {
        throw new RuntimeException('Unable to save uploaded image.');
    }

    return UPLOAD_URL_BASE . '/' . $filename;
}
