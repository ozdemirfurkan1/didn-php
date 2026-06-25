<?php
// Hafif kimlik doğrulama: PHP oturumu + password_hash (bcrypt).
// Kullanıcılar `user` tablosunda. role: "user" | "admin".

declare(strict_types=1);

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
            'path'     => '/',
        ]);
        session_start();
    }
}

// Oturumdaki kullanıcıyı yükler (id, email, name, role) ya da null.
function current_user(): ?array
{
    static $loaded = false;
    static $user = null;
    if ($loaded) {
        return $user;
    }
    $loaded = true;
    start_session();
    if (empty($_SESSION['uid'])) {
        return null;
    }
    $stmt = db()->prepare("SELECT id, email, name, role FROM user WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['uid']]);
    $user = $stmt->fetch() ?: null;
    if (!$user) {
        unset($_SESSION['uid']);
    }
    return $user;
}

function is_admin(): bool
{
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

function login_user(string $email, string $password): bool
{
    $email = mb_strtolower(trim($email), 'UTF-8');
    $stmt  = db()->prepare("SELECT id, password_hash FROM user WHERE email = :e");
    $stmt->execute([':e' => $email]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($password, $row['password_hash'])) {
        return false;
    }
    start_session();
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $row['id'];
    return true;
}

// Yeni kullanıcı (her zaman role=user). ['ok'=>bool, 'error'=>?string]
function register_user(string $email, string $password, string $name): array
{
    $email = mb_strtolower(trim($email), 'UTF-8');
    if ($email === '' || $password === '') {
        return ['ok' => false, 'error' => 'E-posta ve şifre gerekli'];
    }
    if (mb_strlen($password) < 6) {
        return ['ok' => false, 'error' => 'Şifre en az 6 karakter olmalı'];
    }
    $stmt = db()->prepare("SELECT id FROM user WHERE email = :e");
    $stmt->execute([':e' => $email]);
    if ($stmt->fetch()) {
        return ['ok' => false, 'error' => 'Bu e-posta zaten kayıtlı'];
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins  = db()->prepare(
        "INSERT INTO user (email, name, password_hash, role) VALUES (:e, :n, :h, 'user')"
    );
    $ins->execute([':e' => $email, ':n' => ($name !== '' ? $name : null), ':h' => $hash]);
    start_session();
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) db()->lastInsertId();
    return ['ok' => true];
}

function logout_user(): void
{
    start_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// Giriş yapılmamışsa /giris'e yönlendir (route koruması).
function require_login(): array
{
    $u = current_user();
    if (!$u) {
        redirect('/giris');
    }
    return $u;
}

// Admin değilse yönlendir (route koruması).
function require_admin(): array
{
    $u = current_user();
    if (!$u) {
        redirect('/giris');
    }
    if ($u['role'] !== 'admin') {
        redirect('/');
    }
    return $u;
}

// --- CSRF -----------------------------------------------------------------

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): bool
{
    start_session();
    return isset($_POST['_csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string) $_POST['_csrf']);
}
