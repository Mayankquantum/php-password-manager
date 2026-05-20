<?php

require __DIR__ . '/config.php';

// Simple PSR-4 style autoloader: App\Foo  ->  src/Foo.php
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

session_start();

/** Escape output for safe HTML rendering. */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/** Is someone logged in this session? */
function is_logged_in(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['data_key']);
}

/** Redirect to login if not authenticated. */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/** Raw data KEY for the current session (decoded from base64). */
function session_data_key(): string
{
    return base64_decode($_SESSION['data_key'], true);
}
