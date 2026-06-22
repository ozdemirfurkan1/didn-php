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

// Otomatik üretilebilen içerik türleri (blog, rehber, haber).
function generatable_types(): array
{
    return array_keys(CONTENT_TYPES);
}

// Konu havuzundan bir konuda verilen türde içerik üretip kaydeder.
// Dönüş: ['ok'=>true,'id'=>,'slug'=>,'title'=>,'topic'=>,'status'=>,'type'=>] | ['ok'=>false,'error'=>]
function run_content_generation(string $type, string $mode): array
{
    if (!is_content_type($type)) {
        return ['ok' => false, 'error' => 'Geçersiz içerik türü: ' . $type];
    }
    $cfg   = get_generator_config($type);
    $topic = bg_pick_topic($cfg['topicPool']);
    if (!$topic) {
        return ['ok' => false, 'error' => 'Konu havuzu boş — önce en az bir konu ekleyin.'];
    }
    $gen = openai_generate_content($type, $topic, $cfg['extraInstructions'] ?? '');
    if (!$gen['ok']) {
        return $gen;
    }
    $status = $mode === 'publish' ? 'published' : 'draft';
    $id = create_content([
        'type'       => $type,
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
    set_generator_status($type, array_merge(get_generator_status($type), [
        'lastAt'     => date('c'),
        'lastTopic'  => $topic,
        'lastId'     => $id,
        'lastSlug'   => $item['slug'] ?? '',
        'lastStatus' => $status,
        'lastError'  => null,
    ]));
    return ['ok' => true, 'id' => $id, 'slug' => $item['slug'] ?? '', 'title' => $gen['title'], 'topic' => $topic, 'status' => $status, 'type' => $type];
}

// Blog'a özel kısayol (eski çağrılarla uyumluluk için).
function run_blog_generation(string $mode): array
{
    return run_content_generation('blog', $mode);
}

// Tek bir tür için zamanlanmış üretim kontrolü. Açıksa, günde bir kez, gün
// içinde [en erken, en geç] aralığında rastgele bir saatte üretir.
function maybe_run_scheduled_for(string $type): array
{
    $cfg = get_generator_config($type);
    if (empty($cfg['enabled'])) {
        return ['type' => $type, 'ran' => false, 'reason' => 'Otomatik üretim kapalı.'];
    }
    $today  = date('Y-m-d');
    $status = get_generator_status($type);
    if (($status['lastRunDate'] ?? null) === $today) {
        return ['type' => $type, 'ran' => false, 'reason' => 'Bugün zaten üretildi.'];
    }
    $targetHour = $status['targetHour'] ?? null;
    if (($status['targetDate'] ?? null) !== $today || $targetHour === null) {
        $lo = max(0, min(23, (int) $cfg['earliestHour']));
        $hi = max($lo, min(23, (int) $cfg['latestHour']));
        $targetHour = random_int($lo, $hi);
        set_generator_status($type, array_merge($status, ['targetDate' => $today, 'targetHour' => $targetHour]));
    }
    if ((int) date('G') < $targetHour) {
        return ['type' => $type, 'ran' => false, 'reason' => "Bugünün hedef saati $targetHour:00, henüz erken."];
    }
    $r = run_content_generation($type, $cfg['publishMode']);
    if (!$r['ok']) {
        set_generator_status($type, array_merge(get_generator_status($type), ['lastError' => $r['error'], 'lastErrorAt' => date('c')]));
        return ['type' => $type, 'ran' => false, 'error' => $r['error']];
    }
    set_generator_status($type, array_merge(get_generator_status($type), ['lastRunDate' => $today]));
    return ['type' => $type, 'ran' => true, 'reason' => 'Üretildi.', 'result' => $r];
}

// Zamanlayıcı (cron) tarafından çağrılır. Tüm türleri kontrol eder; her açık
// tür için günde bir kez, kendi hedef saatinde üretir.
function maybe_run_scheduled(): array
{
    $results = [];
    foreach (generatable_types() as $type) {
        $results[$type] = maybe_run_scheduled_for($type);
    }
    return ['checked' => date('c'), 'results' => $results];
}
