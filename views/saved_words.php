<?php
// Kullanıcının kaydettiği kelimeler — kart listesi.
declare(strict_types=1);
/** @var array $words */
?>
<header class="page-head">
    <h1>Kelimelerim</h1>
    <p class="page-sub"><?= count($words) ?> kayıtlı kelime. Tekrar etmek için tıkla, sözlükte aç.</p>
</header>

<?php if (!$words): ?>
    <div class="card empty-state">
        <h2>Henüz kelime eklemedin</h2>
        <p>Sözlükte bir kelime aratıp “<strong>+ Kelimelerime ekle</strong>” butonuna basınca burada görünür.</p>
        <p><a class="chip" href="/">Sözlüğe git</a></p>
    </div>
<?php else: ?>
    <div class="saved-grid">
        <?php foreach ($words as $w): ?>
            <?php $url = ($w['dir'] === 'en-tr' ? '/en/' : '/tr/') . rawurlencode($w['word']); ?>
            <div class="saved-card card">
                <a class="saved-word" href="<?= e($url) ?>"><?= e($w['headword']) ?></a>
                <?php if (!empty($w['summary'])): ?>
                    <p class="saved-sum"><?= e($w['summary']) ?></p>
                <?php endif; ?>
                <form method="post" action="/kelime-sil" class="saved-remove">
                    <?= csrf_field() ?>
                    <input type="hidden" name="word" value="<?= e($w['word']) ?>">
                    <input type="hidden" name="dir" value="<?= e($w['dir']) ?>">
                    <input type="hidden" name="back" value="/kelimelerim">
                    <button type="submit" class="btn-remove" aria-label="Kaldır" title="Kaldır">✕</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
