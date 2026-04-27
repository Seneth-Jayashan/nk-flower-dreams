<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/security.php';

$pdo = Database::connection();
$exists = (int) $pdo->query('SELECT COUNT(*) AS total FROM admins')->fetch()['total'] > 0;

if ($exists) {
    flash('success', 'Admin account already exists. Please sign in.');
    redirect('login.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    $csrf = (string) ($_POST['csrf_token'] ?? '');

    if (!verify_csrf($csrf, 'admin_setup')) {
        $error = 'Security token mismatch. Please retry.';
    } elseif ($name === '' || mb_strlen($name) < 3 || mb_strlen($name) > 120) {
        $error = 'Please enter a valid name.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{10,}$/', $password)) {
        $error = 'Password must be at least 10 chars with upper, lower, number, and symbol.';
    } elseif ($password !== $confirm) {
        $error = 'Password confirmation does not match.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO admins (full_name, email, password_hash) VALUES (:name, :email, :hash)');
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':hash' => $hash,
        ]);

        flash('success', 'Admin account created successfully. Sign in now.');
        redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup | NK Flower Dreams</title>
    <meta name="theme-color" content="#0d0a08">
    <link rel="stylesheet" href="admin.css">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-card">
            <div class="auth-badge">NK Flower Dreams Admin</div>
            <h1>First-Time Admin Setup</h1>
            <p>Create your secure administrator account</p>

            <?php if ($error !== null): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <?= csrf_field('admin_setup') ?>
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required minlength="3" maxlength="120">

                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="10">

                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="10">

                <button type="submit">Create Admin</button>
            </form>

            <a class="setup-link" href="login.php">Back to login</a>
        </section>
    </main>
</body>
</html>
