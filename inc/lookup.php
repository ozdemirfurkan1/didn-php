<?php
// Sözlük arama mantığı — Next.js sürümündeki lib/lookup.ts + lib/apis/db.ts +
// lib/apis/details.ts'in PHP karşılığı. Tek veri kaynağı: yerel MySQL.

declare(strict_types=1);

const LOOKUP_MAX_MEANINGS = 30;
const LOOKUP_MAX_RELATED  = 20;

// translate tablosunun sırası alakaya göre değil; karşılıkları puanlayıp en
// olası olanı öne alıyoruz. Hedef İngilizce olduğunda özel isim/kısaltma
// adaylarını geriye iteriz.
function score_entry(array $entry, string $targetLang): int
{
    $score = 0;
    if (mb_strtolower($entry['category'], 'UTF-8') === 'common usage') {
        $score += 10;
    }
    if ($targetLang === 'en') {
        $w = $entry['word'];
        if (preg_match('/^[a-z]/', $w)) {
            $score += 3;
        } else {
            $score -= 3; // büyük harfle başlayan: özel isim/kısaltma olabilir
        }
        if (strpos($w, '.') !== false) {
            $score -= 5; // "Ave.", "Brit." gibi kısaltmalar
        }
        if (preg_match('/^[A-Z.]+$/', $w)) {
            $score -= 3; // tamamı büyük harf
        }
    }
    return $score;
}

// Verilen yöne göre kaynak kelimenin tüm karşılıklarını tür + kategori ile getirir.
// Eşleşme, kolonların utf8mb4_turkish_ci collation'ı sayesinde büyük/küçük
// harf duyarsızdır.
function find_translations(string $word, string $direction): array
{
    $q = trim($word);
    if ($q === '') {
        return ['headword' => null, 'entries' => []];
    }

    if ($direction === 'en-tr') {
        $sql = "SELECT e.word AS source_word, tr.word AS target_word, ty.name AS type, c.name AS category
                FROM translate t
                JOIN english e  ON e.id  = t.english_id
                JOIN turkish tr ON tr.id = t.turkish_id
                LEFT JOIN type ty     ON ty.id = t.type_id
                LEFT JOIN category c  ON c.id  = t.category_id
                WHERE e.word = :q
                ORDER BY t.id ASC LIMIT 300";
    } else {
        $sql = "SELECT tr.word AS source_word, e.word AS target_word, ty.name AS type, c.name AS category
                FROM translate t
                JOIN english e  ON e.id  = t.english_id
                JOIN turkish tr ON tr.id = t.turkish_id
                LEFT JOIN type ty     ON ty.id = t.type_id
                LEFT JOIN category c  ON c.id  = t.category_id
                WHERE tr.word = :q
                ORDER BY t.id ASC LIMIT 300";
    }

    $stmt = db()->prepare($sql);
    $stmt->execute([':q' => $q]);
    $rows = $stmt->fetchAll();

    $headword = null;
    $entries  = [];
    $seen     = [];

    foreach ($rows as $row) {
        $target = trim((string) ($row['target_word'] ?? ''));
        if ($target === '') {
            continue;
        }
        if ($headword === null) {
            $src = trim((string) ($row['source_word'] ?? ''));
            if ($src !== '') {
                $headword = $src;
            }
        }
        $type     = trim((string) ($row['type'] ?? ''));
        $category = trim((string) ($row['category'] ?? ''));

        // Aynı kelime + tür + kategori kombinasyonunu tekrarlama.
        $key = mb_strtolower($target, 'UTF-8') . '|' . $type . '|' . $category;
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $entries[] = ['word' => $target, 'type' => $type, 'category' => $category];
    }

    // Alakaya göre sırala (PHP 8 usort kararlıdır → orijinal sırayı korur).
    $targetLang = $direction === 'en-tr' ? 'tr' : 'en';
    usort($entries, fn($a, $b) => score_entry($b, $targetLang) - score_entry($a, $targetLang));

    // İkincil dedup: aynı kelime + tür için en iyi puanlıyı tut.
    $finalSeen = [];
    $ranked    = [];
    foreach ($entries as $entry) {
        $k = mb_strtolower($entry['word'], 'UTF-8') . '|' . $entry['type'];
        if (isset($finalSeen[$k])) {
            continue;
        }
        $finalSeen[$k] = true;
        $ranked[] = $entry;
    }

    return ['headword' => $headword, 'entries' => $ranked];
}

// --- Dil yönü otomatik algılama --------------------------------------------

