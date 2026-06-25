<?php
// Flashcard çalışma modu: kartı çevir, "biliyorum / tekrar".
declare(strict_types=1);
/** @var array $cards  her biri: key, word, meaning, url, learned */
?>
<a href="/kelimelerim" class="back-link">← Kelimelerim</a>
<header class="page-head">
    <h1>Çalışma</h1>
    <p class="page-sub">Kartı çevir, anlamı hatırlamaya çalış. Bildiklerini işaretle; sonunda “öğrendim” olarak kaydedilir.</p>
</header>

<?php if (!$cards): ?>
    <div class="card empty-state">
        <h2>Çalışacak kelime yok</h2>
        <p>Önce sözlükten birkaç kelime ekle. <a class="chip" href="/">Sözlüğe git</a></p>
    </div>
<?php else: ?>
    <section id="study-area" class="study card">
        <div class="study-progress"><span id="study-pos">1</span> / <span id="study-total"><?= count($cards) ?></span> · Bilinen: <span id="study-known">0</span></div>
        <div class="flashcard" id="flashcard" role="button" tabindex="0" aria-label="Kartı çevir">
            <div class="fc-word" id="fc-word"></div>
            <div class="fc-back" id="fc-back" hidden>
                <p class="fc-meaning" id="fc-meaning"></p>
                <a class="chip" id="fc-link" href="#">Sözlükte aç →</a>
            </div>
            <p class="fc-hint" id="fc-hint">Anlamı görmek için karta dokun</p>
        </div>
        <div class="study-actions">
            <button type="button" class="btn-secondary" id="btn-again">↺ Tekrar</button>
            <button type="button" class="btn-primary" id="btn-known">✓ Biliyorum</button>
        </div>
    </section>

    <section id="study-done" class="card" hidden>
        <h2>Bitti! 🎉</h2>
        <p><strong id="done-known">0</strong> kelimeyi “biliyorum” olarak işaretledin.</p>
        <form method="post" action="/kelime-durum-toplu" id="finish-form" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="learned" id="learned-input" value="">
            <button type="submit" class="btn-primary">Öğrendiklerimi kaydet</button>
        </form>
        <p style="margin-top:12px"><button type="button" class="btn-secondary" id="btn-restart">↺ Baştan başla</button></p>
    </section>

    <script id="cards-data" type="application/json"><?= json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
    <script>
    (function () {
        var cards = [];
        try { cards = JSON.parse(document.getElementById('cards-data').textContent); } catch (e) { return; }
        var area = document.getElementById('study-area');
        var done = document.getElementById('study-done');
        var fcWord = document.getElementById('fc-word');
        var fcBack = document.getElementById('fc-back');
        var fcMeaning = document.getElementById('fc-meaning');
        var fcLink = document.getElementById('fc-link');
        var fcHint = document.getElementById('fc-hint');
        var card = document.getElementById('flashcard');
        var posEl = document.getElementById('study-pos');
        var knownEl = document.getElementById('study-known');
        var i = 0, flipped = false, known = {};

        function render() {
            var c = cards[i];
            fcWord.textContent = c.word;
            fcMeaning.textContent = c.meaning && c.meaning.length ? c.meaning : '(çeviri kayıtlı değil — sözlükte aç)';
            fcLink.href = c.url;
            flipped = false;
            fcBack.hidden = true;
            fcHint.hidden = false;
            posEl.textContent = (i + 1);
            knownEl.textContent = Object.keys(known).length;
        }
        function flip() {
            flipped = !flipped;
            fcBack.hidden = !flipped;
            fcHint.hidden = flipped;
        }
        function next(markKnown) {
            var c = cards[i];
            if (markKnown) { known[c.key] = true; }
            i++;
            if (i >= cards.length) { finish(); } else { render(); }
        }
        function finish() {
            area.hidden = true;
            done.hidden = false;
            document.getElementById('done-known').textContent = Object.keys(known).length;
            document.getElementById('learned-input').value = Object.keys(known).join('\n');
        }
        card.addEventListener('click', flip);
        card.addEventListener('keydown', function (e) { if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); flip(); } });
        document.getElementById('btn-again').addEventListener('click', function () { next(false); });
        document.getElementById('btn-known').addEventListener('click', function () { next(true); });
        document.getElementById('btn-restart').addEventListener('click', function () {
            i = 0; known = {}; done.hidden = true; area.hidden = false; render();
        });
        render();
    })();
    </script>
<?php endif; ?>
