<?php $pageTitle = 'Antrean Laporan'; require __DIR__ . '/../layouts/header.php'; ?>
<?php
$listPath = '/petugas/reports';
$defaultSort = ['created', 'desc'];
$statusPills = ['' => 'Semua', 'BARU' => 'Baru', 'DIPROSES' => 'Diproses', 'SELESAI' => 'Selesai', 'DITOLAK' => 'Ditolak'];
?>

<div class="top-bar">
    <div>
        <h1>Antrean Laporan Kerusakan</h1>
        <p class="subtitle">Tangani laporan kerusakan yang masuk dari pengguna.</p>
    </div>
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
            <input type="search" id="fq" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Pelapor (nama/email), fasilitas, kategori, deskripsi" maxlength="100">
        </div>
        <div class="form-group">
            <label for="fcat">Kategori</label>
            <select id="fcat" name="category">
                <option value="">Semua kategori</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= $filters['category'] === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="fdate">Tanggal lapor</label>
            <input type="date" id="fdate" name="date" value="<?= htmlspecialchars($filters['date']) ?>">
        </div>
        <div class="form-group">
            <label for="fsort">Urutkan</label>
            <select id="fsort" name="sort">
                <option value="created" <?= $filters['sort'] === 'created' ? 'selected' : '' ?>>Tanggal lapor</option>
                <option value="reporter" <?= $filters['sort'] === 'reporter' ? 'selected' : '' ?>>Pelapor</option>
                <option value="facility" <?= $filters['sort'] === 'facility' ? 'selected' : '' ?>>Fasilitas</option>
                <option value="category" <?= $filters['sort'] === 'category' ? 'selected' : '' ?>>Kategori</option>
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
            <?php if ($hasActiveFilter || $filters['sort'] !== 'created' || $filters['dir'] !== 'desc'): ?>
                <a class="btn btn-secondary" href="<?= htmlspecialchars(listingUrl($listPath, ['status' => $filters['status']])) ?>">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <p class="queue-count"><?= count($reports) ?> laporan ditemukan<?= $hasActiveFilter ? ' sesuai filter' : '' ?>.</p>
    <div class="table-wrapper">
        <table>
            <thead><tr>
                <th><?= sortHeaderLink($listPath, $filters, 'facility', 'Fasilitas', $defaultSort) ?></th>
                <th><?= sortHeaderLink($listPath, $filters, 'reporter', 'Pelapor', $defaultSort) ?></th>
                <th><?= sortHeaderLink($listPath, $filters, 'category', 'Kategori', $defaultSort) ?></th>
                <th><?= sortHeaderLink($listPath, $filters, 'created', 'Tanggal', $defaultSort) ?></th>
                <th><?= sortHeaderLink($listPath, $filters, 'status', 'Status', $defaultSort) ?></th>
                <th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($reports as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['facility_name']) ?></td>
                    <td><?= htmlspecialchars($r['reporter_name']) ?><small class="queue-email"><?= htmlspecialchars($r['reporter_email']) ?></small></td>
                    <td><?= htmlspecialchars($r['category']) ?></td>
                    <td><?= htmlspecialchars(date('d M Y', strtotime($r['created_at']))) ?></td>
                    <td><span class="badge badge-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
                    <td><a class="link" href="<?= BASE_URL ?>/petugas/reports/detail?id=<?= $r['id'] ?>">Detail</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($reports)): ?>
                <tr><td colspan="6" class="empty-state"><?= $hasActiveFilter ? 'Tidak ada laporan yang cocok dengan filter.' : 'Tidak ada data.' ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
