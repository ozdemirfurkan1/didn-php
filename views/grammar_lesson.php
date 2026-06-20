<?php
// Tek gramer dersi.
declare(strict_types=1);
/** @var array $lesson */
?>
<a href="/gramer" class="back-link">← Gramer Konuları</a>

<article class="lesson">
    <header class="lesson-head card">
        <div class="lesson-head-top">
            <h1><?= e($lesson['title']) ?></h1>
            <span class="level-badge level-<?= e($lesson['level']) ?>"><?= e($lesson['level']) ?></span>
        </div>
        <p class="lesson-summary"><?= e($lesson['summary']) ?></p>
        <?php foreach (($lesson['intro'] ?? []) as $p): ?>
            <p class="lesson-intro"><?= e($p) ?></p>
        <?php endforeach; ?>
        <?php if (!empty($lesson['formula'])): ?>
            <p class="formula"><strong>Yapı:</strong> <?= e($lesson['formula']) ?></p>
        <?php endif; ?>
    </header>

    <?php foreach (($lesson['sections'] ?? []) as $section): ?>
        <section class="lesson-section card">
            <h2><?= e($section['heading']) ?></h2>
            <?php foreach (($section['body'] ?? []) as $p): ?>
                <p><?= e($p) ?></p>
            <?php endforeach; ?>
            <?php if (!empty($section['bullets'])): ?>
                <ul class="lesson-bullets">
                    <?php foreach ($section['bullets'] as $b): ?>
                        <li><?= e($b) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if (!empty($section['examples'])): ?>
                <ul class="examples">
                    <?php foreach ($section['examples'] as $ex): ?>
                        <li>
                            <span class="ex-en"><?= e($ex['en']) ?></span>
                            <span class="ex-tr"><?= e($ex['tr']) ?></span>
                            <?php if (!empty($ex['note'])): ?>
                                <span class="ex-note"><?= e($ex['note']) ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <?php if (!empty($lesson['mistakes'])): ?>
        <section class="lesson-section card">
            <h2>Sık yapılan hatalar</h2>
            <ul class="mistakes">
                <?php foreach ($lesson['mistakes'] as $m): ?>
                    <li>
                        <span class="wrong">✗ <?= e($m['wrong']) ?></span>
                        <span class="right">✓ <?= e($m['right']) ?></span>
                        <?php if (!empty($m['note'])): ?>
                            <span class="ex-note"><?= e($m['note']) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php
    $related = array_filter(array_map('get_lesson', $lesson['related'] ?? []));
    ?>
    <?php if ($related): ?>
        <section class="lesson-section card">
            <h2>İlgili konular</h2>
            <div class="chips">
                <?php foreach ($related as $r): ?>
                    <a class="chip" href="/gramer/<?= e($r['slug']) ?>"><?= e($r['title']) ?></a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</article>
