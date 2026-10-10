<?php require_once __DIR__ . '/../../config/app.php'; ?>
<?php $pageTitle = 'Akses Ditolak'; require __DIR__ . '/../layouts/header.php'; ?>
<div class="card">
    <h1>403 — Akses Ditolak</h1>
    <p>Anda tidak memiliki hak akses untuk membuka halaman ini.</p>
    <a class="btn" href="<?= BASE_URL ?>/login">Kembali ke Login</a>
</div>
<?php require __DIR__ . '/../layouts/footer.php'; ?>
