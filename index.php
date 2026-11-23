<?php
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/funciones.php';

if (!isLoggedIn()) {
    header('Location: views/auth/login.php');
    exit;
}

if (isAdmin()) {
    header('Location: views/dashboard/dashboard.php');
    exit;
}

header('Location: views/menu/menu.php');
exit;
