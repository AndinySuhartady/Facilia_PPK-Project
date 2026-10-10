<?php $pageTitle = 'Antrean Reservasi'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="top-bar">
    <div>
        <h1>Antrean Reservasi</h1>
        <p class="subtitle">Tinjau, setujui, atau tolak permintaan reservasi fasilitas.</p>
    </div>
</div>

<div class="filter-pills">
    <a class="filter-pill <?= $status === '' ? 'active' : '' ?>" href="<?= BASE_URL ?>/petugas/reservations">Semua</a>
    <a class="filter-pill <?= $status === 'PENDING' ? 'active' : '' ?>" href="<?= BASE_URL ?>/petugas/reservations?status=PENDING">Pending</a>
    <a class="filter-pill <?= $status === 'APPROVED' ? 'active' : '' ?>" href="<?= BASE_URL ?>/petugas/reservations?status=APPROVED">Approved</a>
    <a class="filter-pill <?= $status === 'REJECTED' ? 'active' : '' ?>" href="<?= BASE_URL ?>/petugas/reservations?status=REJECTED">Rejected</a>
    <a class="filter-pill <?= $status === 'CANCELLED' ? 'active' : '' ?>" href="<?= BASE_URL ?>/petugas/reservations?status=CANCELLED">Cancelled</a>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Fasilitas</th><th>Pemohon</th><th>Tanggal</th><th>Jam</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($reservations as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['facility_name']) ?></td>
                    <td><?= htmlspecialchars($r['user_name']) ?></td>
                    <td><?= htmlspecialchars($r['reservation_date']) ?></td>
                    <td><?= substr($r['start_time'], 0, 5) ?>–<?= substr($r['end_time'], 0, 5) ?></td>
                    <td><span class="badge badge-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
                    <td><a class="link" href="<?= BASE_URL ?>/petugas/reservations/detail?id=<?= $r['id'] ?>">Detail</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($reservations)): ?>
                <tr><td colspan="6" class="empty-state">Tidak ada data.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
