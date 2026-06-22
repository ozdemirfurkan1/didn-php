<?php
// Gramer konuları listesi (track'e göre).
declare(strict_types=1);
/** @var array $groups */
/** @var array $t  Track tanımı (base, labels...) */
$lbl  = $t['labels'];
$base = $t['base'];
?>
<header class="page-head">
    <h1><?= e($lbl['indexTitle']) ?></h1>
    <p class="page-sub"><?= e($lbl['indexSub']) ?></p>
</header>

<?php foreach ($groups as $category => $lessons): ?>
    <section class="grammar-cat">
        <h2 class="cat-title"><span class="cat-bar"></span><?= e($category) ?></h2>
        <div class="grammar-grid">
            <?php foreach ($lessons as $lesson): ?>
                <a class="grammar-card card" href="<?= e($base) ?>/<?= e($lesson['slug']) ?>">
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
