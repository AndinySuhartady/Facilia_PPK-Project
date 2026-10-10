<?php $pageTitle = 'Dashboard'; require __DIR__ . '/../layouts/header.php'; ?>

<h1>Dashboard Admin</h1>
<div class="stats-grid">
    <div class="stat-box"><div class="num"><?= $stats['total_users'] ?></div>Total User</div>
    <div class="stat-box"><div class="num"><?= $stats['pending_users'] ?></div>Menunggu Verifikasi</div>
    <div class="stat-box"><div class="num"><?= $stats['total_petugas'] ?></div>Petugas</div>
    <div class="stat-box"><div class="num"><?= $stats['total_facilities'] ?></div>Fasilitas</div>
</div>

<div class="card" style="margin-top:20px;">
    <h2>Navigasi Cepat</h2>
    <a class="btn" href="<?= BASE_URL ?>/admin/users?status=PENDING">Verifikasi User Baru</a>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/facilities">Kelola Fasilitas</a>
    <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/analytics">Lihat Analytics</a>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
