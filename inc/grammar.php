<?php
// Gramer dersleri — dil "track"lerine göre data/*.json'dan yüklenir.
// Her track: hangi veri dosyası, örnek cümlede öğrenilen dil (target) ve
// açıklama/çeviri dili (native), rota öneki ve görünüm etiketleri.

declare(strict_types=1);

// Tüm gramer track'leri. Yeni bir dil eklemek için buraya bir kayıt ve
// ona ait data/grammar-XX.json dosyasını eklemek yeterli.
function grammar_tracks(): array
{
    return [
        // Türkçe konuşan → İngilizce öğrenir (mevcut, /gramer).
        'en' => [
            'key'    => 'en',
            'file'   => 'grammar.json',
            'target' => 'en', // örnek cümlede öğrenilen dil
            'native' => 'tr', // açıklama/çeviri dili
            'base'   => '/gramer',
            // Orijinal (lib/grammar/index.ts) sırası.
            'order'  => [
                'present-simple', 'present-continuous', 'past-simple', 'past-continuous',
                'present-perfect', 'past-perfect', 'future-will-going-to',
                'articles', 'pronouns', 'plurals-quantifiers', 'there-is-there-are',
                'prepositions-time-place', 'question-words',
                'can-could', 'must-have-to', 'should',
                'comparatives-superlatives',
                'conditionals', 'relative-clauses', 'reported-speech',
                'gerunds-infinitives', 'passive-voice',
            ],
            'labels' => [
                'indexTitle' => 'Gramer Konuları',
                'indexSub'   => 'İngilizce gramer konuları — sade Türkçe anlatım, kurallar, örnek cümleler ve sık yapılan hatalarla.',
                'pageIndex'  => 'Gramer Konuları — DiDn',
                'back'       => '← Gramer Konuları',
                'structure'  => 'Yapı:',
                'mistakes'   => 'Sık yapılan hatalar',
                'related'    => 'İlgili konular',
                'quiz'       => 'Mini test',
                'quizDone'   => 'Bitti! Soruları yanıtladın.',
                'formTable'  => 'Çekim tablosu',
                'prev'       => 'Önceki',
                'next'       => 'Sonraki',
                'signalWords' => 'Sinyal kelimeler',
                'faq'        => 'Sık sorulan sorular',
            ],
        ],
        // İngilizce konuşan → İspanyolca öğrenir (yeni, /es/grammar).
        'es' => [
            'key'    => 'es',
            'file'   => 'grammar-es.json',
            'target' => 'es', // örnek cümlede öğrenilen dil (İspanyolca)
            'native' => 'en', // açıklama/çeviri dili (İngilizce)
            'base'   => '/es/grammar',
            'order'  => [], // dosya sırası kullanılır
            'labels' => [
                'indexTitle' => 'Spanish Grammar',
                'indexSub'   => 'Spanish grammar for English speakers — clear explanations, rules, example sentences and common mistakes.',
                'pageIndex'  => 'Spanish Grammar — DiDn',
                'back'       => '← Spanish Grammar',
                'structure'  => 'Structure:',
                'mistakes'   => 'Common mistakes',
                'related'    => 'Related topics',
                'quiz'       => 'Quick quiz',
                'quizDone'   => 'Done! You answered all questions.',
                'formTable'  => 'Forms at a glance',
                'prev'       => 'Previous',
                'next'       => 'Next',
                'signalWords' => 'Signal words',
                'faq'        => 'Frequently asked questions',
            ],
        ],
    ];
}

// Bir track'in tanımını döndürür (yoksa null).
function grammar_track(string $key): ?array
{
    return grammar_tracks()[$key] ?? null;
}

function all_lessons(string $track = 'en'): array
{
    static $cache = [];
    if (!array_key_exists($track, $cache)) {
        $t = grammar_track($track);
        $lessons = [];
        if ($t) {
            $json = @file_get_contents(__DIR__ . '/../data/' . $t['file']);
            $lessons = $json ? (json_decode($json, true) ?: []) : [];
            if (!empty($t['order'])) {
                $rank = array_flip($t['order']);
                usort($lessons, fn($a, $b) => ($rank[$a['slug']] ?? 999) <=> ($rank[$b['slug']] ?? 999));
            }
        }
        $cache[$track] = $lessons;
    }
    return $cache[$track];
}

function get_lesson(string $slug, string $track = 'en'): ?array
{
    foreach (all_lessons($track) as $lesson) {
        if ($lesson['slug'] === $slug) {
            return $lesson;
        }
    }
    return null;
}

// Bir part-of-speech için ilgili İngilizce gramer dersini döndürür (varsa).
// İç linkleme: kelime sayfasından konu anlatımına köprü kurar.
function pos_grammar_lesson(string $pos): ?array
{
    static $map = [
        'verb'        => 'present-simple',
        'noun'        => 'plurals-quantifiers',
        'adjective'   => 'comparatives-superlatives',
        'adj'         => 'comparatives-superlatives',
        'adverb'      => 'comparatives-superlatives',
        'adv'         => 'comparatives-superlatives',
        'pronoun'     => 'pronouns',
        'pron'        => 'pronouns',
        'preposition' => 'prepositions-time-place',
        'prep'        => 'prepositions-time-place',
        'article'     => 'articles',
        'determiner'  => 'articles',
        'det'         => 'articles',
    ];
    $key = mb_strtolower(trim($pos), 'UTF-8');
    if (!isset($map[$key])) {
        return null;
    }
    $lesson = get_lesson($map[$key], 'en');
    return $lesson ? ['slug' => $lesson['slug'], 'title' => $lesson['title']] : null;
}

// Dersleri kategoriye göre gruplar (ilk görülme sırasıyla).
function lessons_by_category(string $track = 'en'): array
{
    $groups = [];
    foreach (all_lessons($track) as $lesson) {
        $groups[$lesson['category']][] = $lesson;
    }
    return $groups;
}
