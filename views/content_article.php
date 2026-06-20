<?php
declare(strict_types=1);
/** @var array $item */
/** @var bool $preview */
$info = CONTENT_TYPES[$item['type']];
?>
<article class="article">
    <?php if (!empty($preview)): ?>
        <p class="preview-note">Önizleme — bu içerik henüz yayında değil (durum: <strong><?= e($item['status']) ?></strong>).</p>
    <?php endif; ?>

    <p class="article-type"><?= e($info['label']) ?></p>
    <h1 class="article-title"><?= e($item['title']) ?></h1>
    <p class="article-date"><?= e(date('d F Y', strtotime($item['published_at'] ?: $item['created_at']))) ?></p>

    <?php if (!empty($item['cover_image'])): ?>
        <img class="article-cover" src="<?= e($item['cover_image']) ?>" alt="<?= e($item['title']) ?>">
    <?php endif; ?>

    <?php if (!empty($item['summary'])): ?>
        <p class="article-summary"><?= e($item['summary']) ?></p>
    <?php endif; ?>

    <div class="article-body"><?= render_markdown($item['body']) ?></div>
</article>
