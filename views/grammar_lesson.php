<?php
// Tek gramer dersi (track'e göre).
declare(strict_types=1);
/** @var array $lesson */
/** @var array $t  Track tanımı (base, target, native, labels...) */
$lbl    = $t['labels'];
$base   = $t['base'];
$target = $t['target']; // örnek cümlede öğrenilen dil alanı
$native = $t['native']; // örnek cümlede çeviri dili alanı
$speakLang = $target === 'es' ? 'es-ES' : 'en-US'; // seslendirme dili

// Önceki / sonraki ders (sıralı listede konuma göre).
$allLessons = all_lessons($t['key']);
$curIdx = null;
foreach ($allLessons as $i => $l) {
    if (($l['slug'] ?? null) === $lesson['slug']) { $curIdx = $i; break; }
}
$prevLesson = ($curIdx !== null && $curIdx > 0) ? $allLessons[$curIdx - 1] : null;
$nextLesson = ($curIdx !== null && $curIdx < count($allLessons) - 1) ? $allLessons[$curIdx + 1] : null;
?>
<a href="<?= e($base) ?>" class="back-link"><?= e($lbl['back']) ?></a>

<article class="lesson" data-speak-lang="<?= e($speakLang) ?>">
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
                            <span class="ex-en">
                                <?= e($ex[$target] ?? '') ?>
                                <?php if (!empty($ex[$target])): ?>
                                    <button type="button" class="speak" data-text="<?= e($ex[$target]) ?>" aria-label="Seslendir" title="Seslendir">🔊</button>
                                <?php endif; ?>
                            </span>
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

    <?php if ($prevLesson || $nextLesson): ?>
        <nav class="lesson-nav">
            <?php if ($prevLesson): ?>
                <a class="lesson-nav-link prev" href="<?= e($base) ?>/<?= e($prevLesson['slug']) ?>">
                    <span class="lesson-nav-dir">← <?= e($lbl['prev']) ?></span>
                    <strong><?= e($prevLesson['title']) ?></strong>
                </a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
            <?php if ($nextLesson): ?>
                <a class="lesson-nav-link next" href="<?= e($base) ?>/<?= e($nextLesson['slug']) ?>">
                    <span class="lesson-nav-dir"><?= e($lbl['next']) ?> →</span>
                    <strong><?= e($nextLesson['title']) ?></strong>
                </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</article>

<script>
(function () {
    var nodes = document.querySelectorAll('.speak');
    var synth = window.speechSynthesis;
    if (!synth || typeof SpeechSynthesisUtterance === 'undefined') {
        nodes.forEach(function (b) { b.hidden = true; });
        return;
    }
    var lessonEl = document.querySelector('.lesson');
    var lang = (lessonEl && lessonEl.dataset.speakLang) || 'en-US';

    function pickVoice() {
        var voices = synth.getVoices() || [];
        var base = lang.slice(0, 2);
        return voices.filter(function (v) { return v.lang === lang; })[0]
            || voices.filter(function (v) { return (v.lang || '').slice(0, 2) === base; })[0]
            || null;
    }
    // Sesler bazı tarayıcılarda gecikmeli yüklenir; erkenden tetikle.
    synth.getVoices();
    if ('onvoiceschanged' in synth) { synth.onvoiceschanged = function () {}; }

    nodes.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var u = new SpeechSynthesisUtterance(btn.dataset.text);
            u.lang = lang;
            var v = pickVoice();
            if (v) { u.voice = v; }
            synth.cancel();
            synth.speak(u);
        });
    });
})();
</script>
