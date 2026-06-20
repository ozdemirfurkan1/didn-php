<?php
// ============================================================================
// SÖZLÜK IMPORT — didn-sozluk.sql.gz'i veritabanına aktarır. SSH gerektirmez.
// Kullanım: didn-sozluk.sql.gz'i httpdocs'a yükle, sonra tarayıcıda aç:
//           https://didn.net/import-dict.php          (devam eder)
//           https://didn.net/import-dict.php?reset=1  (baştan başlatır)
// Sayfa kendini yenileyerek devam eder. Bitince BU DOSYAYI + dump'ı SİL.
// ============================================================================

declare(strict_types=1);
@set_time_limit(0);
@ini_set('memory_limit', '512M');
ignore_user_abort(true);
require __DIR__ . '/inc/db.php';

$DUMP   = __DIR__ . '/didn-sozluk.sql.gz';
$STATE  = __DIR__ . '/.import-state';
$BUDGET = 180; // saniye/tur

while (ob_get_level()) {
    ob_end_flush();
}
ob_implicit_flush(true);
echo "<!doctype html><meta charset='utf-8'><pre style='font:14px monospace;padding:20px'>";

if (($_GET['reset'] ?? '') === '1') {
    @unlink($STATE);
    echo "(durum sıfırlandı, baştan)\n";
}
if (!is_file($DUMP)) {
    exit("HATA: didn-sozluk.sql.gz bulunamadı. Önce httpdocs'a yükle.\n");
}

$done = is_file($STATE) ? (int) file_get_contents($STATE) : 0;
$gz   = gzopen($DUMP, 'rb');
if (!$gz) {
    exit("HATA: dump açılamadı.\n");
}

$pdo = db();
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");
$pdo->exec("SET UNIQUE_CHECKS=0");
$pdo->exec("SET NAMES utf8mb4");

// Atlanacak kontrol ifadeleri (veri için gereksiz, kendi bağlantımızda yönetiriz).
function is_control_stmt(string $head): bool
{
    foreach (['SET ', 'SET@', 'COMMIT', 'START TRANSACTION', 'BEGIN', 'ROLLBACK', 'LOCK TABLE', 'UNLOCK TAB'] as $p) {
        if (str_starts_with($head, $p)) {
            return true;
        }
    }
    return false;
}

$count = 0;
$ran   = 0;
$buf   = '';
$start = microtime(true);

while (!gzeof($gz)) {
    $line = gzgets($gz);
    if ($line === false) {
        break;
    }
    $t = ltrim($line);
    if ($t === '' || $t[0] === '-' || $t[0] === '/') {
        continue; // boş / yorum / koşullu yorum
    }
    $buf .= $line;
    if (substr(rtrim($line), -1) !== ';') {
        continue; // çok satırlı ifade (CREATE TABLE) tamamlanmadı
    }

    $stmt = $buf;
    $buf  = '';
    $count++;
    if ($count <= $done) {
        continue; // önceki turlarda yapıldı
    }

    // Çok yeni MariaDB collation'ını (uca1400) eski sunucuların desteklediğiyle değiştir.
    if (strpos($stmt, 'uca1400') !== false) {
        $stmt = preg_replace('/utf8mb4_uca1400_\w+/', 'utf8mb4_turkish_ci', $stmt);
    }

    $head = strtoupper(substr(ltrim($stmt), 0, 18));
    if (!is_control_stmt($head)) {
        try {
            $pdo->exec($stmt);
            $ran++;
            if ($ran % 200 === 0) {
                echo '.';
                file_put_contents($STATE, (string) $count); // ara kayıt (kesilirse buradan devam)
            }
        } catch (Throwable $ex) {
            $m = $ex->getMessage();
            if (stripos($m, 'Duplicate') === false && stripos($m, 'exists') === false) {
                echo "HATA ($count): " . htmlspecialchars($m) . "\n";
                file_put_contents($STATE, (string) ($count - 1));
                exit;
            }
        }
    }

    $done = $count;
    if ((microtime(true) - $start) > $BUDGET) {
        file_put_contents($STATE, (string) $count);
        echo "... $count ifade işlendi (bu turda $ran). Devam ediyor, sayfa yenileniyor.\n";
        echo "</pre><meta http-equiv='refresh' content='1; url=import-dict.php'>";
        gzclose($gz);
        exit;
    }
}

$finished = gzeof($gz);
gzclose($gz);

if ($finished) {
    @unlink($STATE);
    $n = 0;
    try { $n = $pdo->query("SELECT COUNT(*) c FROM translate")->fetch()['c'] ?? 0; } catch (Throwable $e) {}
    echo "\n✓✓✓ IMPORT TAMAMLANDI. translate satır sayısı: $n\n";
    echo ">>> ŞİMDİ import-dict.php VE didn-sozluk.sql.gz DOSYALARINI SİL! <<<\n";
} else {
    file_put_contents($STATE, (string) $count);
    echo "... $count ifade. Devam için yenile.\n</pre><meta http-equiv='refresh' content='1; url=import-dict.php'>";
}
