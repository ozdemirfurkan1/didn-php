<?php declare(strict_types=1); $cu = current_user(); ?>
<h1 class="admin-h1">Yönetim Paneli</h1>
<p class="admin-sub">Hoş geldin, <strong><?= e($cu['email']) ?></strong>.</p>
<?php include __DIR__ . '/_flash.php'; ?>

<div class="admin-cards">
    <a class="admin-card card" href="/admin/icerik">
        <h2>İçerik Yönetimi</h2>
        <p>Haberler, blog yazıları, rehberler. Görüntüle, düzenle, yayınla.</p>
    </a>
    <a class="admin-card card" href="/admin/blog-uretici">
        <h2>Blog Üretici</h2>
        <p>Konu havuzundan yapay zekâ ile blog üretir. Elle veya otomatik.</p>
    </a>
    <a class="admin-card card" href="/admin/ayarlar">
        <h2>Ayarlar</h2>
        <p>OpenAI API anahtarı ve yönetici şifresi. Anahtar yalnızca sunucuda.</p>
    </a>
</div>
