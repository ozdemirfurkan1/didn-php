<?php
// `user` tablosunu oluşturur (yoksa) ve config'teki admin_email/admin_password
// ile admin hesabını ekler/günceller. Şifre bcrypt (password_hash) ile saklanır.
// Kullanım:  php scripts/seed-admin.php

declare(strict_types=1);
require __DIR__ . '/../inc/db.php';

$c     = config();
$email = mb_strtolower(trim((string) ($c['admin_email'] ?? '')), 'UTF-8');
$pass  = (string) ($c['admin_password'] ?? '');

if ($email === '' || $pass === '') {
    fwrite(STDERR, "config.local.php içinde admin_email ve admin_password tanımlı olmalı.\n");
    exit(1);
}

db()->exec("CREATE TABLE IF NOT EXISTS user (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255),
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$hash = password_hash($pass, PASSWORD_DEFAULT);
$stmt = db()->prepare(
    "INSERT INTO user (email, name, password_hash, role)
     VALUES (:e, 'Admin', :h, 'admin')
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'admin'"
);
$stmt->execute([':e' => $email, ':h' => $hash]);

echo "✓ Admin hazır: {$email} (rol: admin)\n";
