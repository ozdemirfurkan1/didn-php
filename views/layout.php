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
    <script>(function(){try{var t=localStorage.getItem('theme');if(t==='dark'||t==='light')document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
    <?php
    $metaDesc = trim((string) ($description ?? ''));
    if ($metaDesc === '') {
        $metaDesc = 'DiDn — İngilizce ↔ Türkçe sözlük, gramer dersleri ve İngilizce öğrenme rehberleri.';
    }
    $metaDesc = trim((string) preg_replace('/\s+/', ' ', $metaDesc));
    if (mb_strlen($metaDesc, 'UTF-8') > 160) {
        $metaDesc = mb_substr($metaDesc, 0, 157, 'UTF-8') . '…';
    }
    $canonical = current_canonical();
    $ogType = $ogType ?? 'website';
    ?>
    <meta name="description" content="<?= e($metaDesc) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($metaDesc) ?>">
    <meta property="og:type" content="<?= e($ogType) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:site_name" content="DiDn">
    <meta name="twitter:card" content="summary">
    <?php if (!empty($jsonLd)): ?>
        <script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
    <?php endif; ?>
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/assets/style.css?v=<?= e((string) @filemtime(__DIR__ . '/../assets/style.css')) ?>">
    <?php $gaId = config()['ga_measurement_id'] ?? ''; ?>
    <?php if ($gaId !== ''): ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?= rawurlencode($gaId) ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', <?= json_encode($gaId) ?>);
        </script>
    <?php endif; ?>
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
                <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Açık/koyu tema">🌙</button>
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

    <script>
    (function () {
        var btn = document.getElementById('theme-toggle');
        if (!btn) { return; }
        function current() {
            var t = document.documentElement.getAttribute('data-theme');
            if (t === 'dark' || t === 'light') { return t; }
            return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        function syncIcon() { btn.textContent = current() === 'dark' ? '☀️' : '🌙'; }
        syncIcon();
        btn.addEventListener('click', function () {
            var next = current() === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            try { localStorage.setItem('theme', next); } catch (e) {}
            syncIcon();
        });
    })();
    </script>
</body>
</html>
