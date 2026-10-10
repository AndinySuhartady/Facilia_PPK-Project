<?php $pageTitle = $facility['name']; require __DIR__ . '/../layouts/header.php'; ?>

<div class="card">
    <h1><?= htmlspecialchars($facility['name']) ?> <span class="badge badge-INACTIVE">INACTIVE</span></h1>
    <p class="subtitle"><?= htmlspecialchars($facility['type']) ?> · <?= htmlspecialchars($facility['location']) ?></p>
    <div class="alert alert-error" style="margin-top:16px;">
        Fasilitas ini sudah dinonaktifkan oleh admin. Detail dan availability tidak dapat dilihat,
        dan fasilitas ini tidak dapat direservasi.
    </div>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/facilities">← Kembali ke Daftar Fasilitas</a>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
