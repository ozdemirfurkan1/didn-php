<?php declare(strict_types=1); /** @var array|null $wotd */ ?>
<section class="hero">
    <h1>İngilizce ↔ Türkçe Sözlük</h1>
    <p class="hero-sub">Bir kelime ara; anlamı, çevirileri, telaffuzu ve örnek cümleleriyle.</p>
    <?php $curQ = ''; $curDir = 'en-tr'; include __DIR__ . '/_search.php'; ?>

    <?php if (!empty($wotd)): ?>
        <a class="wotd card" href="/en/<?= rawurlencode($wotd['query']) ?>">
            <div class="wotd-label">Günün Kelimesi</div>
            <div class="wotd-head">
                <span class="wotd-word"><?= e($wotd['headword']) ?></span>
                <?php if (!empty($wotd['phonetic'])): ?>
                    <span class="wotd-phonetic"><?= e($wotd['phonetic']) ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($wotd['translations'])): ?>
                <div class="wotd-trans">
                    <?= e(implode(', ', array_map(fn($t) => $t['word'], array_slice($wotd['translations'], 0, 4)))) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($wotd['meaning']['definition'])): ?>
                <p class="wotd-def"><?= e($wotd['meaning']['definition']) ?></p>
            <?php endif; ?>
            <span class="wotd-more">Kelimeyi incele →</span>
        </a>
    <?php endif; ?>
</section>
