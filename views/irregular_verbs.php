<?php
// İngilizce düzensiz fiiller referans sayfası (arama filtreli tablo).
declare(strict_types=1);
/** @var array $verbs */
?>
<a href="/gramer" class="back-link">← Gramer Konuları</a>

<article class="lesson" data-speak-lang="en-US">
    <header class="lesson-head card">
        <h1>İngilizce Düzensiz Fiiller</h1>
        <p class="lesson-summary">En sık kullanılan <?= count($verbs) ?> İngilizce düzensiz fiil: yalın hâl (V1), ikinci hâl / Past (V2) ve üçüncü hâl / Past Participle (V3) ile Türkçe anlamları.</p>
        <p class="lesson-intro">Düzensiz fiiller, geçmiş zaman ve perfect zamanlarda <code>-ed</code> kuralına uymaz; her birini ezberlemek gerekir. Aşağıdan arayarak hızlıca bulabilirsin.</p>
    </header>

    <section class="lesson-section card">
        <input type="search" id="verb-filter" class="verb-filter" placeholder="Fiil ara (ör. go, break, gitmek)…" autocomplete="off">
        <div class="table-wrap">
            <table class="form-table verb-table" id="verb-table">
                <thead>
                    <tr>
                        <th>Yalın (V1)</th>
                        <th>Past (V2)</th>
                        <th>Participle (V3)</th>
                        <th>Türkçe</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($verbs as $v): ?>
                        <tr>
                            <td>
                                <strong><?= e($v['base']) ?></strong>
                                <button type="button" class="speak" data-text="<?= e($v['base']) ?>" aria-label="Seslendir" title="Seslendir">🔊</button>
                            </td>
                            <td><?= e($v['past']) ?></td>
                            <td><?= e($v['pp']) ?></td>
                            <td class="verb-tr"><?= e($v['tr']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="table-caption" id="verb-empty" hidden>Eşleşen fiil bulunamadı.</p>
    </section>

    <section class="lesson-section card">
        <h2>İlgili konular</h2>
        <div class="chips">
            <a class="chip" href="/gramer/past-simple">Past Simple</a>
            <a class="chip" href="/gramer/present-perfect">Present Perfect</a>
            <a class="chip" href="/gramer/passive-voice">Passive Voice</a>
        </div>
    </section>
</article>

<script>
(function () {
    var input = document.getElementById('verb-filter');
    var rows  = Array.prototype.slice.call(document.querySelectorAll('#verb-table tbody tr'));
    var empty = document.getElementById('verb-empty');
    if (input) {
        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase();
            var shown = 0;
            rows.forEach(function (tr) {
                var match = tr.textContent.toLowerCase().indexOf(q) !== -1;
                tr.hidden = !match;
                if (match) { shown++; }
            });
            if (empty) { empty.hidden = shown !== 0; }
        });
    }
    var synth = window.speechSynthesis;
    var speakBtns = document.querySelectorAll('.speak');
    if (!synth || typeof SpeechSynthesisUtterance === 'undefined') {
        speakBtns.forEach(function (b) { b.hidden = true; });
        return;
    }
    speakBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var u = new SpeechSynthesisUtterance(btn.dataset.text);
            u.lang = 'en-US';
            synth.cancel();
            synth.speak(u);
        });
    });
})();
</script>
