<?php
// Kullanıcının kaydettiği kelimeler ("Kelimelerim").
// saved_word tablosu ilk kullanımda kendiliğinden oluşur (manuel migration gerekmez).

declare(strict_types=1);

function ensure_saved_word_table(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    db()->exec(
        "CREATE TABLE IF NOT EXISTS saved_word (
            id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT NOT NULL,
            dir VARCHAR(8) NOT NULL,
            word VARCHAR(190) NOT NULL,
            headword VARCHAR(190) NOT NULL,
            summary VARCHAR(500) NULL,
            status VARCHAR(12) NOT NULL DEFAULT 'learning',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_word (user_id, dir, word),
            INDEX idx_user_created (user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    // Eski tabloda status kolonu yoksa ekle (migration).
    try {
        if (!db()->query("SHOW COLUMNS FROM saved_word LIKE 'status'")->fetch()) {
            db()->exec("ALTER TABLE saved_word ADD COLUMN status VARCHAR(12) NOT NULL DEFAULT 'learning'");
        }
    } catch (Throwable $e) {
        // yoksay
    }
    $done = true;
}

function normalize_word_status(string $s): string
{
    return $s === 'learned' ? 'learned' : 'learning';
}

function save_word(int $userId, string $dir, string $word, string $headword, string $summary = ''): void
{
    ensure_saved_word_table();
    $stmt = db()->prepare(
        "INSERT INTO saved_word (user_id, dir, word, headword, summary)
         VALUES (:u, :d, :w, :h, :s)
         ON DUPLICATE KEY UPDATE headword = VALUES(headword), summary = VALUES(summary)"
    );
    $stmt->execute([
        ':u' => $userId,
        ':d' => $dir,
        ':w' => mb_substr($word, 0, 190, 'UTF-8'),
        ':h' => mb_substr($headword !== '' ? $headword : $word, 0, 190, 'UTF-8'),
        ':s' => mb_substr($summary, 0, 500, 'UTF-8'),
    ]);
}

function unsave_word(int $userId, string $dir, string $word): void
{
    ensure_saved_word_table();
    $stmt = db()->prepare("DELETE FROM saved_word WHERE user_id = :u AND dir = :d AND word = :w");
    $stmt->execute([':u' => $userId, ':d' => $dir, ':w' => mb_substr($word, 0, 190, 'UTF-8')]);
}

function is_word_saved(int $userId, string $dir, string $word): bool
{
    ensure_saved_word_table();
    $stmt = db()->prepare("SELECT 1 FROM saved_word WHERE user_id = :u AND dir = :d AND word = :w LIMIT 1");
    $stmt->execute([':u' => $userId, ':d' => $dir, ':w' => mb_substr($word, 0, 190, 'UTF-8')]);
    return (bool) $stmt->fetchColumn();
}

function list_saved_words(int $userId): array
{
    ensure_saved_word_table();
    $stmt = db()->prepare("SELECT dir, word, headword, summary, status, created_at FROM saved_word WHERE user_id = :u ORDER BY created_at DESC");
    $stmt->execute([':u' => $userId]);
    return $stmt->fetchAll();
}

function count_saved_words(int $userId): int
{
    ensure_saved_word_table();
    $stmt = db()->prepare("SELECT COUNT(*) FROM saved_word WHERE user_id = :u");
    $stmt->execute([':u' => $userId]);
    return (int) $stmt->fetchColumn();
}

// Bir kelimenin öğrenme durumunu günceller (learning|learned).
function set_word_status(int $userId, string $dir, string $word, string $status): void
{
    ensure_saved_word_table();
    $stmt = db()->prepare("UPDATE saved_word SET status = :s WHERE user_id = :u AND dir = :d AND word = :w");
    $stmt->execute([
        ':s' => normalize_word_status($status),
        ':u' => $userId,
        ':d' => $dir,
        ':w' => mb_substr($word, 0, 190, 'UTF-8'),
    ]);
}

// Birden çok kelimeyi (["dir|word", ...]) tek seferde verilen duruma getirir.
function set_words_status_bulk(int $userId, array $keys, string $status): int
{
    ensure_saved_word_table();
    $status = normalize_word_status($status);
    $stmt   = db()->prepare("UPDATE saved_word SET status = :s WHERE user_id = :u AND dir = :d AND word = :w");
    $n = 0;
    foreach ($keys as $key) {
        $pos = strpos($key, '|');
        if ($pos === false) {
            continue;
        }
        $dir  = substr($key, 0, $pos) === 'tr-en' ? 'tr-en' : 'en-tr';
        $word = trim(substr($key, $pos + 1));
        if ($word === '') {
            continue;
        }
        $stmt->execute([':s' => $status, ':u' => $userId, ':d' => $dir, ':w' => mb_substr($word, 0, 190, 'UTF-8')]);
        $n += $stmt->rowCount();
    }
    return $n;
}

// ['total'=>, 'learned'=>] öğrenme ilerlemesi.
function saved_word_progress(int $userId): array
{
    ensure_saved_word_table();
    $stmt = db()->prepare("SELECT COUNT(*) total, SUM(status = 'learned') learned FROM saved_word WHERE user_id = :u");
    $stmt->execute([':u' => $userId]);
    $r = $stmt->fetch() ?: [];
    return ['total' => (int) ($r['total'] ?? 0), 'learned' => (int) ($r['learned'] ?? 0)];
}
