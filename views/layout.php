<?php
// Tüm sayfaları saran iskelet. $title ve $content render() tarafından sağlanır.
declare(strict_types=1);
/** @var string $title */
/** @var string $content */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="/" class="logo">
                <span class="logo-badge">D</span>
                <span class="logo-text">DiDn</span>
            </a>
            <?php $cu = current_user(); ?>
            <nav class="nav">
                <a href="/">Sözlük</a>
                <a href="/gramer">Gramer</a>
                <a href="/es/grammar">Spanish</a>
                <a href="/blog">Blog</a>
                <a href="/rehber">Rehberler</a>
                <a href="/haber">Haberler</a>
                <?php if ($cu): ?>
                    <?php if ($cu['role'] === 'admin'): ?>
                        <a href="/admin" class="nav-admin">Admin</a>
                    <?php endif; ?>
                    <a href="/cikis">Çıkış</a>
                <?php else: ?>
                    <a href="/giris">Giriş</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container main">
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>DiDn — İngilizce ↔ Türkçe Sözlük</p>
            <p class="footer-credit">
                Sözlük verileri <a href="https://www.wiktionary.org/" target="_blank" rel="noopener">Vikisözlük (Wiktionary)</a>
                kaynaklı olup <a href="https://kaikki.org/" target="_blank" rel="noopener">kaikki.org</a> üzerinden derlenmiştir.
                <a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" rel="noopener">CC BY-SA 4.0</a> lisansı altında kullanılmaktadır.
            </p>
        </div>
    </footer>
</body>
</html>
