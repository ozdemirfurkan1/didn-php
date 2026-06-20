<?php
declare(strict_types=1);
/** @var string $type */
/** @var ?array $item */
$info   = CONTENT_TYPES[$type];
$isEdit = $item !== null;
$action = $isEdit ? "/admin/icerik/$type/{$item['id']}" : "/admin/icerik/$type/yeni";
?>
<a href="/admin/icerik/<?= $type ?>" class="back-link">← <?= e($info['plural']) ?></a>
<h1 class="admin-h1"><?= $isEdit ? e($info['label']) . ' düzenle' : 'Yeni ' . e($info['label']) ?></h1>
<?php include __DIR__ . '/_flash.php'; ?>

<section class="card ai-panel">
    <h2>✨ AI ile üret</h2>
    <p class="settings-desc">Konuyu yaz, OpenAI başlık ve içeriği üretip yeni bir taslak oluştursun. Üretimi gözden geçirip kaydedersin. (Metin ~3 kuruş; görsel üretilmez.)</p>
    <form method="post" action="/admin/icerik/<?= $type ?>/uret" class="ai-form"
          onsubmit="return confirm('Bu işlem OpenAI&#39;da ~3 kuruş ücretlendirilir. Devam edilsin mi?');">
        <?= csrf_field() ?>
        <input type="text" name="topic" placeholder="Konu — ne hakkında bir <?= e(mb_strtolower($info['label'], 'UTF-8')) ?> olsun?" required>
        <input type="text" name="instructions" placeholder="Ek talimat (opsiyonel) — ör. başlangıç seviyesi, örneklerle">
        <button type="submit" class="btn-primary">✨ AI ile üret</button>
    </form>
</section>

<form method="post" action="<?= $action ?>" class="content-form card">
    <?= csrf_field() ?>
    <label>Başlık
        <input type="text" name="title" value="<?= e($item['title'] ?? '') ?>" required>
    </label>
    <label>Slug (boş bırakılırsa başlıktan üretilir)
        <input type="text" name="slug" value="<?= e($item['slug'] ?? '') ?>" placeholder="ornek-baslik">
    </label>
    <label>Özet (opsiyonel)
        <textarea name="summary" rows="2"><?= e($item['summary'] ?? '') ?></textarea>
    </label>
    <label>İçerik (Markdown)
        <textarea name="body" rows="16" class="mono"><?= e($item['body'] ?? '') ?></textarea>
    </label>
    <label>Kapak görseli URL (opsiyonel)
        <input type="text" name="coverImage" value="<?= e($item['cover_image'] ?? '') ?>" placeholder="https://...">
    </label>
    <label>Durum
        <select name="status">
            <?php foreach (CONTENT_STATUS_LABELS as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= ($item['status'] ?? 'draft') === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <div class="form-actions">
        <button type="submit" class="btn-primary"><?= $isEdit ? 'Değişiklikleri kaydet' : 'Oluştur' ?></button>
        <a href="/admin/icerik/<?= $type ?>" class="btn-secondary">Vazgeç</a>
    </div>
</form>
