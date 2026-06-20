<?php
// Blog üretici — konu havuzundan üretim (elle + otomatik zamanlanmış).

declare(strict_types=1);

function bg_pick_topic(array $pool): ?string
{
    $items = array_values(array_filter(array_map('trim', $pool), fn($s) => $s !== ''));
    if (!$items) {
        return null;
    }
    return $items[random_int(0, count($items) - 1)];
}

// Konu havuzundan bir konuda blog üretip kaydeder.
// Dönüş: ['ok'=>true,'id'=>,'slug'=>,'title'=>,'topic'=>,'status'=>] | ['ok'=>false,'error'=>]
function run_blog_generation(string $mode): array
{
    $cfg   = get_blog_config();
    $topic = bg_pick_topic($cfg['topicPool']);
    if (!$topic) {
        return ['ok' => false, 'error' => 'Konu havuzu boş — önce en az bir konu ekleyin.'];
    }
    $gen = openai_generate_content('blog', $topic, $cfg['extraInstructions'] ?? '');
    if (!$gen['ok']) {
        return $gen;
    }
    $status = $mode === 'publish' ? 'published' : 'draft';
    $id = create_content([
        'type'       => 'blog',
        'title'      => $gen['title'],
        'slug'       => '',
        'summary'    => $gen['summary'],
        'body'       => $gen['body'],
        'coverImage' => '',
        'status'     => $status,
        'source'     => 'ai',
        'topic'      => $topic,
    ]);
    $item = get_content($id);
    set_blog_status(array_merge(get_blog_status(), [
        'lastAt'     => date('c'),
        'lastTopic'  => $topic,
        'lastId'     => $id,
        'lastSlug'   => $item['slug'] ?? '',
        'lastStatus' => $status,
        'lastError'  => null,
    ]));
    return ['ok' => true, 'id' => $id, 'slug' => $item['slug'] ?? '', 'title' => $gen['title'], 'topic' => $topic, 'status' => $status];
}

// Zamanlayıcı (cron) tarafından çağrılır. Açıksa, günde bir kez, gün içinde
// [en erken, en geç] aralığında rastgele bir saatte üretir.
function maybe_run_scheduled(): array
{
    $cfg = get_blog_config();
    if (empty($cfg['enabled'])) {
        return ['ran' => false, 'reason' => 'Otomatik üretim kapalı.'];
    }
    $today  = date('Y-m-d');
    $status = get_blog_status();
    if (($status['lastRunDate'] ?? null) === $today) {
        return ['ran' => false, 'reason' => 'Bugün zaten üretildi.'];
    }
    $targetHour = $status['targetHour'] ?? null;
    if (($status['targetDate'] ?? null) !== $today || $targetHour === null) {
        $lo = max(0, min(23, (int) $cfg['earliestHour']));
        $hi = max($lo, min(23, (int) $cfg['latestHour']));
        $targetHour = random_int($lo, $hi);
        set_blog_status(array_merge($status, ['targetDate' => $today, 'targetHour' => $targetHour]));
    }
    if ((int) date('G') < $targetHour) {
        return ['ran' => false, 'reason' => "Bugünün hedef saati $targetHour:00, henüz erken."];
    }
    $r = run_blog_generation($cfg['publishMode']);
    if (!$r['ok']) {
        set_blog_status(array_merge(get_blog_status(), ['lastError' => $r['error'], 'lastErrorAt' => date('c')]));
        return ['ran' => false, 'error' => $r['error']];
    }
    set_blog_status(array_merge(get_blog_status(), ['lastRunDate' => $today]));
    return ['ran' => true, 'reason' => 'Üretildi.', 'result' => $r];
}
