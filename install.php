<?php
// ============================================================================
// TEK SEFERLİK KURULUM — SSH olmadan tabloları oluşturur ve admini ekler.
// Çalıştırma: tarayıcıda  https://didn.net/install.php
// !!! ÇALIŞTIRDIKTAN SONRA BU DOSYAYI MUTLAKA SİL (güvenlik) !!!
// ============================================================================

declare(strict_types=1);
require __DIR__ . '/inc/db.php';
header('Content-Type: text/plain; charset=utf-8');

try {
    db()->exec("CREATE TABLE IF NOT EXISTS content (
        id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(20) NOT NULL,
        slug VARCHAR(255) NOT NULL UNIQUE,
        title VARCHAR(500) NOT NULL,
        summary TEXT,
        body LONGTEXT NOT NULL,
        cover_image VARCHAR(1000),
        status VARCHAR(20) NOT NULL DEFAULT 'draft',
        source VARCHAR(50),
        topic VARCHAR(500),
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        published_at DATETIME NULL,
        INDEX idx_content_type_status (type, status),
        INDEX idx_content_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✓ content tablosu hazır\n";

    db()->exec("CREATE TABLE IF NOT EXISTS app_setting (
        `key` VARCHAR(100) NOT NULL PRIMARY KEY,
        `value` LONGTEXT,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✓ app_setting tablosu hazır\n";

    db()->exec("CREATE TABLE IF NOT EXISTS user (
        id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        name VARCHAR(255),
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'user',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✓ user tablosu hazır\n";

    $c     = config();
    $email = mb_strtolower(trim((string) ($c['admin_email'] ?? '')), 'UTF-8');
    $pass  = (string) ($c['admin_password'] ?? '');
    if ($email !== '' && $pass !== '') {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        db()->prepare(
            "INSERT INTO user (email, name, password_hash, role)
             VALUES (:e, 'Admin', :h, 'admin')
             ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'admin'"
        )->execute([':e' => $email, ':h' => $hash]);
        echo "✓ Admin hazır: {$email}\n";
    } else {
        echo "! config.local.php içinde admin_email/admin_password yok — admin eklenmedi\n";
    }

    $hasDict = db()->query("SHOW TABLES LIKE 'translate'")->fetch();
    echo $hasDict
        ? "✓ Sözlük verisi (translate tablosu) mevcut\n"
        : "! UYARI: Sözlük verisi YOK — didn-sozluk.sql.gz dump'ını import etmelisin\n";

    echo "\n=== KURULUM TAMAMLANDI ===\n";
    echo ">>> ŞİMDİ BU install.php DOSYASINI FileZilla İLE SİL! <<<\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "HATA: " . $e->getMessage() . "\n";
    echo "(Veritabanı bilgileri config.local.php'de doğru mu kontrol et.)\n";
}
