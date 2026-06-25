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
