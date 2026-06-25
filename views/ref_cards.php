<?php
// Genel referans listesi (phrasal verbs, deyimler…): aranabilir kart listesi.
declare(strict_types=1);
/** @var array  $items   her biri: term, meaning, example */
/** @var string $heading */
/** @var string $lead */
/** @var string $intro */
/** @var string $ph      arama placeholder'ı */
/** @var array  $related [url, label] çiftleri */
?>
<a href="/gramer" class="back-link">← Gramer Konuları</a>

<article class="lesson" data-speak-lang="en-US">
    <header class="lesson-head card">
        <h1><?= e($heading) ?></h1>
        <p class="lesson-summary"><?= e($lead) ?></p>
        <p class="lesson-intro"><?= e($intro) ?></p>
    </header>

    <section class="lesson-section card">
        <input type="search" id="ref-filter" class="verb-filter" placeholder="<?= e($ph) ?>" autocomplete="off">
        <div class="ref-list" id="ref-list">
            <?php foreach ($items as $it): ?>
                <div class="ref-card">
                    <div class="ref-term">
                        <strong><?= e($it['term']) ?></strong>
                        <button type="button" class="speak" data-text="<?= e($it['term']) ?>" aria-label="Seslendir" title="Seslendir">🔊</button>
                        <span class="ref-meaning"><?= e($it['meaning']) ?></span>
                    </div>
                    <?php if (!empty($it['example'])): ?>
                        <p class="ref-example">“<?= e($it['example']) ?>”</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="table-caption" id="ref-empty" hidden>Eşleşen sonuç bulunamadı.</p>
    </section>

    <?php if (!empty($related)): ?>
        <section class="lesson-section card">
            <h2>İlgili sayfalar</h2>
            <div class="chips">
                <?php foreach ($related as $r): ?>
                    <a class="chip" href="<?= e($r[0]) ?>"><?= e($r[1]) ?></a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</article>

<script>
(function () {
    var input = document.getElementById('ref-filter');
    var cards = Array.prototype.slice.call(document.querySelectorAll('#ref-list .ref-card'));
    var empty = document.getElementById('ref-empty');
    if (input) {
        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase();
            var shown = 0;
            cards.forEach(function (c) {
                var match = c.textContent.toLowerCase().indexOf(q) !== -1;
                c.hidden = !match;
                if (match) { shown++; }
            });
            if (empty) { empty.hidden = shown !== 0; }
        });
    }
    var synth = window.speechSynthesis;
    var btns = document.querySelectorAll('.speak');
    if (!synth || typeof SpeechSynthesisUtterance === 'undefined') {
        btns.forEach(function (b) { b.hidden = true; });
        return;
    }
    btns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var u = new SpeechSynthesisUtterance(btn.dataset.text);
            u.lang = 'en-US';
            synth.cancel();
            synth.speak(u);
        });
    });
})();
</script>
