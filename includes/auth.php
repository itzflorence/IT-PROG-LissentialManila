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

    return isset($_SESSION['user_id']);
}

function require_login(string $loginPath = '../auth/login.php'): void
{
    ensure_session_started();

    if (!is_authenticated()) {
        header('Location: ' . $loginPath);
        exit;
    }
}
