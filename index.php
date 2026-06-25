<?php
// Ön denetleyici (front controller). Tüm istekler .htaccess ile buraya yönlenir.

declare(strict_types=1);

require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/helpers.php';
require __DIR__ . '/inc/lookup.php';
require __DIR__ . '/inc/grammar.php';
require __DIR__ . '/inc/auth.php';
require __DIR__ . '/inc/settings.php';
require __DIR__ . '/inc/content.php';
require __DIR__ . '/inc/openai.php';
require __DIR__ . '/inc/blog-generator.php';

// Oturumu en başta başlat (çerez henüz çıktı olmadan gönderilsin).
start_session();

// PHP yerleşik sunucusunda (geliştirme) statik dosyaları doğrudan servis et.
if (php_sapi_name() === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = '/' . trim(rawurldecode((string) $uri), '/');
$segments = $uri === '/' ? [] : explode('/', ltrim($uri, '/'));

// --- Yönlendirme ----------------------------------------------------------

// Cron: otomatik blog üretimi ucu (oturum yok; cron_secret ile korumalı)
if ($uri === '/cron/generate-blog') {
    header('Content-Type: application/json; charset=utf-8');
    $secret = config()['cron_secret'] ?? '';
    if ($secret === '') {
        http_response_code(503);
        echo json_encode(['error' => 'cron_secret tanımlı değil']);
        exit;
    }
    $auth     = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $provided = preg_replace('/^Bearer\s+/i', '', $auth);
    if ($provided === '' || $provided === $auth) {
        $provided = $_GET['secret'] ?? '';
    }
    if (!hash_equals($secret, (string) $provided)) {
        http_response_code(401);
        echo json_encode(['error' => 'Yetkisiz']);
        exit;
    }
    try {
        echo json_encode(maybe_run_scheduled(), JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        http_response_code(502);
        echo json_encode(['ran' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// SEO: sitemap ve robots (oturum/yetki gerektirmez)
if ($uri === '/sitemap.xml') {
    header('Content-Type: application/xml; charset=utf-8');
    echo build_sitemap_index();
    exit;
}
if ($uri === '/sitemap-pages.xml') {
    header('Content-Type: application/xml; charset=utf-8');
    echo build_pages_sitemap();
    exit;
}
if (preg_match('#^/sitemap-(en|tr)-(\d+)\.xml$#', $uri, $smMatch)) {
    header('Content-Type: application/xml; charset=utf-8');
    echo build_words_sitemap($smMatch[1], (int) $smMatch[2]);
    exit;
}
if ($uri === '/robots.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\nAllow: /\n\nSitemap: " . site_base_url() . "/sitemap.xml\n";
    exit;
}

if ($uri === '/') {
    render('home', [
        'wotd' => get_word_of_the_day(),
        'jsonLd' => [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'DiDn',
            'url' => site_base_url(),
            'inLanguage' => 'tr',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => site_base_url() . '/ara?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ]);
    exit;
}

// Düzensiz fiiller referans sayfası
if ($uri === '/duzensiz-fiiller') {
    render('irregular_verbs', [
        'verbs' => irregular_verbs(),
        'title' => 'İngilizce Düzensiz Fiiller Listesi (V1 V2 V3) — DiDn',
        'description' => 'İngilizce düzensiz fiiller (irregular verbs) tablosu: yalın hâl, ikinci hâl (V2) ve üçüncü hâl (V3) ile Türkçe anlamları. Arayarak hızlıca bul.',
    ]);
    exit;
}

// Seviyeye göre sık kullanılan İngilizce kelimeler (sözlüğe linkli hub)
if ($uri === '/ingilizce-kelimeler') {
    render('common_words', [
        'groups' => load_dataset('common-words.json'),
        'title'  => 'En Sık Kullanılan İngilizce Kelimeler (Seviyeye Göre) — DiDn',
        'description' => 'Seviyeye göre (A1, A2, B1) en sık kullanılan İngilizce kelimeler listesi. Her kelimeye tıklayıp Türkçe anlamını, telaffuzunu ve örneklerini gör.',
    ]);
    exit;
}

// Phrasal verbs (öbek fiiller) referans sayfası
if ($uri === '/phrasal-verbs') {
    render('ref_cards', [
        'items'    => load_dataset('phrasal-verbs.json'),
        'heading'  => 'İngilizce Phrasal Verbs (Öbek Fiiller)',
        'lead'     => 'En sık kullanılan İngilizce phrasal verb (öbek fiil): anlamları ve örnek cümleleriyle.',
        'intro'    => 'Phrasal verb, bir fiil ile edat/zarfın birleşerek çoğu zaman yeni bir anlam kazandığı kalıptır (give up = vazgeçmek). Aşağıdan arayarak bulabilirsin.',
        'ph'       => 'Phrasal verb ara (ör. give up, vazgeçmek)…',
        'related'  => [['/duzensiz-fiiller', 'Düzensiz Fiiller'], ['/deyimler', 'Deyimler'], ['/gramer', 'Gramer Konuları']],
        'title'    => 'İngilizce Phrasal Verbs Listesi ve Anlamları — DiDn',
        'description' => 'En sık kullanılan İngilizce phrasal verbs (öbek fiiller) listesi: Türkçe anlamları ve örnek cümleleriyle. Arayarak hızlıca bul.',
    ]);
    exit;
}

// Deyimler (idioms) referans sayfası
if ($uri === '/deyimler') {
    render('ref_cards', [
        'items'    => load_dataset('idioms.json'),
        'heading'  => 'İngilizce Deyimler (Idioms)',
        'lead'     => 'Sık kullanılan İngilizce deyimler: Türkçe karşılıkları ve örnek cümleleriyle.',
        'intro'    => 'Deyim (idiom), kelimelerin tek tek anlamından farklı, kalıplaşmış bir anlam taşıyan ifadedir (a piece of cake = çok kolay). Aşağıdan arayarak bulabilirsin.',
        'ph'       => 'Deyim ara (ör. break the ice, kolay)…',
        'related'  => [['/phrasal-verbs', 'Phrasal Verbs'], ['/duzensiz-fiiller', 'Düzensiz Fiiller'], ['/gramer', 'Gramer Konuları']],
        'title'    => 'İngilizce Deyimler ve Anlamları (Idioms) — DiDn',
        'description' => 'Sık kullanılan İngilizce deyimler (idioms) listesi: Türkçe karşılıkları ve örnek cümleleriyle. Arayarak hızlıca bul.',
    ]);
    exit;
}

// Arama formu: /ara?q=...&dir=en-tr  ->  /en/{kelime} ya da /tr/{kelime}
if ($uri === '/ara') {
    $q   = trim($_GET['q'] ?? '');
    $dir = ($_GET['dir'] ?? 'en-tr') === 'tr-en' ? 'tr-en' : 'en-tr';
    if ($q === '') {
        redirect('/');
    }
    $prefix = $dir === 'en-tr' ? '/en/' : '/tr/';
    redirect($prefix . rawurlencode($q));
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// --- Kimlik doğrulama -----------------------------------------------------

if ($uri === '/giris') {
    if ($method === 'POST') {
        $email    = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        if (!csrf_check()) {
            render('auth_form', ['mode' => 'login', 'error' => 'Oturum süresi doldu, tekrar deneyin.', 'old' => ['email' => $email], 'title' => 'Giriş — DiDn']);
            exit;
        }
        if (login_user($email, $password)) {
            $u = current_user();
            redirect($u && $u['role'] === 'admin' ? '/admin' : '/');
        }
        render('auth_form', ['mode' => 'login', 'error' => 'E-posta veya şifre hatalı.', 'old' => ['email' => $email], 'title' => 'Giriş — DiDn']);
        exit;
    }
    if (current_user()) {
        redirect('/');
    }
    render('auth_form', ['mode' => 'login', 'title' => 'Giriş — DiDn']);
    exit;
}

if ($uri === '/kayit') {
    if ($method === 'POST') {
        $email    = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $name     = trim($_POST['name'] ?? '');
        if (!csrf_check()) {
            render('auth_form', ['mode' => 'register', 'error' => 'Oturum süresi doldu, tekrar deneyin.', 'old' => ['email' => $email, 'name' => $name], 'title' => 'Kayıt — DiDn']);
            exit;
        }
        $r = register_user($email, $password, $name);
        if ($r['ok']) {
            redirect('/');
        }
        render('auth_form', ['mode' => 'register', 'error' => $r['error'], 'old' => ['email' => $email, 'name' => $name], 'title' => 'Kayıt — DiDn']);
        exit;
    }
    if (current_user()) {
        redirect('/');
    }
    render('auth_form', ['mode' => 'register', 'title' => 'Kayıt — DiDn']);
    exit;
}

if ($uri === '/cikis') {
    logout_user();
    redirect('/');
}

// --- Admin paneli ---------------------------------------------------------

if (($segments[0] ?? '') === 'admin') {
    $admin = require_admin(); // admin değilse yönlendirir
    $sub   = $segments[1] ?? '';

    // Pano
    if ($sub === '') {
        render('admin_dashboard', ['title' => 'Yönetim Paneli — DiDn']);
        exit;
    }

    // Ayarlar
    if ($sub === 'ayarlar') {
        if ($method === 'POST') {
            if (!csrf_check()) {
                set_flash('Oturum süresi doldu, tekrar deneyin.', 'error');
                redirect('/admin/ayarlar');
            }
            $apiKey = trim($_POST['apiKey'] ?? '');
            $model  = trim($_POST['model'] ?? '');
            if ($apiKey !== '') {
                if (!str_starts_with($apiKey, 'sk-')) {
                    set_flash("OpenAI anahtarı 'sk-' ile başlamalı.", 'error');
                    redirect('/admin/ayarlar');
                }
                set_openai_key($apiKey);
            }
            set_openai_model(in_array($model, OPENAI_ALLOWED_MODELS, true) ? $model : (config()['openai_default_model'] ?? 'gpt-4o-mini'));
            set_flash('Ayarlar kaydedildi.');
            redirect('/admin/ayarlar');
        }
        render('admin_settings', [
            'openaiConfigured' => is_openai_configured(),
            'model'            => get_openai_model(),
            'title'            => 'Ayarlar — DiDn',
        ]);
        exit;
    }

    // Şifre değiştir (POST)
    if ($sub === 'sifre' && $method === 'POST') {
        if (!csrf_check()) {
            set_flash('Oturum süresi doldu, tekrar deneyin.', 'error');
            redirect('/admin/ayarlar');
        }
        $cur = $_POST['currentPassword'] ?? '';
        $new = $_POST['newPassword'] ?? '';
        $stmt = db()->prepare("SELECT password_hash FROM user WHERE id = :id");
        $stmt->execute([':id' => $admin['id']]);
        $hash = $stmt->fetch()['password_hash'] ?? '';
        if (!password_verify($cur, $hash)) {
            set_flash('Mevcut şifre hatalı.', 'error');
        } elseif (mb_strlen($new) < 8) {
            set_flash('Yeni şifre en az 8 karakter olmalı.', 'error');
        } else {
            db()->prepare("UPDATE user SET password_hash = :h WHERE id = :id")
                ->execute([':h' => password_hash($new, PASSWORD_DEFAULT), ':id' => $admin['id']]);
            set_flash('Şifre güncellendi.');
        }
        redirect('/admin/ayarlar');
    }

    // OpenAI bağlantı testi
    if ($sub === 'ayarlar-test' && $method === 'POST') {
        if (csrf_check()) {
            $r = openai_test_connection();
            set_flash(
                $r['ok'] ? 'Bağlantı başarılı — anahtar geçerli.' : ('Bağlantı başarısız: ' . ($r['error'] ?? '')),
                $r['ok'] ? 'ok' : 'error'
            );
        }
        redirect('/admin/ayarlar');
    }

    // İçerik Üretici (blog/rehber/haber). İki rota:
    //  /admin/blog-uretici[/uret]        → blog (eski URL, uyumluluk)
    //  /admin/uretici/{type}[/uret]      → tüm türler
    if ($sub === 'blog-uretici' || $sub === 'uretici') {
        if ($sub === 'blog-uretici') {
            $genType = 'blog';
            $genAct  = $segments[2] ?? '';
        } else {
            $genType = $segments[2] ?? '';
            $genAct  = $segments[3] ?? '';
        }
        if (!is_content_type($genType)) {
            redirect('/admin');
        }
        $genBase = '/admin/uretici/' . $genType;
        if ($genAct === 'uret' && $method === 'POST') {
            if (csrf_check()) {
                $mode = ($_POST['mode'] ?? 'draft') === 'publish' ? 'publish' : 'draft';
                $r = run_content_generation($genType, $mode);
                set_flash(
                    $r['ok'] ? ($mode === 'publish' ? 'Üretildi ve yayınlandı.' : 'Taslak olarak üretildi.') : ('Üretim başarısız: ' . $r['error']),
                    $r['ok'] ? 'ok' : 'error'
                );
            }
            redirect($genBase);
        }
        if ($method === 'POST') { // ayarları kaydet
            if (csrf_check()) {
                $earliest = max(0, min(23, (int) ($_POST['earliestHour'] ?? 9)));
                $latest   = max(0, min(23, (int) ($_POST['latestHour'] ?? 18)));
                $pool = array_values(array_filter(array_map('trim', explode("\n", $_POST['topicPool'] ?? '')), fn($s) => $s !== ''));
                set_generator_config($genType, [
                    'enabled'           => !empty($_POST['enabled']),
                    'publishMode'       => ($_POST['publishMode'] ?? 'draft') === 'publish' ? 'publish' : 'draft',
                    'earliestHour'      => min($earliest, $latest),
                    'latestHour'        => max($earliest, $latest),
                    'topicPool'         => $pool,
                    'extraInstructions' => trim($_POST['extraInstructions'] ?? ''),
                ]);
                set_flash('Ayarlar kaydedildi.');
            }
            redirect($genBase);
        }
        render('admin_blog_generator', [
            'type'           => $genType,
            'typeLabel'      => CONTENT_TYPES[$genType]['label'],
            'genBase'        => $genBase,
            'config'         => get_generator_config($genType),
            'status'         => get_generator_status($genType),
            'cronConfigured' => !empty(config()['cron_secret']),
            'title'          => CONTENT_TYPES[$genType]['label'] . ' Üretici — DiDn',
        ]);
        exit;
    }

    // İçerik yönetimi
    if ($sub === 'icerik') {
        $type = $segments[2] ?? '';

        // İçerik ana sayfası (tip kartları)
        if ($type === '') {
            render('admin_content_home', ['title' => 'İçerik Yönetimi — DiDn']);
            exit;
        }
        if (!is_content_type($type)) {
            redirect('/admin/icerik');
        }
        $third = $segments[3] ?? '';

        // Liste
        if ($third === '') {
            render('admin_content_list', [
                'type'  => $type,
                'items' => list_content($type),
                'title' => CONTENT_TYPES[$type]['plural'] . ' — DiDn',
            ]);
            exit;
        }

        // Yeni
        if ($third === 'yeni') {
            if ($method === 'POST') {
                if (!csrf_check()) {
                    set_flash('Oturum süresi doldu.', 'error');
                    redirect("/admin/icerik/$type/yeni");
                }
                $title = trim($_POST['title'] ?? '');
                if ($title === '') {
                    set_flash('Başlık gerekli.', 'error');
                    redirect("/admin/icerik/$type/yeni");
                }
                create_content([
                    'type'       => $type,
                    'title'      => $title,
                    'slug'       => trim($_POST['slug'] ?? ''),
                    'summary'    => trim($_POST['summary'] ?? ''),
                    'body'       => $_POST['body'] ?? '',
                    'coverImage' => trim($_POST['coverImage'] ?? ''),
                    'status'     => $_POST['status'] ?? 'draft',
                    'source'     => 'manual',
                ]);
                set_flash('İçerik oluşturuldu.');
                redirect("/admin/icerik/$type");
            }
            render('admin_content_editor', ['type' => $type, 'item' => null, 'title' => 'Yeni İçerik — DiDn']);
            exit;
        }

        // AI ile üret → yeni taslak oluşturur, editöre yönlendirir
        if ($third === 'uret' && $method === 'POST') {
            if (csrf_check()) {
                $topic = trim($_POST['topic'] ?? '');
                if ($topic === '') {
                    set_flash('Konu gerekli.', 'error');
                    redirect("/admin/icerik/$type/yeni");
                }
                $gen = openai_generate_content($type, $topic, trim($_POST['instructions'] ?? ''));
                if (!$gen['ok']) {
                    set_flash('Üretim başarısız: ' . $gen['error'], 'error');
                    redirect("/admin/icerik/$type/yeni");
                }
                $newId = create_content([
                    'type' => $type, 'title' => $gen['title'], 'slug' => '',
                    'summary' => $gen['summary'], 'body' => $gen['body'], 'coverImage' => '',
                    'status' => 'draft', 'source' => 'ai', 'topic' => $topic,
                ]);
                set_flash('AI taslağı oluşturuldu — gözden geçirip kaydedin.');
                redirect("/admin/icerik/$type/$newId");
            }
            redirect("/admin/icerik/$type/yeni");
        }

        // Belirli içerik
        $id   = $third;
        $item = get_content($id);
        if (!$item || $item['type'] !== $type) {
            http_response_code(404);
            render('notfound', ['title' => 'Bulunamadı']);
            exit;
        }
        $action = $segments[4] ?? '';

        if ($action === 'sil' && $method === 'POST') {
            if (csrf_check()) {
                delete_content($id);
                set_flash('İçerik silindi.');
            }
            redirect("/admin/icerik/$type");
        }
        if ($action === 'durum' && $method === 'POST') {
            if (csrf_check()) {
                $s = $_POST['status'] ?? '';
                if (is_content_status($s)) {
                    update_content($id, ['status' => $s], $item);
                    set_flash('Durum güncellendi.');
                }
            }
            redirect("/admin/icerik/$type");
        }
        if ($action === '') {
            if ($method === 'POST') {
                if (!csrf_check()) {
                    set_flash('Oturum süresi doldu.', 'error');
                    redirect("/admin/icerik/$type/$id");
                }
                update_content($id, [
                    'title'      => trim($_POST['title'] ?? ''),
                    'slug'       => trim($_POST['slug'] ?? ''),
                    'summary'    => trim($_POST['summary'] ?? ''),
                    'body'       => $_POST['body'] ?? '',
                    'coverImage' => trim($_POST['coverImage'] ?? ''),
                    'status'     => $_POST['status'] ?? $item['status'],
                ], $item);
                set_flash('Değişiklikler kaydedildi.');
                redirect("/admin/icerik/$type");
            }
            render('admin_content_editor', ['type' => $type, 'item' => $item, 'title' => $item['title'] . ' — DiDn']);
            exit;
        }
    }

    http_response_code(404);
    render('notfound', ['title' => 'Bulunamadı']);
    exit;
}

// Gramer rotaları (track'lere göre). Mevcut /gramer (Türkçe→İngilizce) ve
// /es/grammar (İngilizce→İspanyolca). Yeni dil eklemek için grammar_tracks()
// içine bir kayıt + buraya bir eşleme yeterli.
$grammarRoutes = [
    'gramer'    => 'en', // /gramer , /gramer/{slug}
    'es/grammar' => 'es', // /es/grammar , /es/grammar/{slug}
];
foreach ($grammarRoutes as $path => $trackKey) {
    $pathSegs = explode('/', $path);
    $depth    = count($pathSegs);
    if (array_slice($segments, 0, $depth) !== $pathSegs) {
        continue;
    }
    $t = grammar_track($trackKey);
    // Liste sayfası: tam eşleşme (örn. /gramer ya da /es/grammar)
    if (count($segments) === $depth) {
        render('grammar_index', [
            'groups' => lessons_by_category($trackKey),
            't'      => $t,
            'title'  => $t['labels']['pageIndex'],
            'description' => $t['labels']['indexSub'] ?? '',
        ]);
        exit;
    }
    // Tek ders: önek + {slug}
    if (count($segments) === $depth + 1) {
        $lesson = get_lesson($segments[$depth], $trackKey);
        if ($lesson) {
            render('grammar_lesson', [
                'lesson' => $lesson,
                't'      => $t,
                'title'  => $lesson['title'] . ' — DiDn',
                'description' => $lesson['summary'] ?? '',
                'ogType' => 'article',
                'jsonLd' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'LearningResource',
                    'name' => $lesson['title'],
                    'description' => $lesson['summary'] ?? '',
                    'url' => current_canonical(),
                    'inLanguage' => $t['native'],
                    'learningResourceType' => 'Grammar lesson',
                    'educationalLevel' => $lesson['level'] ?? '',
                    'isPartOf' => [
                        '@type' => 'Course',
                        'name' => $t['labels']['indexTitle'] ?? 'Gramer',
                        'url' => site_base_url() . $t['base'],
                    ],
                ],
            ]);
            exit;
        }
    }
}

// Public içerik: /blog, /haber, /rehber (liste) ve /{route}/{slug} (detay)
$publicRoutes = ['blog' => 'blog', 'haber' => 'news', 'rehber' => 'guide'];
if (isset($publicRoutes[$segments[0] ?? ''])) {
    $ctype = $publicRoutes[$segments[0]];
    if (count($segments) === 1) {
        render('content_listing', [
            'type'  => $ctype,
            'items' => list_published($ctype),
            'title' => CONTENT_TYPES[$ctype]['plural'] . ' — DiDn',
            'description' => 'DiDn ' . CONTENT_TYPES[$ctype]['plural'] . ' — İngilizce öğrenmeye dair yazılar, rehberler ve haberler.',
        ]);
        exit;
    }
    if (count($segments) === 2) {
        $slug    = $segments[1];
        $item    = get_published_by_slug($ctype, $slug);
        $preview = false;
        if (!$item && is_admin()) {
            $item    = get_by_slug_any($ctype, $slug);
            $preview = true;
        }
        if ($item) {
            $artDate = $item['published_at'] ?? $item['created_at'] ?? null;
            $artMod  = (!empty($item['updated_at']) ? $item['updated_at'] : $artDate);
            render('content_article', [
                'item' => $item,
                'preview' => $preview,
                'title' => $item['title'] . ' — DiDn',
                'description' => $item['summary'] ?? '',
                'ogType' => 'article',
                'jsonLd' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'Article',
                    'headline' => $item['title'],
                    'description' => $item['summary'] ?? '',
                    'datePublished' => $artDate ? date('c', strtotime($artDate)) : null,
                    'dateModified' => $artMod ? date('c', strtotime($artMod)) : null,
                    'inLanguage' => 'tr',
                    'url' => current_canonical(),
                    'author' => ['@type' => 'Organization', 'name' => 'DiDn'],
                    'publisher' => ['@type' => 'Organization', 'name' => 'DiDn'],
                ],
            ]);
            exit;
        }
        http_response_code(404);
        render('notfound', ['title' => 'Bulunamadı']);
        exit;
    }
}

// Kelime sayfaları: /en/{kelime} (en->tr), /tr/{kelime} (tr->en)
if (count($segments) === 2 && ($segments[0] === 'en' || $segments[0] === 'tr')) {
    $dir    = $segments[0] === 'en' ? 'en-tr' : 'tr-en';
    $word   = $segments[1];
    $result = lookup_word($word, $dir);
    $result = with_example_translations($result);
    $wordDesc  = '“' . $word . '” kelimesinin ' . ($dir === 'en-tr' ? 'Türkçe' : 'İngilizce') . ' karşılığı, anlamı ve çevirisi — DiDn İngilizce ↔ Türkçe Sözlük.';
    $wordJsonLd = empty($result['error']) ? [
        '@context' => 'https://schema.org',
        '@type' => 'DefinedTerm',
        'name' => $word,
        'description' => $wordDesc,
        'url' => current_canonical(),
        'inLanguage' => $dir === 'en-tr' ? 'en' : 'tr',
        'inDefinedTermSet' => [
            '@type' => 'DefinedTermSet',
            'name' => 'DiDn İngilizce ↔ Türkçe Sözlük',
            'url' => site_base_url(),
        ],
    ] : null;
    render('word', [
        'result' => $result,
        'word'   => $word,
        'dir'    => $dir,
        'title'  => $word . ' — DiDn Sözlük',
        'description' => $wordDesc,
        'jsonLd' => $wordJsonLd,
    ]);
    exit;
}

// Bulunamadı
http_response_code(404);
render('notfound', ['title' => 'Sayfa bulunamadı — DiDn']);
