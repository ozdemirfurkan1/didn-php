<?php
// İçerik (haber/blog/rehber) veri katmanı — content tablosu.

declare(strict_types=1);

const CONTENT_TYPES = [
    'news'  => ['label' => 'Haber',  'plural' => 'Haberler',      'route' => 'haber'],
    'blog'  => ['label' => 'Blog',   'plural' => 'Blog Yazıları', 'route' => 'blog'],
    'guide' => ['label' => 'Rehber', 'plural' => 'Rehberler',     'route' => 'rehber'],
];

const CONTENT_STATUS_LABELS = [
    'draft'     => 'Taslak',
    'published' => 'Yayında',
    'hidden'    => 'Gizli',
];

function is_content_type(string $t): bool
{
    return isset(CONTENT_TYPES[$t]);
}

function is_content_status(string $s): bool
{
    return isset(CONTENT_STATUS_LABELS[$s]);
}

function content_route(string $type): string
{
    return CONTENT_TYPES[$type]['route'] ?? $type;
}

// Tipe göre tüm içerikler (admin), en yeni önce.
function list_content(string $type): array
{
    $stmt = db()->prepare("SELECT * FROM content WHERE type = :t ORDER BY created_at DESC");
    $stmt->execute([':t' => $type]);
    return $stmt->fetchAll();
}

function count_content(string $type): int
{
    $stmt = db()->prepare("SELECT COUNT(*) AS c FROM content WHERE type = :t");
    $stmt->execute([':t' => $type]);
    return (int) ($stmt->fetch()['c'] ?? 0);
}

function get_content(string $id): ?array
{
    if (!ctype_digit($id)) {
        return null;
    }
    $stmt = db()->prepare("SELECT * FROM content WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

// Yayın listesi (public): yalnızca yayında olanlar.
function list_published(string $type): array
{
    $stmt = db()->prepare(
        "SELECT * FROM content WHERE type = :t AND status = 'published'
         ORDER BY COALESCE(published_at, created_at) DESC"
    );
    $stmt->execute([':t' => $type]);
    return $stmt->fetchAll();
}

function get_published_by_slug(string $type, string $slug): ?array
{
    $stmt = db()->prepare(
        "SELECT * FROM content WHERE type = :t AND slug = :s AND status = 'published' LIMIT 1"
    );
    $stmt->execute([':t' => $type, ':s' => $slug]);
    return $stmt->fetch() ?: null;
}

function get_by_slug_any(string $type, string $slug): ?array
{
    $stmt = db()->prepare("SELECT * FROM content WHERE type = :t AND slug = :s LIMIT 1");
    $stmt->execute([':t' => $type, ':s' => $slug]);
    return $stmt->fetch() ?: null;
}

// Benzersiz slug üretir (çakışırsa -2, -3 ...). $excludeId düzenlemede kendini saymaz.
function ensure_unique_slug(string $base, ?string $excludeId = null): string
{
    $safe = $base !== '' ? $base : 'icerik';
    $candidate = $safe;
    $n = 1;
    for ($i = 0; $i < 1000; $i++) {
        $stmt = db()->prepare("SELECT id FROM content WHERE slug = :s LIMIT 1");
        $stmt->execute([':s' => $candidate]);
        $row = $stmt->fetch();
        if (!$row || ($excludeId !== null && (string) $row['id'] === $excludeId)) {
            return $candidate;
        }
        $n++;
        $candidate = $safe . '-' . $n;
    }
    return $safe . '-' . time();
}

// Yeni içerik oluşturur, id döner.
function create_content(array $data): string
{
    $status = is_content_status($data['status'] ?? '') ? $data['status'] : 'draft';
    $slug   = ensure_unique_slug(slugify($data['slug'] ?: $data['title']));
    $stmt = db()->prepare(
        "INSERT INTO content (type, slug, title, summary, body, cover_image, status, source, topic, published_at)
         VALUES (:type, :slug, :title, :summary, :body, :cover, :status, :source, :topic, :published_at)"
    );
    $stmt->execute([
        ':type'    => $data['type'],
        ':slug'    => $slug,
        ':title'   => $data['title'],
        ':summary' => $data['summary'] ?: null,
        ':body'    => $data['body'] ?? '',
        ':cover'   => $data['coverImage'] ?: null,
        ':status'  => $status,
        ':source'  => $data['source'] ?? 'manual',
        ':topic'   => $data['topic'] ?: null,
        ':published_at' => $status === 'published' ? date('Y-m-d H:i:s') : null,
    ]);
    return (string) db()->lastInsertId();
}

// Var olan içeriği günceller.
function update_content(string $id, array $data, array $existing): void
{
    $fields = [];
    $params = [':id' => $id];

    if (array_key_exists('title', $data)) {
        $fields[] = 'title = :title';
        $params[':title'] = $data['title'];
    }
    if (array_key_exists('slug', $data)) {
        $base = slugify($data['slug'] !== '' ? $data['slug'] : ($data['title'] ?? $existing['title']));
        $fields[] = 'slug = :slug';
        $params[':slug'] = ensure_unique_slug($base, $id);
    }
    if (array_key_exists('summary', $data)) {
        $fields[] = 'summary = :summary';
        $params[':summary'] = $data['summary'] ?: null;
    }
    if (array_key_exists('body', $data)) {
        $fields[] = 'body = :body';
        $params[':body'] = $data['body'];
    }
    if (array_key_exists('coverImage', $data)) {
        $fields[] = 'cover_image = :cover';
        $params[':cover'] = $data['coverImage'] ?: null;
    }
    if (array_key_exists('topic', $data)) {
        $fields[] = 'topic = :topic';
        $params[':topic'] = $data['topic'] ?: null;
    }
    if (array_key_exists('status', $data) && is_content_status($data['status'])) {
        $fields[] = 'status = :status';
        $params[':status'] = $data['status'];
        if ($data['status'] === 'published' && empty($existing['published_at'])) {
            $fields[] = 'published_at = :pub';
            $params[':pub'] = date('Y-m-d H:i:s');
        }
    }

    if (!$fields) {
        return;
    }
    $sql = "UPDATE content SET " . implode(', ', $fields) . " WHERE id = :id";
    db()->prepare($sql)->execute($params);
}

function delete_content(string $id): void
{
    db()->prepare("DELETE FROM content WHERE id = :id")->execute([':id' => $id]);
}
