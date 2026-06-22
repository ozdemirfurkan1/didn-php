<?php
declare(strict_types=1);
/** @var array $config */
/** @var array $status */
/** @var bool $cronConfigured */
/** @var string $type */
/** @var string $typeLabel */
/** @var string $genBase */
$pool  = implode("\n", $config['topicPool'] ?? []);
$lower = mb_strtolower($typeLabel, 'UTF-8');
?>
<a href="/admin" class="back-link">← Yönetim Paneli</a>
<h1 class="admin-h1"><?= e($typeLabel) ?> Üretici</h1>
<p class="admin-sub">Konu havuzundaki bir konuda yapay zekâ ile <?= e($lower) ?> üretir. Elle veya otomatik.</p>
<?php include __DIR__ . '/_flash.php'; ?>

<section class="card settings-section">
    <h2>Otomatik üretim</h2>
    <p class="settings-desc">Her gün, gün içinde rastgele bir saatte, konu havuzundaki bir konuda <?= e($lower) ?> üretir.</p>
    <form method="post" action="<?= e($genBase) ?>" class="settings-form">
        <?= csrf_field() ?>
        <label class="check-row">
            <input type="checkbox" name="enabled" value="1" <?= !empty($config['enabled']) ? 'checked' : '' ?>>
            Günlük otomatik üretim açık
        </label>
        <label>Yayın modu
            <select name="publishMode">
                <option value="draft" <?= ($config['publishMode'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Taslak üret (ben yayınlarım)</option>
                <option value="publish" <?= ($config['publishMode'] ?? '') === 'publish' ? 'selected' : '' ?>>Doğrudan yayınla (SEO için)</option>
            </select>
        </label>
        <div class="hour-row">
            <label>En erken saat
                <input type="number" name="earliestHour" min="0" max="23" value="<?= (int) ($config['earliestHour'] ?? 9) ?>">
            </label>
            <label>En geç saat
                <input type="number" name="latestHour" min="0" max="23" value="<?= (int) ($config['latestHour'] ?? 18) ?>">
            </label>
        </div>
        <label>Konu havuzu (her satır bir tema)
            <textarea name="topicPool" rows="6" class="mono" placeholder="İngilizce zamanları özet&#10;En sık kullanılan 50 İngilizce kelime"><?= e($pool) ?></textarea>
        </label>
        <label>Ek talimat (opsiyonel)
            <textarea name="extraInstructions" rows="2" placeholder="ör. başlangıç seviyesine uygun, bol örnekli"><?= e($config['extraInstructions'] ?? '') ?></textarea>
        </label>
        <button type="submit" class="btn-primary">Ayarları kaydet</button>
    </form>
</section>

<section class="card settings-section">
    <h2>Şimdi Üret</h2>
    <p class="settings-desc">Konu havuzundan rastgele bir konuda hemen <?= e($lower) ?> üretir. Metin üretimi ~3 kuruş; görsel üretilmez.</p>
    <div class="run-buttons">
        <form method="post" action="<?= e($genBase) ?>/uret" class="inline-form"
              onsubmit="return confirm('Bir içerik üretilecek (taslak). OpenAI'da ~3 kuruş ücretlendirilir. Devam?');">
            <?= csrf_field() ?>
            <input type="hidden" name="mode" value="draft">
            <button type="submit" class="btn-secondary">Şimdi Üret (taslak)</button>
        </form>
        <form method="post" action="<?= e($genBase) ?>/uret" class="inline-form"
              onsubmit="return confirm('Bir içerik üretilip YAYINLANACAK. OpenAI'da ~3 kuruş ücretlendirilir. Devam?');">
            <?= csrf_field() ?>
            <input type="hidden" name="mode" value="publish">
            <button type="submit" class="btn-primary">Üret ve Yayınla</button>
        </form>
    </div>
</section>

<section class="card settings-section">
    <h2>Durum</h2>
    <dl class="status-list">
        <div><dt>Otomatik üretim</dt><dd><?= !empty($config['enabled']) ? '<span class="ok-text">Açık</span>' : '<span class="muted-text">Kapalı</span>' ?></dd></div>
        <div><dt>Zamanlayıcı (cron)</dt><dd><?= $cronConfigured ? '<span class="ok-text">cron_secret tanımlı</span>' : '<span class="danger-text">cron_secret yok — otomatik tetikleme çalışmaz</span>' ?></dd></div>
        <div><dt>Son üretim</dt><dd>
            <?php if (!empty($status['lastAt'])): ?>
                <?= e(date('d.m.Y H:i', strtotime($status['lastAt']))) ?><?= !empty($status['lastStatus']) ? ' · ' . e($status['lastStatus']) : '' ?>
            <?php else: ?><span class="muted-text">Henüz yok</span><?php endif; ?>
        </dd></div>
        <?php if (!empty($status['lastTopic'])): ?>
            <div><dt>Son konu</dt><dd><?= e($status['lastTopic']) ?></dd></div>
        <?php endif; ?>
        <?php if (!empty($status['lastError'])): ?>
            <div><dt>Son hata</dt><dd class="danger-text"><?= e($status['lastError']) ?></dd></div>
        <?php endif; ?>
    </dl>
    <details class="cron-help">
        <summary>Otomatik üretim nasıl kurulur?</summary>
        <p>Her zaman açık bir makinedeki bir zamanlayıcı (sistem/hosting cron) şu ucu saat başı çağırmalı:</p>
        <code>POST /cron/generate-blog?secret=&lt;CRON_SECRET&gt;</code>
        <p>config.local.php içine bir <code>cron_secret</code> ekle. Tek bir cron, <strong>açık olan tüm türleri</strong> (blog, rehber, haber) kontrol eder; her tür kendi hedef saatinde, günde yalnızca bir kez üretilir.</p>
    </details>
</section>
