<?php declare(strict_types=1); ?>
<?php foreach (get_flashes() as $f): ?>
    <p class="<?= $f['type'] === 'error' ? 'form-error' : 'form-ok' ?>"><?= e($f['msg']) ?></p>
<?php endforeach; ?>
