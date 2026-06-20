<?php
// Arama formu parçası. $curQ ve $curDir opsiyoneldir.
declare(strict_types=1);
$curQ   = $curQ ?? '';
$curDir = $curDir ?? 'en-tr';
?>
<form class="search-box" action="/ara" method="get">
    <div class="dir-seg">
        <input type="radio" id="dir-en" name="dir" value="en-tr" <?= $curDir === 'en-tr' ? 'checked' : '' ?>>
        <label for="dir-en">EN&nbsp;→&nbsp;TR</label>
        <input type="radio" id="dir-tr" name="dir" value="tr-en" <?= $curDir === 'tr-en' ? 'checked' : '' ?>>
        <label for="dir-tr">TR&nbsp;→&nbsp;EN</label>
    </div>
    <input
        type="text"
        name="q"
        value="<?= e($curQ) ?>"
        placeholder="Bir kelime yazın…"
        autocomplete="off"
        autofocus>
    <button type="submit">Ara</button>
</form>
