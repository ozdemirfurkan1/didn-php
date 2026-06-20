<?php
// Sunucu tarafı OpenAI istemcisi (cURL). Anahtar yalnızca burada okunur.
// Yalnızca metin üretimi — görsel üretimi yoktur.

declare(strict_types=1);

const OPENAI_BASE = 'https://api.openai.com/v1';

// Anahtarı ÜCRETSIZ doğrular (GET /models token harcamaz).
function openai_test_connection(): array
{
    $key = get_openai_key();
    if (!$key) {
        return ['ok' => false, 'error' => 'Anahtar tanımlı değil'];
    }
    $ch = curl_init(OPENAI_BASE . '/models');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $key"],
        CURLOPT_TIMEOUT        => 20,
    ]);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false) {
        return ['ok' => false, 'error' => "OpenAI'a bağlanılamadı"];
    }
    if ($code === 401) {
        return ['ok' => false, 'error' => 'Anahtar geçersiz (401)'];
    }
    if ($code < 200 || $code >= 300) {
        return ['ok' => false, 'error' => "OpenAI hatası: $code"];
    }
    return ['ok' => true];
}

// Konu + türe göre Türkçe içerik üretir.
// Dönüş: ['ok'=>true,'title'=>,'summary'=>,'body'=>] | ['ok'=>false,'error'=>]
function openai_generate_content(string $type, string $topic, string $instructions = ''): array
{
    $key = get_openai_key();
    if (!$key) {
        return ['ok' => false, 'error' => 'OpenAI anahtarı tanımlı değil.'];
    }
    $model     = get_openai_model();
    $typeLabel = CONTENT_TYPES[$type]['label'] ?? 'Blog';

    $system = 'Sen bir İngilizce–Türkçe sözlük ve İngilizce öğrenme sitesi için içerik üreten '
        . 'profesyonel bir Türkçe editörsün. Akıcı, doğru ve özgün Türkçe yazarsın. '
        . 'Çıktıyı YALNIZCA geçerli JSON olarak verirsin.';
    $userPrompt = "Tür: $typeLabel\nKonu: $topic\n"
        . ($instructions !== '' ? "Ek talimat: $instructions\n" : '')
        . "\nAşağıdaki JSON şemasında bir içerik üret:\n"
        . '{ "title": "...", "summary": "...", "body": "..." }' . "\n"
        . "- title: ilgi çekici, kısa bir başlık.\n"
        . "- summary: 1-2 cümlelik özet.\n"
        . "- body: Markdown biçiminde, ## ile alt başlıklar içeren doyurucu bir yazı.\n"
        . 'Tüm metin Türkçe olmalı.';

    $payload = json_encode([
        'model'           => $model,
        'messages'        => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $userPrompt],
        ],
        'response_format' => ['type' => 'json_object'],
        'temperature'     => 0.7,
        // Doyurucu bir Markdown gövdesi 2000 token'ı kolayca aşar; sınıra
        // takılırsa JSON ortadan kesilip geçersiz olur. Geniş tutuyoruz.
        'max_tokens'      => 4096,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init(OPENAI_BASE . '/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $key", 'Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 60,
    ]);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false) {
        return ['ok' => false, 'error' => "OpenAI'a bağlanılamadı."];
    }
    if ($code === 401) {
        return ['ok' => false, 'error' => 'OpenAI anahtarı geçersiz.'];
    }
    if ($code === 429) {
        return ['ok' => false, 'error' => 'OpenAI kotası/limiti doldu (429).'];
    }
    if ($code < 200 || $code >= 300) {
        return ['ok' => false, 'error' => "OpenAI hatası: $code"];
    }

    $data    = json_decode($resp, true);
    $content = $data['choices'][0]['message']['content'] ?? null;
    $finish  = $data['choices'][0]['finish_reason'] ?? null;
    if (!$content) {
        return ['ok' => false, 'error' => 'OpenAI boş yanıt döndü.'];
    }
    $parsed = json_decode($content, true);
    if (!is_array($parsed)) {
        // json_object modunda geçersiz JSON'ın tek nedeni çıktının token
        // sınırında kesilmesidir; bunu ayrı ve anlaşılır bir mesajla bildir.
        if ($finish === 'length') {
            return ['ok' => false, 'error' => 'İçerik çok uzun olduğu için yarıda kesildi (token sınırı). Daha kısa bir konu deneyin veya max_tokens sınırını artırın.'];
        }
        return ['ok' => false, 'error' => 'OpenAI geçerli JSON döndürmedi.'];
    }
    $title = trim($parsed['title'] ?? '');
    $body  = trim($parsed['body'] ?? '');
    if ($title === '' || $body === '') {
        return ['ok' => false, 'error' => 'Üretilen içerik eksik (başlık/gövde).'];
    }
    return ['ok' => true, 'title' => $title, 'summary' => trim($parsed['summary'] ?? ''), 'body' => $body];
}
