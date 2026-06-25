<?php
// Kelime sonuç sayfası.
declare(strict_types=1);
/** @var array $result */
/** @var string $word */
/** @var string $dir */
?>
<?php $curQ = $word; $curDir = $dir; include __DIR__ . '/_search.php'; ?>

<?php if (is_lookup_error($result)): ?>
    <div class="card empty-state">
        <h2><?= e($result['error']) ?></h2>
        <p>“<?= e($result['query']) ?>” için bir sonuç bulunamadı. Yazımı kontrol edip tekrar deneyin.</p>
    </div>
<?php else: ?>
    <article class="result">
        <header class="word-header card">
            <?php if ($dir === 'tr-en'): ?>
                <p class="searched">Aranan: <strong><?= e($result['query']) ?></strong></p>
            <?php endif; ?>
            <div class="word-title-row">
                <h1 class="headword"><?= e($result['headword']) ?></h1>
                <?php if (!empty($result['phonetic'])): ?>
                    <span class="phonetic"><?= e($result['phonetic']) ?></span>
                <?php endif; ?>
                <?php if (!empty($result['audioUrl'])): ?>
                    <audio controls preload="none" src="<?= e($result['audioUrl']) ?>"></audio>
                <?php endif; ?>
            </div>
        </header>

        <?php
        $groups     = group_translations_by_type($result['translations']);
        $hasDetails = !empty($result['meanings']) || !empty($result['synonyms']) || !empty($result['antonyms']);

        // İç linkleme: İngilizce kelimenin türlerinden ilgili gramer derslerine köprü.
        $gramLinks = [];
        if ($dir === 'en-tr') {
            $posSet = [];
            foreach ($result['translations'] as $it) {
                if (!empty($it['type'])) { $posSet[] = $it['type']; }
            }
            foreach (($result['meanings'] ?? []) as $m) {
                if (!empty($m['pos'])) { $posSet[] = $m['pos']; }
            }
            foreach (array_unique($posSet) as $p) {
                if ($gl = pos_grammar_lesson($p)) {
                    $gramLinks[$gl['slug']] = $gl['title'];
                }
            }
        }
        ?>
        <div class="result-grid <?= $hasDetails ? '' : 'single' ?>">
            <?php if ($groups): ?>
                <section class="translations card">
                    <h2 class="section-title">Çeviriler</h2>
                    <?php foreach ($groups as $label => $items): ?>
                        <div class="trans-group">
                            <h3 class="pos-label"><?= e($label) ?></h3>
                            <ul class="trans-list">
                                <?php foreach ($items as $it): ?>
                                    <li>
                                        <?php $tDir = $dir === 'en-tr' ? '/tr/' : '/en/'; ?>
                                        <a href="<?= $tDir . rawurlencode($it['word']) ?>"><?= e($it['word']) ?></a>
                                        <?php if (!empty($it['category']) && strtolower($it['category']) !== 'common usage'): ?>
                                            <span class="cat-tag"><?= e($it['category']) ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <?php if ($hasDetails): ?>
                <div class="result-side">
                    <?php if (!empty($result['meanings'])): ?>
                        <section class="meanings card">
                            <h2 class="section-title">Tanımlar (İngilizce)</h2>
                            <ol class="meaning-list">
                                <?php foreach ($result['meanings'] as $m): ?>
                                    <li>
                                        <?php if (!empty($m['pos'])): ?>
                                            <span class="pos-chip"><?= e(translate_pos($m['pos'])) ?></span>
                                        <?php endif; ?>
                                        <span class="definition"><?= e($m['definition']) ?></span>
                                        <?php if (!empty($m['example'])): ?>
                                            <p class="example">“<?= e($m['example']) ?>”</p>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($result['synonyms']) || !empty($result['antonyms'])): ?>
                        <section class="relations card">
                            <?php if (!empty($result['synonyms'])): ?>
                                <div class="rel-block">
                                    <h3>Eş anlamlılar</h3>
                                    <div class="chips">
                                        <?php foreach ($result['synonyms'] as $s): ?>
                                            <a class="chip" href="/en/<?= rawurlencode($s) ?>"><?= e($s) ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($result['antonyms'])): ?>
                                <div class="rel-block">
                                    <h3>Zıt anlamlılar</h3>
                                    <div class="chips">
                                        <?php foreach ($result['antonyms'] as $a): ?>
                                            <a class="chip" href="/en/<?= rawurlencode($a) ?>"><?= e($a) ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($gramLinks): ?>
            <section class="card grammar-hint">
                <h2 class="section-title">İlgili gramer konuları</h2>
                <p class="grammar-hint-desc">Bu kelimenin türüne göre konu anlatımları:</p>
                <div class="chips">
                    <?php foreach ($gramLinks as $slug => $gtitle): ?>
                        <a class="chip" href="/gramer/<?= e($slug) ?>"><?= e($gtitle) ?></a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </article>
<?php endif; ?>
