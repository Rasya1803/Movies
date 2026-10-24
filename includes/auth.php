<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Helper base URL otomatis (misal: /BIOSKOP)
 */
function baseUrl(string $path = ''): string
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $root = preg_replace('#/(public|user|operator|admin|api)$#', '', $scriptDir);
    $root = rtrim($root, '/');
    return $root . '/' . ltrim($path, '/');
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user']);
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('danger', 'Silakan login terlebih dahulu untuk melanjutkan.');
        header('Location: ' . baseUrl('public/login.php'));
        exit;
    }
}

/**
 * Batasi halaman berdasarkan role (contoh: ['admin'] atau ['operator', 'admin'])
 */
function requireRole(array $allowedRoles): void
{
    requireLogin();
    $user = currentUser();
    if (!in_array($user['role'], $allowedRoles, true)) {
        http_response_code(403);
        setFlash('danger', 'Akses ditolak: Anda tidak memiliki hak akses ke halaman tersebut.');
        header('Location: ' . baseUrl('public/index.php'));
        exit;
    }
}
