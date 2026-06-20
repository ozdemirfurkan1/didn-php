<?php declare(strict_types=1); ?>
<section class="hero">
    <h1>İngilizce ↔ Türkçe Sözlük</h1>
    <p class="hero-sub">Bir kelime ara; anlamı, çevirileri, telaffuzu ve örnek cümleleriyle.</p>
    <?php $curQ = ''; $curDir = 'en-tr'; include __DIR__ . '/_search.php'; ?>
</section>
