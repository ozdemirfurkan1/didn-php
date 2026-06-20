<?php
declare(strict_types=1);
/** @var bool $openaiConfigured */
/** @var string $model */
?>
<a href="/admin" class="back-link">← Yönetim Paneli</a>
<h1 class="admin-h1">Ayarlar</h1>
<?php include __DIR__ . '/_flash.php'; ?>

<section class="card settings-section">
    <h2>Yapay Zekâ (OpenAI)</h2>
    <p class="settings-desc">İçerik üretimi için OpenAI API anahtarı. Anahtar yalnızca sunucuda saklanır; “AI ile üret” butonları bunu kullanır.</p>
    <form method="post" action="/admin/ayarlar" class="settings-form">
        <?= csrf_field() ?>
        <label>Metin modeli
            <select name="model">
                <?php foreach (OPENAI_ALLOWED_MODELS as $m): ?>
                    <option value="<?= $m ?>" <?= $model === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>OpenAI API anahtarı
            <span class="hint">(<?= $openaiConfigured ? 'tanımlı — değiştirmek için doldurun' : 'tanımlı değil' ?>)</span>
            <input type="password" name="apiKey" placeholder="sk-..." autocomplete="off">
        </label>
        <div class="form-actions">
            <button type="submit" class="btn-primary">Ayarları kaydet</button>
        </div>
    </form>
    <form method="post" action="/admin/ayarlar-test" class="test-form">
        <?= csrf_field() ?>
        <button type="submit" class="btn-secondary" <?= $openaiConfigured ? '' : 'disabled' ?>>Bağlantıyı test et</button>
        <span class="hint">Ücretsiz doğrulama (token harcamaz).</span>
    </form>
</section>

<section class="card settings-section">
    <h2>Şifre değiştir</h2>
    <p class="settings-desc">Yönetim girişi için kullanılan şifreyi güncelleyin.</p>
    <form method="post" action="/admin/sifre" class="settings-form">
        <?= csrf_field() ?>
        <label>Mevcut şifre
            <input type="password" name="currentPassword" autocomplete="current-password" required>
        </label>
        <label>Yeni şifre (en az 8 karakter)
            <input type="password" name="newPassword" autocomplete="new-password" required>
        </label>
        <button type="submit" class="btn-primary">Şifreyi değiştir</button>
    </form>
</section>
