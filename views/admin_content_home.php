<?php declare(strict_types=1); ?>
<a href="/admin" class="back-link">← Yönetim Paneli</a>
<h1 class="admin-h1">İçerik Yönetimi</h1>
<p class="admin-sub">Bir bölüme girip içerikleri görüntüle, düzenle veya yayından kaldır.</p>
<?php include __DIR__ . '/_flash.php'; ?>

<div class="admin-cards">
    <?php foreach (['news', 'blog', 'guide'] as $t): ?>
        <a class="admin-card card" href="/admin/icerik/<?= $t ?>">
            <div class="admin-card-top">
                <h2><?= e(CONTENT_TYPES[$t]['plural']) ?></h2>
                <span class="count-badge"><?= count_content($t) ?></span>
            </div>
            <p>Görüntüle, düzenle, yayınla.</p>
        </a>
    <?php endforeach; ?>
    <a class="admin-card card" href="/" >
        <h2>Sözlük ↗</h2>
        <p>DB'den gelir — yalnızca görüntüle.</p>
    </a>
    <a class="admin-card card" href="/gramer">
        <h2>Gramer ↗</h2>
        <p>Sabit dersler — yalnızca görüntüle.</p>
    </a>
</div>
