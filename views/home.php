<?php declare(strict_types=1); /** @var array|null $wotd */ ?>
<section class="hero">
    <h1>İngilizce ↔ Türkçe Sözlük</h1>
    <p class="hero-sub">Bir kelime ara; anlamı, çevirileri, telaffuzu ve örnek cümleleriyle.</p>
    <?php $curQ = ''; $curDir = 'en-tr'; include __DIR__ . '/_search.php'; ?>
</section>

<?php if (!empty($wotd)): ?>
    <section class="wotd card">
        <div class="wotd-label">Günün Kelimesi</div>
        <div class="wotd-head">
            <a class="wotd-word" href="/en/<?= rawurlencode($wotd['query']) ?>"><?= e($wotd['headword']) ?></a>
            <?php if (!empty($wotd['phonetic'])): ?>
                <span class="wotd-phonetic"><?= e($wotd['phonetic']) ?></span>
            <?php endif; ?>
            <?php if (!empty($wotd['audioUrl'])): ?>
                <audio controls preload="none" src="<?= e($wotd['audioUrl']) ?>"></audio>
            <?php endif; ?>
        </div>
        <?php if (!empty($wotd['translations'])): ?>
            <div class="wotd-trans"><?= e(implode(', ', array_map(fn($t) => $t['word'], array_slice($wotd['translations'], 0, 5)))) ?></div>
        <?php endif; ?>
        <?php if (!empty($wotd['meaning']['definition'])): ?>
            <p class="wotd-def"><?= e($wotd['meaning']['definition']) ?></p>
        <?php endif; ?>
        <?php if (!empty($wotd['meaning']['example'])): ?>
            <p class="wotd-ex">“<?= e($wotd['meaning']['example']) ?>”</p>
        <?php endif; ?>
    </section>
<?php endif; ?>
