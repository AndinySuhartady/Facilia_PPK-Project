<?php $pageTitle = 'Laporan Saya'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="top-bar">
    <div>
        <h1>Laporan Saya</h1>
        <p class="subtitle">Riwayat laporan kerusakan yang pernah Anda kirim.</p>
    </div>
    <a class="btn" href="<?= BASE_URL ?>/report/create">+ Buat Laporan Baru</a>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>Fasilitas</th><th>Kategori</th><th>Tanggal Lapor</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($reports as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['facility_name']) ?></td>
                    <td><?= htmlspecialchars($r['category']) ?></td>
                    <td><?= htmlspecialchars(date('d M Y', strtotime($r['created_at']))) ?></td>
                    <td><span class="badge badge-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
                    <td><a class="link" href="<?= BASE_URL ?>/report/detail?id=<?= $r['id'] ?>">Detail</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($reports)): ?>
                <tr><td colspan="5" class="empty-state">Belum ada laporan.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
