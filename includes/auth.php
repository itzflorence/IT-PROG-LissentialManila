<?php

declare(strict_types=1);

function ensure_session_started(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function is_authenticated(): bool
{
    ensure_session_started();

    if (!isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'])) {
        return false;
    }

    $userId = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $username = trim((string) $_SESSION['username']);
    $role = strtolower((string) $_SESSION['role']);

    return $userId !== false
        && $username !== ''
        && in_array($role, ['student', 'official', 'admin'], true);
}

function require_login(string $loginPath = '../auth/login.php'): void
{
    ensure_session_started();

    if (!is_authenticated()) {
        header('Location: ' . $loginPath);
        exit;
    }
}
