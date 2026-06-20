<?php
// content ve app_setting tablolarını oluşturur (yoksa). Sözlük tablolarına
// dokunmaz. Kullanım:  php scripts/setup.php

declare(strict_types=1);
require __DIR__ . '/../inc/db.php';

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

db()->exec("CREATE TABLE IF NOT EXISTS app_setting (
    `key` VARCHAR(100) NOT NULL PRIMARY KEY,
    `value` LONGTEXT,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

echo "✓ content ve app_setting tabloları hazır\n";
