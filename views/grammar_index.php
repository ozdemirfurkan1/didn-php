<?php
// Gramer konuları listesi.
declare(strict_types=1);
/** @var array $groups */
?>
<header class="page-head">
    <h1>Gramer Konuları</h1>
    <p class="page-sub">İngilizce gramer konuları — sade Türkçe anlatım, kurallar, örnek cümleler ve sık yapılan hatalarla.</p>
</header>

<?php foreach ($groups as $category => $lessons): ?>
    <section class="grammar-cat">
        <h2 class="cat-title"><span class="cat-bar"></span><?= e($category) ?></h2>
        <div class="grammar-grid">
            <?php foreach ($lessons as $lesson): ?>
                <a class="grammar-card card" href="/gramer/<?= e($lesson['slug']) ?>">
                    <div class="grammar-card-top">
                        <h3><?= e($lesson['title']) ?></h3>
                        <span class="level-badge level-<?= e($lesson['level']) ?>"><?= e($lesson['level']) ?></span>
                    </div>
                    <p><?= e($lesson['summary']) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>
