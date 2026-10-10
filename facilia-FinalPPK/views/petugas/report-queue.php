<?php $pageTitle = 'Antrean Laporan'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="top-bar">
    <div>
        <h1>Antrean Laporan Kerusakan</h1>
        <p class="subtitle">Tangani laporan kerusakan yang masuk dari pengguna.</p>
    </div>
</div>

<div class="filter-pills">
    <a class="filter-pill <?= $status === '' ? 'active' : '' ?>" href="<?= BASE_URL ?>/petugas/reports">Semua</a>
    <a class="filter-pill <?= $status === 'BARU' ? 'active' : '' ?>" href="<?= BASE_URL ?>/petugas/reports?status=BARU">Baru</a>
    <a class="filter-pill <?= $status === 'DIPROSES' ? 'active' : '' ?>" href="<?= BASE_URL ?>/petugas/reports?status=DIPROSES">Diproses</a>
    <a class="filter-pill <?= $status === 'SELESAI' ? 'active' : '' ?>" href="<?= BASE_URL ?>/petugas/reports?status=SELESAI">Selesai</a>
    <a class="filter-pill <?= $status === 'DITOLAK' ? 'active' : '' ?>" href="<?= BASE_URL ?>/petugas/reports?status=DITOLAK">Ditolak</a>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Fasilitas</th><th>Pelapor</th><th>Kategori</th><th>Tanggal</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($reports as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['facility_name']) ?></td>
                    <td><?= htmlspecialchars($r['reporter_name']) ?></td>
                    <td><?= htmlspecialchars($r['category']) ?></td>
                    <td><?= htmlspecialchars(date('d M Y', strtotime($r['created_at']))) ?></td>
                    <td><span class="badge badge-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
                    <td><a class="link" href="<?= BASE_URL ?>/petugas/reports/detail?id=<?= $r['id'] ?>">Detail</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($reports)): ?>
                <tr><td colspan="6" class="empty-state">Tidak ada data.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
