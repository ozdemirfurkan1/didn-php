<?php
// Genel yardımcılar: HTML kaçışı, slug, part-of-speech çevirisi, görünüm render.

declare(strict_types=1);

// HTML kaçışı (XSS koruması).
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Yönlendirme.
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

// Part-of-speech Türkçe karşılığı (lib/part-of-speech.ts ile aynı tablo).
function translate_pos(string $pos): string
{
    static $map = [
        'noun' => 'isim', 'verb' => 'fiil', 'adjective' => 'sıfat', 'adverb' => 'zarf',
        'pronoun' => 'zamir', 'preposition' => 'edat', 'conjunction' => 'bağlaç',
        'interjection' => 'ünlem', 'article' => 'artikel', 'determiner' => 'belirteç',
        'exclamation' => 'ünlem', 'abbreviation' => 'kısaltma', 'expression' => 'ifade',
        'prefix' => 'önek', 'suffix' => 'sonek', '-1' => 'diğer',
        'adj' => 'sıfat', 'adv' => 'zarf', 'pron' => 'zamir', 'prep' => 'edat',
        'conj' => 'bağlaç', 'intj' => 'ünlem', 'det' => 'belirteç', 'num' => 'sayı',
        'name' => 'özel isim', 'phrase' => 'ifade', 'prep_phrase' => 'edat öbeği',
        'proverb' => 'atasözü', 'contraction' => 'büzülme', 'particle' => 'ilgeç',
        'symbol' => 'sembol', 'punct' => 'noktalama', 'character' => 'karakter',
        'infix' => 'içek', 'interfix' => 'araek', 'circumfix' => 'çevreek',
    ];
    $key = mb_strtolower(trim($pos), 'UTF-8');
    if (isset($map[$key])) {
        return $map[$key];
    }
    return $key !== '' ? $pos : 'diğer';
}

// Başlıktan URL-dostu slug (Türkçe karakterler sadeleştirilir).
function slugify(string $input): string
{
    $tr = ['ç','ğ','ı','ö','ş','ü','Ç','Ğ','İ','Ö','Ş','Ü'];
    $en = ['c','g','i','o','s','u','c','g','i','o','s','u'];
    $s = str_replace($tr, $en, trim($input));
    $s = mb_strtolower($s, 'UTF-8');
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    $s = trim((string) $s, '-');
    return mb_substr($s, 0, 200, 'UTF-8');
}

// --- SEO: site kök URL'si ve sitemap üretimi -------------------------------

// İsteğe göre mutlak kök URL (örn. https://didn.net). Sitemap/canonical için.
function site_base_url(): string
{
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443)
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'didn.net';
    return $scheme . '://' . $host;
}

// Tüm önemli sayfaları içeren sitemap XML'i üretir (statik + gramer + içerik).
function build_sitemap(): string
{
    $base = site_base_url();
    $urls = [];
    $add  = function (string $path, ?string $lastmod = null, ?string $priority = null) use (&$urls, $base) {
        $urls[] = ['loc' => $base . $path, 'lastmod' => $lastmod, 'priority' => $priority];
    };

    // Statik / liste sayfaları
    $add('/', null, '1.0');
    $add('/gramer', null, '0.8');
    $add('/es/grammar', null, '0.6');
    $add('/blog', null, '0.6');
    $add('/rehber', null, '0.6');
    $add('/haber', null, '0.6');

    // Gramer dersleri (en + es track)
    $gDate  = ($m = @filemtime(__DIR__ . '/../data/grammar.json')) ? date('Y-m-d', $m) : null;
    foreach (all_lessons('en') as $l) {
        $add('/gramer/' . rawurlencode($l['slug']), $gDate, '0.7');
    }
    $esDate = ($m = @filemtime(__DIR__ . '/../data/grammar-es.json')) ? date('Y-m-d', $m) : null;
    foreach (all_lessons('es') as $l) {
        $add('/es/grammar/' . rawurlencode($l['slug']), $esDate, '0.5');
    }

    // Yayınlanan içerik (blog/rehber/haber) — DB erişilemezse sessizce atla.
    try {
        $routeOf = ['blog' => 'blog', 'guide' => 'rehber', 'news' => 'haber'];
        foreach ($routeOf as $type => $route) {
            foreach (list_published($type) as $it) {
                $when = $it['published_at'] ?? $it['created_at'] ?? null;
                $add('/' . $route . '/' . rawurlencode($it['slug']), $when ? date('Y-m-d', strtotime($when)) : null, '0.6');
            }
        }
    } catch (Throwable $e) {
        // Sitemap statik + gramer ile yine de döner.
    }

    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        $xml .= '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . '</loc>';
        if ($u['lastmod'])  { $xml .= '<lastmod>' . $u['lastmod'] . '</lastmod>'; }
        if ($u['priority']) { $xml .= '<priority>' . $u['priority'] . '</priority>'; }
        $xml .= '</url>' . "\n";
    }
    $xml .= '</urlset>' . "\n";
    return $xml;
}

// Basit Markdown-lite gövde render'ı (## / ### başlık, "- " liste, paragraf).
function render_markdown(string $body): string
{
    $blocks = preg_split('/\n{2,}/', trim($body)) ?: [];
    $html   = '';
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') {
            continue;
        }
        if (str_starts_with($block, '### ')) {
            $html .= '<h3>' . e(substr($block, 4)) . '</h3>';
            continue;
        }
        if (str_starts_with($block, '## ')) {
            $html .= '<h2>' . e(substr($block, 3)) . '</h2>';
            continue;
        }
        $lines      = explode("\n", $block);
        $allBullets = true;
        foreach ($lines as $l) {
            if (!str_starts_with($l, '- ')) {
                $allBullets = false;
                break;
            }
        }
        if ($allBullets) {
            $html .= '<ul>';
            foreach ($lines as $l) {
                $html .= '<li>' . e(substr($l, 2)) . '</li>';
            }
            $html .= '</ul>';
            continue;
        }
        $html .= '<p>' . nl2br(e($block)) . '</p>';
    }
    return $html;
}

// --- Flash mesajları (oturum üzerinden, PRG sonrası gösterilir) -----------
function set_flash(string $msg, string $type = 'ok'): void
{
    start_session();
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}

function get_flashes(): array
{
    start_session();
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// Bir görünümü layout içinde render eder.
function render(string $view, array $data = []): void
{
    $data['title'] = $data['title'] ?? 'DiDn — İngilizce Türkçe Sözlük';
    extract($data, EXTR_SKIP);
    $viewFile = __DIR__ . '/../views/' . $view . '.php';
    ob_start();
    require $viewFile;
    $content = ob_get_clean();
    require __DIR__ . '/../views/layout.php';
}