// Verilen kelime ilgili dilin sözlüğünde başlık olarak var mı? Kolonların
// utf8mb4_turkish_ci collation'ı sayesinde eşleşme büyük/küçük harf duyarsız.
function word_exists(string $lang, string $word): bool
{
    $w = trim($word);
    if ($w === '') {
        return false;
    }
    $table = $lang === 'tr' ? 'turkish' : 'english';
    $stmt  = db()->prepare("SELECT 1 FROM `$table` WHERE word = :w LIMIT 1");
    $stmt->execute([':w' => $w]);
    return (bool) $stmt->fetchColumn();
}

// Kelimeye bakıp arama yönünü ('en-tr' / 'tr-en') tahmin eder.
// Önce sözlükte hangi dilde karşılığı olduğuna bakar; kararsız kalırsa
// Türkçeye özgü harfleri ipucu olarak kullanır.
function detect_direction(string $word): string
{
    $w = trim($word);
    if ($w === '') {
        return 'en-tr';
    }
    $hasEn = word_exists('en', $w);
    $hasTr = word_exists('tr', $w);
    if ($hasEn && !$hasTr) {
        return 'en-tr';
    }
    if ($hasTr && !$hasEn) {
        return 'tr-en';
    }
    // İkisinde de var ya da ikisinde de yok: Türkçe karakter varsa TR kabul et.
    if (preg_match('/[çşğıöüÇŞĞİÖÜ]/u', $w)) {
        return 'tr-en';
    }
    return 'en-tr';
}

// --- Otomatik tamamlama önerileri ------------------------------------------

// 'q' önekiyle başlayan kelimeleri english + turkish tablolarından getirir.
// Her öneri kendi dilini taşır ki tıklanınca doğru URL'ye (/en/ veya /tr/) gidilsin.
function suggest_words(string $q, int $limit = 8): array
{
    $q = trim($q);
    if (mb_strlen($q, 'UTF-8') < 2) {
        return [];
    }
    $limit = max(1, min(20, $limit));
    // LIKE özel karakterlerini kaçır (% ve _ ile sorgu yapılmasını engelle).
    $like = addcslashes($q, '%_\\') . '%';

    $rows = [];
    foreach (['en' => 'english', 'tr' => 'turkish'] as $lang => $table) {
        // Önce tam önek eşleşmeleri, kısa kelimeler önce (daha alakalı).
        $sql  = "SELECT DISTINCT word FROM `$table`
                 WHERE word LIKE :p AND word <> '' AND word NOT LIKE '%/%'
                 ORDER BY CHAR_LENGTH(word) ASC, word ASC
                 LIMIT " . ($limit + 4);
        $stmt = db()->prepare($sql);
        $stmt->execute([':p' => $like]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $w) {
            $rows[] = ['word' => $w, 'lang' => $lang];
        }
    }

    // İki dildeki sonuçları birleştirip uzunluğa göre sırala, tekilleştir.
    usort($rows, fn($a, $b) => mb_strlen($a['word'], 'UTF-8') - mb_strlen($b['word'], 'UTF-8'));
    $seen = [];
    $out  = [];
    foreach ($rows as $r) {
        $k = mb_strtolower($r['word'], 'UTF-8') . '|' . $r['lang'];
        if (isset($seen[$k])) {
            continue;
        }
        $seen[$k] = true;
        $out[]    = $r;
        if (count($out) >= $limit) {
            break;
        }
    }
    return $out;
}

// Kaikki/Wiktionary tablolarından (word_details/sense/relation) bir İngilizce
// kelimenin tanım, telaffuz, örnek ve eş/zıt anlamlarını toplar.
function get_english_details(string $word): ?array
{
    $w = mb_strtolower(trim($word), 'UTF-8');
    if ($w === '') {
        return null;
    }

    $stmt = db()->prepare(
        "SELECT id, word, pos, ipa, audio_url FROM word_details WHERE word_lower = :w LIMIT 12"
    );
    $stmt->execute([':w' => $w]);
    $details = $stmt->fetchAll();
    if (!$details) {
        return null;
    }

    $phonetic  = null;
    $audioUrl  = null;
    $canonical = $word;
    $meanings  = [];
    $synonyms  = [];
    $antonyms  = [];

    $senseStmt = db()->prepare("SELECT definition, example FROM sense WHERE word_detail_id = :id");
    $relStmt   = db()->prepare("SELECT kind, word FROM relation WHERE word_detail_id = :id");

    foreach ($details as $d) {
        if (!$phonetic && !empty($d['ipa'])) {
            $phonetic = $d['ipa'];
        }
        if (!$audioUrl && !empty($d['audio_url'])) {
            $audioUrl = $d['audio_url'];
        }
        if (!empty($d['word'])) {
            $canonical = $d['word'];
        }

        $senseStmt->execute([':id' => $d['id']]);
        foreach ($senseStmt->fetchAll() as $s) {
            $meanings[] = [
                'pos'        => $d['pos'] ?? '',
                'definition' => $s['definition'],
                'example'    => $s['example'] ?? null,
            ];
        }

        $relStmt->execute([':id' => $d['id']]);
        foreach ($relStmt->fetchAll() as $r) {
            if (mb_strtolower($r['word'], 'UTF-8') === $w) {
                continue; // kelimenin kendisini atla
            }
            if ($r['kind'] === 'synonym') {
                $synonyms[$r['word']] = true;
            } elseif ($r['kind'] === 'antonym') {
                $antonyms[$r['word']] = true;
            }
        }
    }

    return [
        'word'     => $canonical,
        'phonetic' => $phonetic,
        'audioUrl' => $audioUrl,
        'meanings' => array_slice($meanings, 0, LOOKUP_MAX_MEANINGS),
        'synonyms' => array_slice(array_keys($synonyms), 0, LOOKUP_MAX_RELATED),
        'antonyms' => array_slice(array_keys($antonyms), 0, LOOKUP_MAX_RELATED),
    ];
}

