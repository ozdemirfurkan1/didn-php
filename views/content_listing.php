<?php
declare(strict_types=1);
/** @var string $type */
/** @var array $items */
$info  = CONTENT_TYPES[$type];
$route = $info['route'];
$titles = ['blog' => 'Blog', 'news' => 'Haberler', 'guide' => 'Rehberler'];
$descs  = [
    'blog'  => 'İngilizce öğrenme, kelime ve dil bilgisi üzerine yazılar.',
    'news'  => 'Güncel haberler ve duyurular.',
    'guide' => 'Adım adım İngilizce öğrenme rehberleri.',
];
?>
<header class="page-head">
    <h1><?= e($titles[$type]) ?></h1>
    <p class="page-sub"><?= e($descs[$type]) ?></p>
</header>

<?php if (!$items): ?>
    <p class="empty-box">Henüz yayınlanmış içerik yok.</p>
<?php else: ?>
    <div class="listing-grid">
        <?php foreach ($items as $it): ?>
            <a class="listing-card card" href="/<?= $route ?>/<?= rawurlencode($it['slug']) ?>">
                <h2><?= e($it['title']) ?></h2>
                <?php if (!empty($it['summary'])): ?>
                    <p class="listing-summary"><?= e($it['summary']) ?></p>
                <?php endif; ?>
                <p class="listing-date"><?= e(date('d F Y', strtotime($it['published_at'] ?: $it['created_at']))) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
