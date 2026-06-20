<?php
declare(strict_types=1);
/** @var string $type */
/** @var array $items */
$info  = CONTENT_TYPES[$type];
$route = $info['route'];
?>
<a href="/admin/icerik" class="back-link">← İçerik Yönetimi</a>
<div class="admin-list-head">
    <h1 class="admin-h1"><?= e($info['plural']) ?></h1>
    <a class="btn-primary" href="/admin/icerik/<?= $type ?>/yeni">+ Yeni <?= e($info['label']) ?></a>
</div>
<?php include __DIR__ . '/_flash.php'; ?>

<?php if (!$items): ?>
    <p class="empty-box">Henüz içerik yok. Sağ üstten ekleyebilirsin.</p>
<?php else: ?>
    <div class="content-rows">
        <?php foreach ($items as $it): ?>
            <div class="content-row card">
                <div class="content-row-main">
                    <div class="content-row-title">
                        <span class="status-badge status-<?= e($it['status']) ?>"><?= e(CONTENT_STATUS_LABELS[$it['status']] ?? $it['status']) ?></span>
                        <strong><?= e($it['title']) ?></strong>
                    </div>
                    <p class="content-row-meta">/<?= e($route) ?>/<?= e($it['slug']) ?>
                        · <?= e(date('d.m.Y', strtotime($it['created_at']))) ?>
                        <?= $it['source'] === 'ai' ? ' · AI' : '' ?></p>
                </div>
                <div class="content-row-actions">
                    <a href="/<?= $route ?>/<?= rawurlencode($it['slug']) ?>" target="_blank">Görüntüle</a>
                    <a href="/admin/icerik/<?= $type ?>/<?= $it['id'] ?>">Düzenle</a>
                    <form method="post" action="/admin/icerik/<?= $type ?>/<?= $it['id'] ?>/durum" class="inline-form">
                        <?= csrf_field() ?>
                        <?php if ($it['status'] === 'published'): ?>
                            <input type="hidden" name="status" value="hidden">
                            <button type="submit" class="link-btn">Yayından kaldır</button>
                        <?php else: ?>
                            <input type="hidden" name="status" value="published">
                            <button type="submit" class="link-btn link-ok">Yayınla</button>
                        <?php endif; ?>
                    </form>
                    <form method="post" action="/admin/icerik/<?= $type ?>/<?= $it['id'] ?>/sil" class="inline-form"
                          onsubmit="return confirm('Bu içerik silinsin mi? Geri alınamaz.');">
                        <?= csrf_field() ?>
                        <button type="submit" class="link-btn link-danger">Sil</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
