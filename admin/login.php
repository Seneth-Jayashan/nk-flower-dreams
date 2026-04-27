<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/security.php';

$pdo = Database::connection();

if (is_admin_logged_in()) {
    redirect('dashboard.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $csrf = (string) ($_POST['csrf_token'] ?? '');
    $ip = client_ip();

    if (!verify_csrf($csrf, 'admin_login')) {
        $error = 'Security token mismatch. Please try again.';
    } elseif (is_login_blocked($pdo, $email, $ip)) {
        $error = 'Too many failed attempts. Please wait 15 minutes.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Invalid login credentials.';
        record_login_attempt($pdo, $email, $ip, false);
    } elseif (!authenticate_admin($pdo, $email, $password)) {
        $error = 'Invalid login credentials.';
        record_login_attempt($pdo, $email, $ip, false);
    } else {
        record_login_attempt($pdo, $email, $ip, true);
        clear_failed_attempts($pdo, $email, $ip);
        redirect('dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | NK Flower Dreams</title>
    <meta name="theme-color" content="#0d0a08">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card">
            <div class="auth-badge">NK Flower Dreams Admin</div>
            <h1>Admin Sign In</h1>
            <p>Secure access to product management</p>

            <?php if (isset($_GET['expired'])): ?>
                <div class="alert alert-warning">Session expired. Please log in again.</div>
            <?php endif; ?>

            <?php if ($error !== null): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($message = flash('success')): ?>
                <div class="alert alert-success"><?= e($message) ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <?= csrf_field('admin_login') ?>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autocomplete="username">

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">

                <button type="submit">Sign In</button>
            </form>

            <a class="btn btn-ghost" href="../index.php" target="_blank" rel="noopener" style="margin-top: 0.7rem; width: 100%;">View Website</a>

            <a class="setup-link" href="setup.php">First-time setup</a>
        </section>
    </main>
</body>
</html>
