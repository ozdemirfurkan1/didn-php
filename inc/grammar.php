<?php
// Gramer dersleri — data/grammar.json'dan yüklenir (Next.js lib/grammar'dan
// aktarıldı). Sıra ve kategori gruplaması orijinaldeki gibidir.

declare(strict_types=1);

function all_lessons(): array
{
    static $lessons = null;
    if ($lessons === null) {
        $json = @file_get_contents(__DIR__ . '/../data/grammar.json');
        $lessons = $json ? (json_decode($json, true) ?: []) : [];
        // Orijinal (lib/grammar/index.ts) sırası.
        $order = [
            'present-simple', 'present-continuous', 'past-simple', 'past-continuous',
            'present-perfect', 'past-perfect', 'future-will-going-to',
            'articles', 'pronouns', 'plurals-quantifiers', 'there-is-there-are',
            'prepositions-time-place', 'question-words',
            'can-could', 'must-have-to', 'should',
            'comparatives-superlatives',
            'conditionals', 'relative-clauses', 'reported-speech',
            'gerunds-infinitives', 'passive-voice',
        ];
        $rank = array_flip($order);
        usort($lessons, fn($a, $b) => ($rank[$a['slug']] ?? 999) <=> ($rank[$b['slug']] ?? 999));
    }
    return $lessons;
}

function get_lesson(string $slug): ?array
{
    foreach (all_lessons() as $lesson) {
        if ($lesson['slug'] === $slug) {
            return $lesson;
        }
    }
    return null;
}

// Dersleri kategoriye göre gruplar (ilk görülme sırasıyla).
function lessons_by_category(): array
{
    $groups = [];
    foreach (all_lessons() as $lesson) {
        $groups[$lesson['category']][] = $lesson;
    }
    return $groups;
}
