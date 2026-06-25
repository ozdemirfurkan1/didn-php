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

// Tek sitemap dosyasına sığacak azami URL sayısı (Google sınırı 50.000).
const SITEMAP_CHUNK = 40000;

// --- Düzensiz fiiller (data/irregular-verbs.json) --------------------------

function irregular_verbs(): array
{
    static $cache = null;
    if ($cache === null) {
        $json  = @file_get_contents(__DIR__ . '/../data/irregular-verbs.json');
        $cache = $json ? (json_decode($json, true) ?: []) : [];
    }
    return $cache;
}

// Yalın hâle göre düzensiz fiil kaydını döndürür (yoksa null).
function irregular_verb(string $base): ?array
{
    $b = mb_strtolower(trim($base), 'UTF-8');
    foreach (irregular_verbs() as $v) {
        if (mb_strtolower($v['base'] ?? '', 'UTF-8') === $b) {
            return $v;
        }
    }
    return null;
}

// --- Fiil çekimi (kelime sayfasındaki çekim kutusu için) -------------------

function _en_is_vowel(string $c): bool
{
    return $c !== '' && strpos('aeiou', $c) !== false;
}

// Tek heceli kısa fiillerde son ünsüzü ikileme durumu (stop→stopping).
function _en_doubles(string $b): bool
{
    $len = strlen($b);
    if ($len < 3 || $len > 4) {
        return false;
    }
    $c1 = $b[$len - 1];
    $v  = $b[$len - 2];
    $c0 = $b[$len - 3];
    return !_en_is_vowel($c1) && _en_is_vowel($v) && !_en_is_vowel($c0) && strpos('wxy', $c1) === false;
}

function verb_ing(string $b): string
{
    $len = strlen($b);
    if ($len >= 2 && substr($b, -2) === 'ie') {
        return substr($b, 0, -2) . 'ying';            // lie → lying
    }
    if (substr($b, -1) === 'e' && !in_array(substr($b, -2), ['ee', 'ye', 'oe'], true) && $len > 2) {
        return substr($b, 0, -1) . 'ing';             // make → making
    }
    if (_en_doubles($b)) {
        return $b . substr($b, -1) . 'ing';           // stop → stopping
    }
    return $b . 'ing';
}

function verb_ed(string $b): string
{
    $len = strlen($b);
    if (substr($b, -1) === 'e') {
        return $b . 'd';                              // like → liked
    }
    if ($len >= 2 && substr($b, -1) === 'y' && !_en_is_vowel($b[$len - 2])) {
        return substr($b, 0, -1) . 'ied';            // study → studied
    }
    if (_en_doubles($b)) {
        return $b . substr($b, -1) . 'ed';           // stop → stopped
    }
    return $b . 'ed';
}

function verb_third_person(string $b): string
{
    $len = strlen($b);
    if (in_array(substr($b, -1), ['s', 'x', 'z', 'o'], true) || in_array(substr($b, -2), ['ss', 'sh', 'ch'], true)) {
        return $b . 'es';                             // watch → watches
    }
    if ($len >= 2 && substr($b, -1) === 'y' && !_en_is_vowel($b[$len - 2])) {
        return substr($b, 0, -1) . 'ies';            // study → studies
    }
    return $b . 's';
}

// Bir fiilin tüm temel biçimlerini döndürür. Düzensizse V2/V3 tablodan,
// değilse kurala göre üretilir.
function verb_conjugation(string $base): array
{
    $b   = mb_strtolower(trim($base), 'UTF-8');
    $irr = irregular_verb($b);
    return [
        'base'        => $b,
        'thirdPerson' => verb_third_person($b),
        'ing'         => verb_ing($b),
        'past'        => $irr ? $irr['past'] : verb_ed($b),
        'pp'          => $irr ? $irr['pp'] : verb_ed($b),
        'irregular'   => (bool) $irr,
    ];
}

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

// Geçerli isteğin canonical URL'si (sorgu dizesi olmadan).
function current_canonical(): string
{
    return site_base_url() . strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
}

// Sitemap index: sayfa sitemap'i + sözlük kelime chunk'larını listeler.
function build_sitemap_index(): string
{
    $base = site_base_url();
    $maps = ['/sitemap-pages.xml'];
    try {
        foreach (['en', 'tr'] as $lang) {
            $pages = (int) ceil(dictionary_word_count($lang) / SITEMAP_CHUNK);
            for ($i = 1; $i <= $pages; $i++) {
                $maps[] = "/sitemap-$lang-$i.xml";
            }
        }
    } catch (Throwable $e) {
        // DB yoksa yalnızca sayfa sitemap'i listelenir.
    }
    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($maps as $m) {
        $xml .= '  <sitemap><loc>' . htmlspecialchars($base . $m, ENT_XML1, 'UTF-8') . '</loc></sitemap>' . "\n";
    }
    $xml .= '</sitemapindex>' . "\n";
    return $xml;
}

// Sözlük kelimelerinin bir chunk'ı için sitemap (lang: en|tr, page: 1..N).
function build_words_sitemap(string $lang, int $page): string
{
    $base   = site_base_url();
    $prefix = $lang === 'tr' ? '/tr/' : '/en/';
    $words  = dictionary_words($lang, ($page - 1) * SITEMAP_CHUNK, SITEMAP_CHUNK);
    $xml    = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml   .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($words as $w) {
        $loc  = $base . $prefix . rawurlencode($w);
        $xml .= '  <url><loc>' . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . '</loc></url>' . "\n";
    }
    $xml .= '</urlset>' . "\n";
    return $xml;
}

// Statik + gramer + yayınlanan içerik sayfalarının sitemap'i.
function build_pages_sitemap(): string
{
    $base = site_base_url();
    $urls = [];
    $add  = function (string $path, ?string $lastmod = null, ?string $priority = null) use (&$urls, $base) {
        $urls[] = ['loc' => $base . $path, 'lastmod' => $lastmod, 'priority' => $priority];
    };

    // Statik / liste sayfaları
    $add('/', null, '1.0');
    $add('/gramer', null, '0.8');
    $add('/duzensiz-fiiller', null, '0.7');
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
