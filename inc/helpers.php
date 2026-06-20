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
