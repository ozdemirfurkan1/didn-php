<?php
// Kullanıcı geri bildirimleri (öneri / şikayet / destek). Veri MySQL'de tutulur,
// admin panelinden okunur. Tablo ilk kullanımda otomatik oluşur (kurulum adımı yok).

declare(strict_types=1);

const FEEDBACK_TYPES = [
    'oneri'   => 'Öneri',
    'sikayet' => 'Şikayet',
    'destek'  => 'Destek',
    'diger'   => 'Diğer',
];

// Tabloyu (yoksa) oluşturur. İstek başına en fazla bir kez çalışır.
function feedback_table(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    db()->exec("CREATE TABLE IF NOT EXISTS feedback (
        id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(20) NOT NULL DEFAULT 'oneri',
        name VARCHAR(190) NULL,
        email VARCHAR(255) NULL,
        message TEXT NOT NULL,
        user_id BIGINT NULL,
        ip VARCHAR(45) NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created (created_at),
        INDEX idx_read (is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $done = true;
}

function save_feedback(string $type, string $name, string $email, string $message, ?int $userId): bool
{
    feedback_table();
    if (!isset(FEEDBACK_TYPES[$type])) {
        $type = 'diger';
    }
    $stmt = db()->prepare(
        "INSERT INTO feedback (type, name, email, message, user_id, ip)
         VALUES (:t, :n, :e, :m, :u, :ip)"
    );
    return $stmt->execute([
        ':t'  => $type,
        ':n'  => $name !== '' ? mb_substr($name, 0, 190) : null,
        ':e'  => $email !== '' ? mb_substr($email, 0, 255) : null,
        ':m'  => mb_substr($message, 0, 4000),
        ':u'  => $userId,
        ':ip' => mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
    ]);
}

function recent_feedback(int $limit = 200): array
{
    feedback_table();
    $limit = max(1, min(500, $limit));
    return db()->query("SELECT * FROM feedback ORDER BY created_at DESC LIMIT $limit")->fetchAll();
}

function feedback_unread_count(): int
{
    feedback_table();
    return (int) db()->query("SELECT COUNT(*) FROM feedback WHERE is_read = 0")->fetchColumn();
}

function mark_all_feedback_read(): void
{
    feedback_table();
    db()->exec("UPDATE feedback SET is_read = 1 WHERE is_read = 0");
}

function delete_feedback(int $id): void
{
    feedback_table();
    $stmt = db()->prepare("DELETE FROM feedback WHERE id = :id");
    $stmt->execute([':id' => $id]);
}
