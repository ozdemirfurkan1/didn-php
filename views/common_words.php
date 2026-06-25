<?php
// Seviyeye göre sık kullanılan kelimeler; her kelime sözlük sayfasına link.
declare(strict_types=1);
/** @var array $groups  her biri: level, title, words[] */
?>
<a href="/gramer" class="back-link">← Gramer Konuları</a>

<article class="lesson">
    <header class="lesson-head card">
        <h1>En Sık Kullanılan İngilizce Kelimeler</h1>
        <p class="lesson-summary">Seviyeye göre (A1 → B1) en sık kullanılan İngilizce kelimeler. Bir kelimeye tıkla; Türkçe anlamı, telaffuzu ve örnekleri sözlükte açılsın.</p>
        <p class="lesson-intro">İngilizceye başlarken en çok işine yarayacak kelimeler bunlardır. Aşağıdan arayıp doğrudan sözlük sayfasına geçebilirsin.</p>
    </header>

    <section class="lesson-section card">
        <input type="search" id="cw-filter" class="verb-filter" placeholder="Kelime ara (ör. water)…" autocomplete="off">
        <p class="table-caption" id="cw-empty" hidden>Eşleşen kelime bulunamadı.</p>
    </section>

    <?php foreach ($groups as $g): ?>
        <section class="lesson-section card cw-group">
            <h2 class="level-row"><?= e($g['title']) ?> <span class="level-badge level-<?= e($g['level']) ?>"><?= e($g['level']) ?></span></h2>
            <div class="chips cw-chips">
                <?php foreach ($g['words'] as $w): ?>
                    <a class="chip cw-word" href="/en/<?= rawurlencode($w) ?>"><?= e($w) ?></a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <section class="lesson-section card">
        <h2>İlgili sayfalar</h2>
        <div class="chips">
            <a class="chip" href="/duzensiz-fiiller">Düzensiz Fiiller</a>
            <a class="chip" href="/phrasal-verbs">Phrasal Verbs</a>
            <a class="chip" href="/deyimler">Deyimler</a>
            <a class="chip" href="/gramer">Gramer Konuları</a>
        </div>
    </section>
</article>

<script>
(function () {
    var input  = document.getElementById('cw-filter');
    var words  = Array.prototype.slice.call(document.querySelectorAll('.cw-word'));
    var groups = Array.prototype.slice.call(document.querySelectorAll('.cw-group'));
    var empty  = document.getElementById('cw-empty');
    if (!input) { return; }
    input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase();
        var total = 0;
        words.forEach(function (a) {
            var match = a.textContent.toLowerCase().indexOf(q) !== -1;
            a.hidden = !match;
            if (match) { total++; }
        });
        groups.forEach(function (sec) {
            var anyVisible = sec.querySelectorAll('.cw-word:not([hidden])').length > 0;
            sec.hidden = !anyVisible;
        });
        if (empty) { empty.hidden = total !== 0; }
    });
})();
</script>
