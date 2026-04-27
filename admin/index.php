<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/security.php';

if (is_admin_logged_in()) {
    redirect('dashboard.php');
}

redirect('login.php');
