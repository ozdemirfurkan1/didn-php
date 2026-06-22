<?php
// app_setting tablosu + gizli değer şifreleme (AES-256-GCM) + OpenAI ayarları.

declare(strict_types=1);

function settings_key(): string
{
    $c = config();
    $s = !empty($c['settings_secret']) ? $c['settings_secret'] : ($c['session_secret'] ?? '');
    if ($s === '') {
        throw new RuntimeException('settings_secret/session_secret tanımlı değil');
    }
    return hash('sha256', $s, true); // 32 bayt
}

function encrypt_secret(string $plain): string
{
    $iv  = random_bytes(12);
    $tag = '';
    $ct  = openssl_encrypt($plain, 'aes-256-gcm', settings_key(), OPENSSL_RAW_DATA, $iv, $tag);
    return bin2hex($iv) . ':' . bin2hex($tag) . ':' . bin2hex($ct);
}

function decrypt_secret(string $stored): ?string
{
    $parts = explode(':', $stored);
    if (count($parts) !== 3) {
        return null;
    }
    [$iv, $tag, $ct] = array_map('hex2bin', $parts);
    $pt = openssl_decrypt($ct, 'aes-256-gcm', settings_key(), OPENSSL_RAW_DATA, $iv, $tag);
    return $pt === false ? null : $pt;
}

// --- Anahtar/değer ---------------------------------------------------------

function get_setting(string $key): ?string
{
    $stmt = db()->prepare("SELECT `value` FROM app_setting WHERE `key` = :k");
    $stmt->execute([':k' => $key]);
    $r = $stmt->fetch();
    return $r ? $r['value'] : null;
}

function set_setting(string $key, string $value): void
{
    $stmt = db()->prepare(
        "INSERT INTO app_setting (`key`, `value`) VALUES (:k, :v)
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)"
    );
    $stmt->execute([':k' => $key, ':v' => $value]);
}

// --- OpenAI ----------------------------------------------------------------

function set_openai_key(string $plain): void
{
    set_setting('openai_api_key', encrypt_secret(trim($plain)));
}

function get_openai_key(): ?string
{
    $v = get_setting('openai_api_key');
    if (!$v) {
        return null;
    }
    return decrypt_secret($v);
}

function is_openai_configured(): bool
{
    return get_setting('openai_api_key') !== null;
}

function get_openai_model(): string
{
    return get_setting('openai_model') ?: (config()['openai_default_model'] ?? 'gpt-4o-mini');
}

function set_openai_model(string $model): void
{
    set_setting('openai_model', $model);
}

const OPENAI_ALLOWED_MODELS = ['gpt-4o-mini', 'gpt-4o'];

// --- İçerik üretici yapılandırması (türe göre: blog/rehber/haber) ----------

// Ayar/durum anahtarları. Blog için eski anahtarlar korunur (geriye uyumluluk).
function gen_config_key(string $type): string
{
    return $type === 'blog' ? 'blog_generator' : $type . '_generator';
}

function gen_status_key(string $type): string
{
    return $type === 'blog' ? 'blog_generator_status' : $type . '_generator_status';
}

function default_generator_config(): array
{
    return [
        'enabled'           => false,
        'publishMode'       => 'draft',
        'earliestHour'      => 9,
        'latestHour'        => 18,
        'topicPool'         => [],
        'extraInstructions' => '',
    ];
}

function get_generator_config(string $type): array
{
    $v = get_setting(gen_config_key($type));
    if (!$v) {
        return default_generator_config();
    }
    $parsed = json_decode($v, true);
    return is_array($parsed) ? array_merge(default_generator_config(), $parsed) : default_generator_config();
}

function set_generator_config(string $type, array $cfg): void
{
    set_setting(gen_config_key($type), json_encode($cfg, JSON_UNESCAPED_UNICODE));
}

function get_generator_status(string $type): array
{
    $v = get_setting(gen_status_key($type));
    if (!$v) {
        return [];
    }
    $parsed = json_decode($v, true);
    return is_array($parsed) ? $parsed : [];
}

function set_generator_status(string $type, array $status): void
{
    set_setting(gen_status_key($type), json_encode($status, JSON_UNESCAPED_UNICODE));
}

// Blog'a özel kısayollar (eski çağrılarla uyumluluk için).
function default_blog_config(): array { return default_generator_config(); }
function get_blog_config(): array { return get_generator_config('blog'); }
function set_blog_config(array $cfg): void { set_generator_config('blog', $cfg); }
function get_blog_status(): array { return get_generator_status('blog'); }
function set_blog_status(array $status): void { set_generator_status('blog', $status); }
