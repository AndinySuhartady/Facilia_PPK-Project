<?php $pageTitle = 'Antrean Reservasi'; require __DIR__ . '/../layouts/header.php'; ?>
<?php
// Bangun URL antrean dengan mempertahankan filter yang sedang aktif.
$queueUrl = static function (array $override = []) use ($status, $facilityId, $date, $time, $userKeyword, $sort, $dir): string {
    $query = array_merge([
        'status'      => $status,
        'facility_id' => $facilityId ?: '',
        'date'        => $date,
        'time'        => $time,
        'user'        => $userKeyword,
        'sort'        => $sort,
        'dir'         => $dir,
    ], $override);
    // Default sort tidak perlu ditulis di URL agar tetap bersih.
    if (($query['sort'] ?? '') === 'date' && ($query['dir'] ?? '') === 'asc') {
        unset($query['sort'], $query['dir']);
    }
    $query = array_filter($query, static fn($v) => $v !== '' && $v !== null && $v !== 0);
    return BASE_URL . '/petugas/reservations' . ($query ? '?' . http_build_query($query) : '');
};
// Header tabel yang bisa diklik untuk sorting (klik lagi = balik arah).
$sortHeader = static function (string $key, string $label) use ($sort, $dir, $queueUrl): string {
    $isCurrent = $sort === $key;
    $nextDir = ($isCurrent && $dir === 'asc') ? 'desc' : 'asc';
    $arrow = $isCurrent ? ($dir === 'asc' ? ' ▲' : ' ▼') : ' <span class="sort-idle">↕</span>';
    return '<a class="sort-link' . ($isCurrent ? ' is-active' : '') . '" href="' . htmlspecialchars($queueUrl(['sort' => $key, 'dir' => $nextDir])) . '">'
        . htmlspecialchars($label) . $arrow . '</a>';
};
$statusPills = ['' => 'Semua', 'PENDING' => 'Pending', 'APPROVED' => 'Approved', 'REJECTED' => 'Rejected', 'CANCELLED' => 'Cancelled'];
?>

<div class="top-bar">
    <div>
        <h1>Antrean Reservasi</h1>
        <p class="subtitle">Tinjau, setujui, atau tolak permintaan reservasi fasilitas.</p>
    </div>
</div>

<div class="filter-pills">
    <?php foreach ($statusPills as $value => $label): ?>
        <a class="filter-pill <?= $status === $value ? 'active' : '' ?>" href="<?= htmlspecialchars($queueUrl(['status' => $value])) ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<div class="card queue-filter-card">
    <form method="GET" action="<?= BASE_URL ?>/petugas/reservations" class="queue-filter">
        <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>"><?php endif; ?>

        <div class="form-group queue-filter__user">
            <label for="filterUser">Cari user</label>
            <input type="search" id="filterUser" name="user" value="<?= htmlspecialchars($userKeyword) ?>" placeholder="Nama atau email pemohon" maxlength="100">
        </div>

        <div class="form-group">
            <label for="filterFacility">Fasilitas</label>
            <select id="filterFacility" name="facility_id">
                <option value="">Semua fasilitas</option>
                <?php foreach ($facilities as $f): ?>
                    <option value="<?= (int) $f['id'] ?>" <?= (int) $f['id'] === $facilityId ? 'selected' : '' ?>><?= htmlspecialchars($f['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="filterDate">Tanggal</label>
            <input type="date" id="filterDate" name="date" value="<?= htmlspecialchars($date) ?>">
        </div>

        <div class="form-group">
            <label for="filterTime">Jam</label>
            <select id="filterTime" name="time">
                <option value="">Semua jam</option>
                <?php foreach (array_slice($timeOptions, 0, -1) as $t): ?>
                    <option value="<?= $t ?>" <?= $t === $time ? 'selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="filterSort">Urutkan</label>
            <select id="filterSort" name="sort">
                <option value="date" <?= $sort === 'date' ? 'selected' : '' ?>>Tanggal</option>
                <option value="time" <?= $sort === 'time' ? 'selected' : '' ?>>Jam</option>
                <option value="facility" <?= $sort === 'facility' ? 'selected' : '' ?>>Fasilitas</option>
                <option value="user" <?= $sort === 'user' ? 'selected' : '' ?>>Nama user</option>
                <option value="created" <?= $sort === 'created' ? 'selected' : '' ?>>Waktu pengajuan</option>
            </select>
        </div>

        <div class="form-group">
            <label for="filterDir">Arah</label>
            <select id="filterDir" name="dir">
                <option value="asc" <?= $dir === 'asc' ? 'selected' : '' ?>>Naik ↑ (A–Z, lama–baru)</option>
                <option value="desc" <?= $dir === 'desc' ? 'selected' : '' ?>>Turun ↓ (Z–A, baru–lama)</option>
            </select>
        </div>

        <div class="queue-filter__actions">
            <button type="submit">Terapkan</button>
            <?php if ($hasActiveFilter || $sort !== 'date' || $dir !== 'asc'): ?>
                <a class="btn btn-secondary" href="<?= htmlspecialchars(BASE_URL . '/petugas/reservations' . ($status !== '' ? '?status=' . urlencode($status) : '')) ?>">Reset</a>
            <?php endif; ?>
        </div>
    </form>
    <p class="queue-filter__hint">
        Filter <strong>Jam</strong> menampilkan reservasi yang sedang berlangsung pada jam tersebut
        (mis. 09:00 juga memunculkan reservasi 08:00–10:00).
    </p>
</div>

<div class="card">
    <p class="queue-count"><?= count($reservations) ?> reservasi ditemukan<?= $hasActiveFilter ? ' sesuai filter' : '' ?>.</p>
    <div class="table-wrapper">
        <table>
            <thead><tr>
                <th><?= $sortHeader('facility', 'Fasilitas') ?></th>
                <th><?= $sortHeader('user', 'Pemohon') ?></th>
                <th><?= $sortHeader('date', 'Tanggal') ?></th>
                <th><?= $sortHeader('time', 'Jam') ?></th>
                <th>Status</th>
                <th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($reservations as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['facility_name']) ?></td>
                    <td><?= htmlspecialchars($r['user_name']) ?><small class="queue-email"><?= htmlspecialchars($r['user_email']) ?></small></td>
                    <td><?= htmlspecialchars(date('d M Y', strtotime($r['reservation_date']))) ?></td>
                    <td><?= substr($r['start_time'], 0, 5) ?>–<?= substr($r['end_time'], 0, 5) ?></td>
                    <td><span class="badge badge-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
                    <td><a class="link" href="<?= BASE_URL ?>/petugas/reservations/detail?id=<?= $r['id'] ?>">Detail</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($reservations)): ?>
                <tr><td colspan="6" class="empty-state"><?= $hasActiveFilter ? 'Tidak ada reservasi yang cocok dengan filter.' : 'Tidak ada data.' ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
