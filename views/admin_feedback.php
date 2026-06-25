<?php declare(strict_types=1); /** @var array $items */ ?>
<h1 class="admin-h1">Geri Bildirimler</h1>
<p class="admin-sub"><a href="/admin">&larr; Panele dön</a></p>
<?php include __DIR__ . '/_flash.php'; ?>

<?php if (empty($items)): ?>
    <p class="muted">Henüz geri bildirim yok.</p>
<?php else: ?>
    <div class="fb-list">
        <?php foreach ($items as $it): ?>
            <?php $label = FEEDBACK_TYPES[$it['type']] ?? 'Diğer'; ?>
            <article class="fb-item card">
                <div class="fb-top">
                    <span class="fb-type fb-type-<?= e($it['type']) ?>"><?= e($label) ?></span>
                    <span class="fb-date"><?= e(date('d.m.Y H:i', strtotime((string) $it['created_at']))) ?></span>
                </div>
                <p class="fb-msg"><?= nl2br(e($it['message'])) ?></p>
                <div class="fb-meta">
                    <?php if (!empty($it['name'])): ?><span><?= e($it['name']) ?></span><?php endif; ?>
                    <?php if (!empty($it['email'])): ?><a href="mailto:<?= e($it['email']) ?>"><?= e($it['email']) ?></a><?php endif; ?>
                    <?php if (!empty($it['user_id'])): ?><span class="muted">üye #<?= e((string) $it['user_id']) ?></span><?php endif; ?>
                    <form method="post" action="/admin/geri-bildirim" class="fb-del" onsubmit="return confirm('Bu mesaj silinsin mi?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="delete" value="<?= e((string) $it['id']) ?>">
                        <button type="submit">Sil</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
