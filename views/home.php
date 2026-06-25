<?php declare(strict_types=1); /** @var array|null $wotd */ ?>
<section class="hero">
    <h1>İngilizce ↔ Türkçe Sözlük</h1>
    <p class="hero-sub">Bir kelime ara; anlamı, çevirileri, telaffuzu ve örnek cümleleriyle.</p>
    <?php $curQ = ''; $curDir = 'auto'; include __DIR__ . '/_search.php'; ?>
</section>

<?php if (!empty($wotd)): ?>
    <section class="wotd card">
        <div class="wotd-label">Günün Kelimesi</div>

        <div class="wotd-head">
            <span class="wotd-word"><?= e($wotd['headword']) ?></span>
            <?php if (!empty($wotd['phonetic'])): ?>
                <span class="wotd-phonetic"><?= e($wotd['phonetic']) ?></span>
            <?php endif; ?>
            <?php if (!empty($wotd['audioUrl'])): ?>
                <audio class="wotd-audio" controls preload="none" src="<?= e($wotd['audioUrl']) ?>"></audio>
            <?php endif; ?>
        </div>

        <?php if (!empty($wotd['translations'])): ?>
            <div class="wotd-trans">
                <span class="wotd-en"><?= e($wotd['headword']) ?></span>
                <span class="wotd-eq">=</span>
                <span class="wotd-tr"><?= e(implode(', ', array_map(fn($t) => $t['word'], array_slice($wotd['translations'], 0, 5)))) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($wotd['meaning']['definition'])): ?>
            <div class="wotd-row"><span class="wotd-row-label">Tanım</span>
                <p class="wotd-def"><?= e($wotd['meaning']['definition']) ?>
                    <?php if (!empty($wotd['meaning']['example'])): ?>
                        <span class="wotd-ex">“<?= e($wotd['meaning']['example']) ?>”</span>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if (!empty($wotd['synonyms'])): ?>
            <div class="wotd-row"><span class="wotd-row-label">Eş anlamlı</span>
                <div class="wotd-chips">
                    <?php foreach ($wotd['synonyms'] as $s): ?>
                        <a class="chip" href="/en/<?= rawurlencode($s) ?>"><?= e($s) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($wotd['antonyms'])): ?>
            <div class="wotd-row"><span class="wotd-row-label">Zıt anlamlı</span>
                <div class="wotd-chips">
                    <?php foreach ($wotd['antonyms'] as $a): ?>
                        <a class="chip" href="/en/<?= rawurlencode($a) ?>"><?= e($a) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="home-refs">
    <h2 class="home-refs-title">Hızlı Başvuru</h2>
    <div class="ref-banner-grid">
        <a class="ref-banner card" href="/duzensiz-fiiller">
            <span class="ref-banner-icon">📋</span>
            <span><strong>Düzensiz Fiiller</strong><span class="ref-banner-sub">go-went-gone, V2/V3 ve Türkçe anlamları.</span></span>
        </a>
        <a class="ref-banner card" href="/phrasal-verbs">
            <span class="ref-banner-icon">🔗</span>
            <span><strong>Phrasal Verbs</strong><span class="ref-banner-sub">Öbek fiiller, anlam ve örnek cümleler.</span></span>
        </a>
        <a class="ref-banner card" href="/deyimler">
            <span class="ref-banner-icon">💬</span>
            <span><strong>Deyimler (Idioms)</strong><span class="ref-banner-sub">İngilizce deyimler ve Türkçe karşılıkları.</span></span>
        </a>
        <a class="ref-banner card" href="/ingilizce-kelimeler">
            <span class="ref-banner-icon">⭐</span>
            <span><strong>Sık Kullanılan Kelimeler</strong><span class="ref-banner-sub">Seviyeye göre temel İngilizce kelimeler.</span></span>
        </a>
    </div>
</section>

<section class="home-contact card" id="bize-yazin">
    <h2 class="home-contact-title">Bize Yazın</h2>
    <p class="home-contact-sub">Öneri, şikayet veya bir hata mı var? Gelişmemize yardımcı ol — her mesajı okuyoruz.</p>
    <?php include __DIR__ . '/_flash.php'; ?>
    <form class="contact-form" method="post" action="/geri-bildirim">
        <?= csrf_field() ?>
        <div class="contact-row">
            <label class="contact-field">
                <span>Konu</span>
                <select name="type">
                    <?php foreach (FEEDBACK_TYPES as $val => $label): ?>
                        <option value="<?= e($val) ?>"><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="contact-field">
                <span>Adın <small>(isteğe bağlı)</small></span>
                <input type="text" name="name" maxlength="190" placeholder="Adın">
            </label>
            <label class="contact-field">
                <span>E-posta <small>(isteğe bağlı)</small></span>
                <input type="email" name="email" maxlength="255" placeholder="ornek@eposta.com">
            </label>
        </div>
        <label class="contact-field">
            <span>Mesajın</span>
            <textarea name="message" rows="4" maxlength="4000" required placeholder="Mesajını buraya yaz…"></textarea>
        </label>
        <!-- bal küpü: gizli, gerçek kullanıcı doldurmaz -->
        <input type="text" name="website" tabindex="-1" autocomplete="off" class="hp-field" aria-hidden="true">
        <div class="contact-actions">
            <button type="submit">Gönder</button>
        </div>
    </form>
</section>
