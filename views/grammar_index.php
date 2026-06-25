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
    <div class="ref-banner-grid">
        <a class="ref-banner card" href="/duzensiz-fiiller">
            <span class="ref-banner-icon">📋</span>
            <span><strong>Düzensiz Fiiller</strong><span class="ref-banner-sub">go-went-gone, V2/V3 ve Türkçe anlamları.</span></span>
        </a>
        <a class="ref-banner card" href="/phrasal-verbs">
            <span class="ref-banner-icon">🔗</span>
            <span><strong>Phrasal Verbs</strong><span class="ref-banner-sub">Öbek fiiller, anlam ve örnek cümleler.</span></span>
        </a>
        <a class="ref-banner card" href="/deyimler">
            <span class="ref-banner-icon">💬</span>
            <span><strong>Deyimler (Idioms)</strong><span class="ref-banner-sub">İngilizce deyimler ve Türkçe karşılıkları.</span></span>
        </a>
        <a class="ref-banner card" href="/ingilizce-kelimeler">
            <span class="ref-banner-icon">⭐</span>
            <span><strong>Sık Kullanılan Kelimeler</strong><span class="ref-banner-sub">Seviyeye göre temel İngilizce kelimeler.</span></span>
        </a>
    </div>
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
