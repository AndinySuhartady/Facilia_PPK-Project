<?php $pageTitle = 'Riwayat Reservasi'; require __DIR__ . '/../layouts/header.php'; ?>

<h1>Riwayat Reservasi Saya</h1>
<p>Pembatalan hanya dapat dilakukan sebelum jam mulai reservasi (WIB).</p>

<div class="card">
    <table>
        <thead>
            <tr><th>Fasilitas</th><th>Tanggal</th><th>Jam</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
        <?php foreach ($reservations as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['facility_name']) ?></td>
                <td><?= htmlspecialchars($r['reservation_date']) ?></td>
                <td><?= substr($r['start_time'], 0, 5) ?> – <?= substr($r['end_time'], 0, 5) ?></td>
                <td>
                    <span class="badge badge-<?= $r['status'] ?>"><?= $r['status'] ?></span>
                    <?php if ($r['status'] === 'REJECTED' && !empty($r['rejection_reason'])): ?>
                        <div style="font-size:0.78rem;color:#9ca3af;"><?= htmlspecialchars($r['rejection_reason']) ?></div>
                    <?php endif; ?>
                    <?php if ($r['status'] === 'CANCELLED' && !empty($r['cancellation_reason'])): ?>
                        <div style="font-size:0.78rem;color:#9ca3af;"><?= htmlspecialchars($r['cancellation_reason']) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?= BASE_URL ?>/reservation/detail?id=<?= (int) $r['id'] ?>">Detail</a>
                    <?php if (Reservation::canOwnerCancel($r)): ?>
                        <form method="POST" action="<?= BASE_URL ?>/reservation/cancel"
                              onsubmit="return confirmReservationAction(this, 'Batalkan reservasi ini?');" style="display:inline">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="confirmed" value="0">
                            <button type="submit" class="btn-danger">Batalkan</button>
                        </form>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($reservations)): ?>
            <tr><td colspan="5" style="color:#9ca3af;">Belum ada reservasi.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function confirmReservationAction(form, message) {
    if (!confirm(message)) return false;
    if (!confirm('Konfirmasi terakhir: Anda benar-benar yakin?')) return false;
    form.querySelector('[name="confirmed"]').value = '1';
    return true;
}
</script>

<a class="btn btn-secondary" href="<?= BASE_URL ?>/facilities">← Cari Fasilitas Lain</a>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
