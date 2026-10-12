<?php $pageTitle = 'Riwayat Reservasi'; require __DIR__ . '/../layouts/header.php'; ?>
<?php
$listPath = '/reservation/history';
$defaultSort = ['date', 'desc'];
$statusPills = ['' => 'Semua', 'PENDING' => 'Pending', 'APPROVED' => 'Approved', 'REJECTED' => 'Rejected', 'CANCELLED' => 'Cancelled'];
?>

<div class="top-bar">
    <div>
        <h1>Riwayat Reservasi Saya</h1>
        <p class="subtitle">Pembatalan hanya dapat dilakukan sebelum jam mulai reservasi (WIB).</p>
    </div>
    <a class="btn" href="<?= BASE_URL ?>/facilities">+ Reservasi Baru</a>
</div>

<div class="filter-pills">
    <?php foreach ($statusPills as $value => $label): ?>
        <a class="filter-pill <?= $filters['status'] === $value ? 'active' : '' ?>" href="<?= htmlspecialchars(listingUrl($listPath, $filters, ['status' => $value], $defaultSort)) ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <form method="GET" action="<?= BASE_URL . $listPath ?>" class="queue-filter">
        <?php if ($filters['status'] !== ''): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filters['status']) ?>"><?php endif; ?>
        <div class="form-group queue-filter__user">
            <label for="fq">Cari</label>
            <input type="search" id="fq" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Nama fasilitas atau tujuan penggunaan" maxlength="100">
        </div>
        <div class="form-group">
            <label for="fdate">Tanggal reservasi</label>
            <input type="date" id="fdate" name="date" value="<?= htmlspecialchars($filters['date']) ?>">
        </div>
        <div class="form-group">
            <label for="fsort">Urutkan</label>
            <select id="fsort" name="sort">
                <option value="date" <?= $filters['sort'] === 'date' ? 'selected' : '' ?>>Tanggal reservasi</option>
                <option value="created" <?= $filters['sort'] === 'created' ? 'selected' : '' ?>>Waktu pengajuan</option>
                <option value="facility" <?= $filters['sort'] === 'facility' ? 'selected' : '' ?>>Fasilitas</option>
                <option value="status" <?= $filters['sort'] === 'status' ? 'selected' : '' ?>>Status</option>
            </select>
        </div>
        <div class="form-group">
            <label for="fdir">Arah</label>
            <select id="fdir" name="dir">
                <option value="desc" <?= $filters['dir'] === 'desc' ? 'selected' : '' ?>>↓ Terbaru dulu</option>
                <option value="asc" <?= $filters['dir'] === 'asc' ? 'selected' : '' ?>>↑ Terlama dulu</option>
            </select>
        </div>
        <div class="queue-filter__actions">
            <button type="submit">Terapkan</button>
            <?php if ($hasActiveFilter || $filters['sort'] !== 'date' || $filters['dir'] !== 'desc'): ?>
                <a class="btn btn-secondary" href="<?= htmlspecialchars(listingUrl($listPath, ['status' => $filters['status']])) ?>">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <p class="queue-count"><?= count($reservations) ?> reservasi ditemukan<?= $hasActiveFilter ? ' sesuai filter' : '' ?>.</p>
    <div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th><?= sortHeaderLink($listPath, $filters, 'facility', 'Fasilitas', $defaultSort) ?></th>
                <th><?= sortHeaderLink($listPath, $filters, 'date', 'Tanggal', $defaultSort) ?></th>
                <th>Jam</th>
                <th><?= sortHeaderLink($listPath, $filters, 'status', 'Status', $defaultSort) ?></th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($reservations as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['facility_name']) ?>
                    <?php if (!empty($r['purpose'])): ?><small class="queue-email"><?= htmlspecialchars($r['purpose']) ?></small><?php endif; ?>
                </td>
                <td><?= htmlspecialchars(date('d M Y', strtotime($r['reservation_date']))) ?></td>
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
                    <a class="link" href="<?= BASE_URL ?>/reservation/detail?id=<?= (int) $r['id'] ?>">Detail</a>
                    <?php if (Reservation::canOwnerCancel($r)): ?>
                        <form method="POST" action="<?= BASE_URL ?>/reservation/cancel"
                              onsubmit="return confirmReservationAction(this, 'Batalkan reservasi ini?');" style="display:inline">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <input type="hidden" name="confirmed" value="0">
                            <input type="text" name="cancellation_reason" maxlength="255" placeholder="Alasan (opsional, min. 10 karakter)" style="width:200px;">
                            <button type="submit" class="btn-danger">Batalkan</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($reservations)): ?>
            <tr><td colspan="5" class="empty-state"><?= $hasActiveFilter ? 'Tidak ada reservasi yang cocok dengan filter.' : 'Belum ada reservasi.' ?></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
