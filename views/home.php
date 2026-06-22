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
            <span class="wotd-word"><?= e($wotd['headword']) ?></span>
            <?php if (!empty($wotd['phonetic'])): ?>
                <span class="wotd-phonetic"><?= e($wotd['phonetic']) ?></span>
            <?php endif; ?>
            <?php if (!empty($wotd['audioUrl'])): ?>
                <audio class="wotd-audio" controls preload="none" src="<?= e($wotd['audioUrl']) ?>"></audio>
            <?php endif; ?>
        </div>

        <?php if (!empty($wotd['translations'])): ?>
            <div class="wotd-trans">
                <span class="wotd-en"><?= e($wotd['headword']) ?></span>
                <span class="wotd-eq">=</span>
                <span class="wotd-tr"><?= e(implode(', ', array_map(fn($t) => $t['word'], array_slice($wotd['translations'], 0, 5)))) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($wotd['meaning']['definition'])): ?>
            <div class="wotd-row"><span class="wotd-row-label">Tanım</span>
                <p class="wotd-def"><?= e($wotd['meaning']['definition']) ?>
                    <?php if (!empty($wotd['meaning']['example'])): ?>
                        <span class="wotd-ex">“<?= e($wotd['meaning']['example']) ?>”</span>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if (!empty($wotd['synonyms'])): ?>
            <div class="wotd-row"><span class="wotd-row-label">Eş anlamlı</span>
                <div class="wotd-chips">
                    <?php foreach ($wotd['synonyms'] as $s): ?>
                        <a class="chip" href="/en/<?= rawurlencode($s) ?>"><?= e($s) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($wotd['antonyms'])): ?>
            <div class="wotd-row"><span class="wotd-row-label">Zıt anlamlı</span>
                <div class="wotd-chips">
                    <?php foreach ($wotd['antonyms'] as $a): ?>
                        <a class="chip" href="/en/<?= rawurlencode($a) ?>"><?= e($a) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