// Tek giriş noktası: hem arama hem kelime sayfası bunu kullanır.
// Dönüş ya sonuç dizisidir ya da ['error' => ...] hatasıdır (is_lookup_error).
function lookup_word(string $word, string $direction): array
{
    $q = trim($word);
    if ($q === '') {
        return ['error' => 'Kelime giriniz', 'query' => $word, 'direction' => $direction];
    }

    try {
        if ($direction === 'en-tr') {
            $t       = find_translations($q, 'en-tr');
            $details = get_english_details($q);
            if (count($t['entries']) === 0 && !$details) {
                return ['error' => 'Kelime bulunamadı', 'query' => $q, 'direction' => 'en-tr'];
            }
            $headword = $details['word'] ?? $t['headword'] ?? $q;
        } else {
            $t = find_translations($q, 'tr-en');
            if (count($t['entries']) === 0) {
                return ['error' => 'Kelime bulunamadı', 'query' => $q, 'direction' => 'tr-en'];
            }
            // Sıralı adaylardan Kaikki'de detayı bulunan ilkini seç (en fazla 6 dene).
            $details = null;
            $seen    = [];
            foreach ($t['entries'] as $entry) {
                $cand = mb_strtolower($entry['word'], 'UTF-8');
                if (isset($seen[$cand])) {
                    continue;
                }
                $seen[$cand] = true;
                if (count($seen) > 6) {
                    break;
                }
                $details = get_english_details($entry['word']);
                if ($details) {
                    break;
                }
            }
            $headword = $details['word'] ?? $t['entries'][0]['word'];
        }
    } catch (Throwable $ex) {
        return [
            'error'     => 'Sözlük şu an kullanılamıyor, lütfen tekrar deneyin',
            'query'     => $q,
            'direction' => $direction,
        ];
    }

    return [
        'query'       => $q,
        'direction'   => $direction,
        'headword'    => $headword,
        'translations'=> $t['entries'],
        'phonetic'    => $details['phonetic'] ?? null,
        'audioUrl'    => $details['audioUrl'] ?? null,
        'meanings'    => $details['meanings'] ?? [],
        'synonyms'    => $details['synonyms'] ?? [],
        'antonyms'    => $details['antonyms'] ?? [],
    ];
}

function is_lookup_error(array $result): bool
{
    return isset($result['error']);
}

// Rastgele, çevirisi olan tek bir İngilizce kelime seçip tam sonucunu döndürür.
// BINARY ... REGEXP ile yalnızca küçük harfli, tek sözcüklü adaylar seçilir
// (özel isim/kısaltma elenir). Birkaç deneme yapar.
function pick_random_word_for_day(): ?array
{
    $fallback = null;
    for ($i = 0; $i < 14; $i++) {
        $stmt = db()->query(
            "SELECT word FROM english WHERE BINARY word REGEXP '^[a-z]{3,15}$' ORDER BY RAND() LIMIT 1"
        );
        $w = $stmt ? (string) $stmt->fetchColumn() : '';
        if ($w === '') {
            continue;
        }
        $res = lookup_word($w, 'en-tr');
        if (is_lookup_error($res) || empty($res['translations'])) {
            continue;
        }
        if ($fallback === null) {
            $fallback = $res; // geçerli ilk adayı yedek tut
        }
        if (!empty($res['audioUrl'])) {
            return $res; // sesi olan kelimeyi tercih et
        }
    }
    return $fallback;
}

