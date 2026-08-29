<?php
declare(strict_types=1);

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
}

function logout_user(): void
{
    unset($_SESSION['user']);
    session_regenerate_id(true);
}

function require_login(): void
{
    if (!current_user()) {
        flash('warning', 'Bu alanı görmek için giriş yapmalısınız.');
        redirect('giris');
    }
}

function require_admin(): void
{
    $user = current_user();
    if (!$user || !in_array($user['role'], ['admin', 'support'], true)) {
        flash('warning', 'Yönetim paneli için yetkiniz bulunmuyor.');
        redirect('admin');
    }
}

function ensure_seed_admin(): void
{
    $statement = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $statement->execute([ADMIN_SEED_EMAIL]);
    if ($statement->fetch()) {
        return;
    }

    $insert = db()->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
    $insert->execute(['Dijirota Yönetici', ADMIN_SEED_EMAIL, password_hash(ADMIN_SEED_PASSWORD, PASSWORD_DEFAULT)]);
}
