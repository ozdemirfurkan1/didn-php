<?php
// Tek gramer dersi (track'e göre).
declare(strict_types=1);
/** @var array $lesson */
/** @var array $t  Track tanımı (base, target, native, labels...) */
$lbl    = $t['labels'];
$base   = $t['base'];
$target = $t['target']; // örnek cümlede öğrenilen dil alanı
$native = $t['native']; // örnek cümlede çeviri dili alanı
?>
<a href="<?= e($base) ?>" class="back-link"><?= e($lbl['back']) ?></a>

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
            <p class="formula"><strong><?= e($lbl['structure']) ?></strong> <?= e($lesson['formula']) ?></p>
        <?php endif; ?>
    </header>

    <?php if (!empty($lesson['formTable'])): $ft = $lesson['formTable']; ?>
        <section class="lesson-section card">
            <h2><?= e($lbl['formTable']) ?></h2>
            <div class="table-wrap">
                <table class="form-table">
                    <?php if (!empty($ft['headers'])): ?>
                        <thead>
                            <tr><?php foreach ($ft['headers'] as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr>
                        </thead>
                    <?php endif; ?>
                    <tbody>
                        <?php foreach (($ft['rows'] ?? []) as $row): ?>
                            <tr>
                                <?php foreach ($row as $ci => $cell): ?>
                                    <?php if ($ci === 0): ?>
                                        <th scope="row"><?= e($cell) ?></th>
                                    <?php else: ?>
                                        <td><?= e($cell) ?></td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!empty($ft['caption'])): ?>
                <p class="table-caption"><?= e($ft['caption']) ?></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

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
                            <span class="ex-en"><?= e($ex[$target] ?? '') ?></span>
                            <span class="ex-tr"><?= e($ex[$native] ?? '') ?></span>
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
            <h2><?= e($lbl['mistakes']) ?></h2>
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

    <?php if (!empty($lesson['quiz'])): ?>
        <section class="lesson-section card quiz">
            <h2><?= e($lbl['quiz']) ?></h2>
            <?php foreach ($lesson['quiz'] as $q): ?>
                <div class="quiz-q" data-answer="<?= (int) ($q['answer'] ?? 0) ?>">
                    <p class="quiz-prompt"><?= e($q['q'] ?? '') ?></p>
                    <div class="quiz-options">
                        <?php foreach (($q['options'] ?? []) as $oi => $opt): ?>
                            <button type="button" class="quiz-opt" data-index="<?= (int) $oi ?>"><?= e($opt) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($q['explanation'])): ?>
                        <p class="quiz-explain" hidden><?= e($q['explanation']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>
        <script>
        document.querySelectorAll('.quiz-q').forEach(function (q) {
            var answer  = parseInt(q.dataset.answer, 10);
            var opts    = q.querySelectorAll('.quiz-opt');
            var explain = q.querySelector('.quiz-explain');
            opts.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (q.classList.contains('answered')) return;
                    q.classList.add('answered');
                    if (opts[answer]) opts[answer].classList.add('correct');
                    if (parseInt(btn.dataset.index, 10) !== answer) btn.classList.add('wrong');
                    if (explain) explain.hidden = false;
                });
            });
        });
        </script>
    <?php endif; ?>

    <?php
    $related = array_filter(array_map(fn($s) => get_lesson($s, $t['key']), $lesson['related'] ?? []));
    ?>
    <?php if ($related): ?>
        <section class="lesson-section card">
            <h2><?= e($lbl['related']) ?></h2>
            <div class="chips">
                <?php foreach ($related as $r): ?>
                    <a class="chip" href="<?= e($base) ?>/<?= e($r['slug']) ?>"><?= e($r['title']) ?></a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</article>
