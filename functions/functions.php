<?php

/**
 * Load .env file
 */
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Load .env on require
loadEnv(dirname(__DIR__) . '/.env');

/**
 * Get env variable with default
 */
function env(string $key, mixed $default = null): mixed {
    $val = $_ENV[$key] ?? getenv($key);
    return ($val !== false && $val !== '') ? $val : $default;
}

/**
 * Sanitize output (prevent XSS)
 */
function e(mixed $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect
 */
function redirect(string $url): never {
    header("Location: $url");
    exit;
}

/**
 * Flash message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user
 */
function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Role check
 */
function isAdmin(): bool {
    return ($_SESSION['user']['role'] ?? '') === 'admin';
}

/**
 * Require login
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect('/auth/login.php');
    }
}

/**
 * Require admin
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        redirect('/dashboard.php');
    }
}

/**
 * Format currency (Philippine Peso)
 */
function peso(float $amount): string {
    return '₱ ' . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate(string $date, string $format = 'M d, Y h:i A'): string {
    return date($format, strtotime($date));
}

/**
 * Base URL
 */
function baseUrl(string $path = ''): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'casestudy';
    return $scheme . '://' . $host . '/' . ltrim($path, '/');
}
