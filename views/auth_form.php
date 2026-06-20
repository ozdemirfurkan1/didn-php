<?php
// Giriş / Kayıt formu (paylaşımlı). $mode: 'login' | 'register'.
declare(strict_types=1);
/** @var string $mode */
$error = $error ?? null;
$old   = $old ?? [];
$isLogin = $mode === 'login';
?>
<div class="auth-card card">
    <h1><?= $isLogin ? 'Giriş Yap' : 'Kayıt Ol' ?></h1>

    <?php if ($error): ?>
        <p class="form-error"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" action="<?= $isLogin ? '/giris' : '/kayit' ?>" class="auth-form">
        <?= csrf_field() ?>
        <?php if (!$isLogin): ?>
            <label>Ad (opsiyonel)
                <input type="text" name="name" value="<?= e($old['name'] ?? '') ?>" autocomplete="name">
            </label>
        <?php endif; ?>
        <label>E-posta
            <input type="email" name="email" value="<?= e($old['email'] ?? '') ?>" autocomplete="email" required>
        </label>
        <label>Şifre
            <input type="password" name="password" autocomplete="<?= $isLogin ? 'current-password' : 'new-password' ?>" required>
        </label>
        <button type="submit"><?= $isLogin ? 'Giriş Yap' : 'Hesap Oluştur' ?></button>
    </form>

    <p class="auth-alt">
        <?php if ($isLogin): ?>
            Hesabın yok mu? <a href="/kayit">Kayıt ol</a>
        <?php else: ?>
            Zaten hesabın var mı? <a href="/giris">Giriş yap</a>
        <?php endif; ?>
    </p>
</div>
