<?php
// Kullanıcının kaydettiği kelimeler — kart listesi + ilerleme + çalışma.
declare(strict_types=1);
/** @var array $words */
$total   = count($words);
$learned = 0;
foreach ($words as $w) {
    if (($w['status'] ?? 'learning') === 'learned') { $learned++; }
}
$pct = $total > 0 ? (int) round($learned / $total * 100) : 0;
?>
<header class="page-head">
    <h1>Kelimelerim</h1>
    <p class="page-sub"><?= $total ?> kayıtlı kelime. Tekrar etmek için tıkla, sözlükte aç.</p>
</header>

<?php if (!$words): ?>
    <div class="card empty-state">
        <h2>Henüz kelime eklemedin</h2>
        <p>Sözlükte bir kelime aratıp “<strong>+ Kelimelerime ekle</strong>” butonuna basınca burada görünür.</p>
        <p><a class="chip" href="/">Sözlüğe git</a></p>
    </div>
<?php else: ?>
    <section class="card progress-card">
        <div class="progress-top">
            <span><strong><?= $learned ?></strong> / <?= $total ?> öğrenildi (%<?= $pct ?>)</span>
            <a class="btn-primary" href="/kelimelerim/calis">▶ Çalış (flashcard)</a>
        </div>
        <div class="progress-bar"><span style="width: <?= $pct ?>%"></span></div>
    </section>

    <div class="saved-grid">
        <?php foreach ($words as $w): ?>
            <?php
            $url      = ($w['dir'] === 'en-tr' ? '/en/' : '/tr/') . rawurlencode($w['word']);
            $isLearned = ($w['status'] ?? 'learning') === 'learned';
            ?>
            <div class="saved-card card<?= $isLearned ? ' is-learned' : '' ?>">
                <a class="saved-word" href="<?= e($url) ?>"><?= e($w['headword']) ?></a>
                <?php if (!empty($w['summary'])): ?>
                    <p class="saved-sum"><?= e($w['summary']) ?></p>
                <?php endif; ?>
                <div class="saved-foot">
                    <form method="post" action="/kelime-durum" class="inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="word" value="<?= e($w['word']) ?>">
                        <input type="hidden" name="dir" value="<?= e($w['dir']) ?>">
                        <input type="hidden" name="status" value="<?= $isLearned ? 'learning' : 'learned' ?>">
                        <button type="submit" class="status-toggle <?= $isLearned ? 'st-learned' : 'st-learning' ?>">
                            <?= $isLearned ? '✓ Öğrendim' : 'Öğreniyorum' ?>
                        </button>
                    </form>
                    <form method="post" action="/kelime-sil" class="saved-remove">
                        <?= csrf_field() ?>
                        <input type="hidden" name="word" value="<?= e($w['word']) ?>">
                        <input type="hidden" name="dir" value="<?= e($w['dir']) ?>">
                        <input type="hidden" name="back" value="/kelimelerim">
                        <button type="submit" class="btn-remove" aria-label="Kaldır" title="Listeden kaldır">✕</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
