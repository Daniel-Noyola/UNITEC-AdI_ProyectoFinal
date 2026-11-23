<?php
/**
 * Funciones compartidas de autenticación, sesión y permisos.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getCurrentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool
{
    return getCurrentUser() !== null;
}

function isAdmin(): bool
{
    $user = getCurrentUser();
    return $user !== null && $user['rol'] === 'admin';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('danger', 'Debes iniciar sesión para acceder a esta sección.');
        header('Location: ../auth/login.php');
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        setFlash('danger', 'Acceso no autorizado. Se requieren permisos de administrador.');
        header('Location: ../menu/menu.php');
        exit;
    }
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}
