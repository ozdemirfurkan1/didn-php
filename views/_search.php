<?php
// Arama formu parçası. $curQ ve $curDir opsiyoneldir.
declare(strict_types=1);
$curQ   = $curQ ?? '';
$curDir = $curDir ?? 'auto';
?>
<form class="search-box" action="/ara" method="get" autocomplete="off">
    <div class="dir-seg">
        <input type="radio" id="dir-auto" name="dir" value="auto" <?= ($curDir !== 'en-tr' && $curDir !== 'tr-en') ? 'checked' : '' ?>>
        <label for="dir-auto">Otomatik</label>
        <input type="radio" id="dir-en" name="dir" value="en-tr" <?= $curDir === 'en-tr' ? 'checked' : '' ?>>
        <label for="dir-en">EN&nbsp;→&nbsp;TR</label>
        <input type="radio" id="dir-tr" name="dir" value="tr-en" <?= $curDir === 'tr-en' ? 'checked' : '' ?>>
        <label for="dir-tr">TR&nbsp;→&nbsp;EN</label>
    </div>
    <div class="search-field">
        <input
            type="text"
            name="q"
            id="search-input"
            value="<?= e($curQ) ?>"
            placeholder="Bir kelime yazın…"
            autocomplete="off"
            role="combobox"
            aria-autocomplete="list"
            aria-expanded="false"
            aria-controls="suggest-list"
            autofocus>
        <ul class="suggest-list" id="suggest-list" role="listbox" hidden></ul>
    </div>
    <button type="submit">Ara</button>
</form>

<?php if (!defined('DIDN_SEARCH_JS')): define('DIDN_SEARCH_JS', true); ?>
<script>
(function () {
    var input = document.getElementById('search-input');
    var list  = document.getElementById('suggest-list');
    if (!input || !list) return;

    var timer = null, active = -1, items = [];

    function wordUrl(it) {
        return (it.lang === 'tr' ? '/tr/' : '/en/') + encodeURIComponent(it.word);
    }

    function close() {
        list.hidden = true;
        list.innerHTML = '';
        items = []; active = -1;
        input.setAttribute('aria-expanded', 'false');
    }

    function render(data) {
        items = data || [];
        if (!items.length) { close(); return; }
        list.innerHTML = items.map(function (it, i) {
            var tag = it.lang === 'tr' ? 'TR' : 'İNG';
            return '<li role="option" data-i="' + i + '">' +
                   '<span class="sg-word"></span>' +
                   '<span class="sg-tag">' + tag + '</span></li>';
        }).join('');
        // Metni textContent ile güvenli yaz (XSS önlemi).
        Array.prototype.forEach.call(list.children, function (li, i) {
            li.querySelector('.sg-word').textContent = items[i].word;
        });
        active = -1;
        list.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    function highlight() {
        Array.prototype.forEach.call(list.children, function (li, i) {
            li.classList.toggle('active', i === active);
        });
    }

    function fetchSuggest() {
        var q = input.value.trim();
        if (q.length < 2) { close(); return; }
        fetch('/api/suggest?q=' + encodeURIComponent(q))
            .then(function (r) { return r.json(); })
            .then(render)
            .catch(close);
    }

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(fetchSuggest, 150);
    });

    input.addEventListener('keydown', function (e) {
        if (list.hidden) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(active + 1, items.length - 1); highlight(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(active - 1, -1); highlight(); }
        else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); window.location = wordUrl(items[active]); }
        else if (e.key === 'Escape') { close(); }
    });

    list.addEventListener('mousedown', function (e) {
        var li = e.target.closest('li[data-i]');
        if (!li) return;
        e.preventDefault();
        window.location = wordUrl(items[+li.dataset.i]);
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.search-field')) close();
    });
})();
</script>
<?php endif; ?>