// Günün kelimesi: gün içinde değişmez, her gün yenilenir. Seçilen kelime
// app_setting'te (word_of_the_day) tarih damgasıyla saklanır; aynı gün tekrar
// sorgu yapılmaz. DB erişilemezse null döner (ana sayfa bozulmaz).
function get_word_of_the_day(): ?array
{
    $today = date('Y-m-d');
    // Önbellek anahtarına sürüm ekli; payload yapısı değişince eski kayıt otomatik yenilenir.
    $cacheKey = 'word_of_the_day_v2';
    try {
        $stored = function_exists('get_setting') ? get_setting($cacheKey) : null;
        if ($stored) {
            $p = json_decode($stored, true);
            if (is_array($p) && ($p['date'] ?? null) === $today && !empty($p['word'])) {
                return $p['word'];
            }
        }
        $res = pick_random_word_for_day();
        if (!$res) {
            return null;
        }
        $payload = [
            'headword'     => $res['headword'],
            'query'        => $res['query'],
            'phonetic'     => $res['phonetic'] ?? null,
            'audioUrl'     => $res['audioUrl'] ?? null,
            'translations' => array_slice($res['translations'], 0, 6),
            'meaning'      => $res['meanings'][0] ?? null,
            'synonyms'     => array_slice($res['synonyms'] ?? [], 0, 8),
            'antonyms'     => array_slice($res['antonyms'] ?? [], 0, 8),
        ];
        set_setting($cacheKey, json_encode(['date' => $today, 'word' => $payload], JSON_UNESCAPED_UNICODE));
        return $payload;
    } catch (Throwable $e) {
        return null;
    }
}

// --- Örnek cümle çevirileri (önbellekli) -----------------------------------

function _example_cache_key(string $text): string
{
    return 'trex:' . md5(mb_strtolower(trim($text), 'UTF-8'));
}

// Sonuçtaki İngilizce örnek cümlelere Türkçe çeviri (example_tr) ekler.
// Önbellekteki çeviriler hep gösterilir; yeni çeviri yalnızca gerçek
// kullanıcıya ve istek başına sınırlı sayıda üretilir (maliyet kontrolü).
function with_example_translations(array $result): array
{
    if (empty($result['meanings']) || !is_array($result['meanings'])) {
        return $result;
    }
    $budget   = 3;                                   // bu istekte en fazla yeni çeviri
    $canMake  = !is_bot() && is_openai_configured(); // bot değilse ve anahtar varsa
    foreach ($result['meanings'] as &$m) {
        $ex = trim((string) ($m['example'] ?? ''));
        if ($ex === '') {
            continue;
        }
        $key    = _example_cache_key($ex);
        $cached = get_setting($key);
        if ($cached !== null) {
            $m['example_tr'] = $cached;
            continue;
        }
        if ($budget <= 0 || !$canMake) {
            continue;
        }
        $tr = openai_translate_tr($ex);
        if ($tr !== null && $tr !== '') {
            set_setting($key, $tr);
            $m['example_tr'] = $tr;
            $budget--;
        }
    }
    unset($m);
    return $result;
}

// Çevirileri part-of-speech'e göre gruplar (Tureng tarzı görünüm için).
function group_translations_by_type(array $entries): array
{
    $groups = [];
    foreach ($entries as $entry) {
        $label = translate_pos($entry['type']);
        $groups[$label][] = $entry;
    }
    return $groups;
}

// --- Sitemap için sözlük kelime listesi ------------------------------------
// Yalnızca en az bir çevirisi olan ve tek URL segmentine sığan kelimeler.

function dictionary_word_count(string $lang): int
{
    if ($lang === 'tr') {
        $sql = "SELECT COUNT(DISTINCT tr.word) FROM turkish tr
                JOIN translate t ON t.turkish_id = tr.id
                WHERE tr.word <> '' AND tr.word NOT LIKE '%/%'";
    } else {
        $sql = "SELECT COUNT(DISTINCT e.word) FROM english e
                JOIN translate t ON t.english_id = e.id
                WHERE e.word <> '' AND e.word NOT LIKE '%/%'";
    }
    return (int) db()->query($sql)->fetchColumn();
}

function dictionary_words(string $lang, int $offset, int $limit): array
{
    $offset = max(0, $offset);
    $limit  = max(1, $limit);
    if ($lang === 'tr') {
        $sql = "SELECT DISTINCT tr.word AS word FROM turkish tr
                JOIN translate t ON t.turkish_id = tr.id
                WHERE tr.word <> '' AND tr.word NOT LIKE '%/%'
                ORDER BY tr.word LIMIT $offset, $limit";
    } else {
        $sql = "SELECT DISTINCT e.word AS word FROM english e
                JOIN translate t ON t.english_id = e.id
                WHERE e.word <> '' AND e.word NOT LIKE '%/%'
                ORDER BY e.word LIMIT $offset, $limit";
    }
    return array_map(fn($r) => (string) $r['word'], db()->query($sql)->fetchAll());
}
