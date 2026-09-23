<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function is_authenticated(): bool
{
    return isset($_SESSION['user']['id'], $_SESSION['user']['role']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_authentication(): void
{
    if (!is_authenticated()) {
        redirect('login.php');
    }
}

function require_role(string|array $roles): void
{
    require_authentication();

    if (!user_has_role($roles)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function user_has_role(string|array $roles): bool
{
    return is_authenticated()
        && in_array($_SESSION['user']['role'], (array) $roles, true);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

function post_text(string $key): string
{
    return trim((string) ($_POST[$key] ?? ''));
}

function valid_enum(string $value, array $allowed): bool
{
    return in_array($value, $allowed, true);
}

function valid_date_value(string $value, string $format = 'Y-m-d H:i:s'): bool
{
    $date = DateTime::createFromFormat($format, $value);
    return $date !== false && $date->format($format) === $value;
}