<?php
// Yapılandırma ve veritabanı bağlantısı. Gerçek değerler config.local.php'de
// (sürüm kontrolüne girmez). Bağlantı tek sefer kurulur (singleton).

declare(strict_types=1);

function config(): array
{
    static $c = null;
    if ($c === null) {
        $local = __DIR__ . '/config.local.php';
        if (!is_file($local)) {
            http_response_code(500);
            exit('Yapılandırma eksik: inc/config.local.php oluşturun (config.example.php\'yi kopyalayın).');
        }
        $c = require $local;
    }
    return $c;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config();
        $dsn = "mysql:host={$c['db_host']};port={$c['db_port']};dbname={$c['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $c['db_user'], $c['db_pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
