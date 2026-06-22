<?php declare(strict_types=1); $cu = current_user(); ?>
<h1 class="admin-h1">Yönetim Paneli</h1>
<p class="admin-sub">Hoş geldin, <strong><?= e($cu['email']) ?></strong>.</p>
<?php include __DIR__ . '/_flash.php'; ?>

<div class="admin-cards">
    <a class="admin-card card" href="/admin/icerik">
        <h2>İçerik Yönetimi</h2>
        <p>Haberler, blog yazıları, rehberler. Görüntüle, düzenle, yayınla.</p>
    </a>
    <?php foreach (CONTENT_TYPES as $ctype => $meta): $cfg = get_generator_config($ctype); ?>
        <a class="admin-card card" href="/admin/uretici/<?= e($ctype) ?>">
            <h2><?= e($meta['label']) ?> Üretici</h2>
            <p>Konu havuzundan yapay zekâ ile <?= e(mb_strtolower($meta['label'], 'UTF-8')) ?> üretir. Elle veya otomatik.
                <?= !empty($cfg['enabled']) ? '<span class="ok-text">• Otomatik açık</span>' : '' ?></p>
        </a>
    <?php endforeach; ?>
    <a class="admin-card card" href="/admin/ayarlar">
        <h2>Ayarlar</h2>
        <p>OpenAI API anahtarı ve yönetici şifresi. Anahtar yalnızca sunucuda.</p>
    </a>
</div>
