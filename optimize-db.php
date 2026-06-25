<?php
// Tek seferlik bakım: sözlük aramasını hızlandıran index'leri ekler.
// Güvenlik: config.local.php'deki cron_secret ile korunur.
// Kullanım:  https://didn.net/optimize-db.php?secret=<CRON_SECRET>
// Çalıştırdıktan sonra bu dosyayı sunucudan SİLEBİLİRSİN.

declare(strict_types=1);
if (!function_exists('db')) {
    require __DIR__ . '/inc/db.php';
}

header('Content-Type: text/plain; charset=utf-8');

$secret = config()['cron_secret'] ?? '';
if ($secret === '' || !hash_equals($secret, (string) ($_GET['secret'] ?? ''))) {
    http_response_code(401);
    exit("Yetkisiz. Doğru ?secret= ver.\n");
}

// (tablo, kolon, index adı) — arama WHERE/JOIN kolonları.
$targets = [
    ['english',      'word',       'idx_english_word'],
    ['turkish',      'word',       'idx_turkish_word'],
    ['word_details', 'word_lower', 'idx_word_details_word_lower'],
    ['translate',    'english_id', 'idx_translate_english_id'],
    ['translate',    'turkish_id', 'idx_translate_turkish_id'],
];

function index_exists(string $table, string $name): bool
{
    try {
        $stmt = db()->prepare("SHOW INDEX FROM `$table` WHERE Key_name = :n");
        $stmt->execute([':n' => $name]);
        return (bool) $stmt->fetch();
    } catch (Throwable $e) {
        return false;
    }
}

foreach ($targets as [$table, $col, $idx]) {
    if (index_exists($table, $idx)) {
        echo "• zaten var: $table.$col ($idx)\n";
        continue;
    }
    try {
        db()->exec("ALTER TABLE `$table` ADD INDEX `$idx` (`$col`)");
        echo "✓ eklendi:   $table.$col ($idx)\n";
    } catch (Throwable $e) {
        // Kolon çok uzunsa önek (prefix) ile dene.
        try {
            db()->exec("ALTER TABLE `$table` ADD INDEX `$idx` (`$col`(191))");
            echo "✓ eklendi (önek 191): $table.$col ($idx)\n";
        } catch (Throwable $e2) {
            echo "! atlandı:    $table.$col ($idx) — " . $e2->getMessage() . "\n";
        }
    }
}

echo "\nBitti. Aramayı test et; bittiyse bu dosyayı sunucudan silebilirsin.\n";
