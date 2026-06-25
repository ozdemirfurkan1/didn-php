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

<?php if (($t['key'] ?? '') === 'en'): ?>
    <a class="ref-banner card" href="/duzensiz-fiiller">
        <span class="ref-banner-icon">📋</span>
        <span>
            <strong>İngilizce Düzensiz Fiiller Listesi</strong>
            <span class="ref-banner-sub">go-went-gone gibi tüm düzensiz fiiller, V2/V3 ve Türkçe anlamlarıyla — arayarak bul.</span>
        </span>
    </a>
<?php endif; ?>

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
