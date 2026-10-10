<?php $pageTitle = 'Fasilitas'; require __DIR__ . '/../layouts/header.php'; ?>

<h1>Daftar Fasilitas</h1>

<form method="GET" class="card" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;">
    <div class="form-group" style="flex:2;min-width:180px;">
        <label>Cari nama / lokasi</label>
        <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" placeholder="mis. Ruang Seminar">
    </div>
    <div class="form-group" style="flex:1;min-width:140px;">
        <label>Tipe</label>
        <select name="type">
            <option value="">Semua</option>
            <?php foreach ($types as $t): ?>
                <option value="<?= htmlspecialchars($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group" style="flex:1;min-width:140px;">
        <label>Kapasitas minimum</label>
        <input type="number" name="min_capacity" min="1" value="<?= htmlspecialchars((string) ($minCapacity ?? '')) ?>">
    </div>
    <button type="submit">Cari</button>
</form>

<div class="stats-grid" style="margin-top:16px;">
    <?php foreach ($facilities as $f): ?>
        <div class="card">
            <h2 style="margin-bottom:4px;"><?= htmlspecialchars($f['name']) ?></h2>
            <span class="badge badge-<?= $f['status'] ?>"><?= $f['status'] ?></span>
            <p style="color:#6b7280;font-size:0.88rem;margin:10px 0;">
                <?= htmlspecialchars($f['type']) ?> · <?= htmlspecialchars($f['location']) ?> · Kapasitas <?= (int) $f['capacity'] ?>
            </p>
            <?php if ($f['status'] === 'INACTIVE'): ?>
                <span class="muted">Fasilitas nonaktif — detail tidak tersedia</span>
            <?php else: ?>
                <a class="btn" href="<?= BASE_URL ?>/facility/detail?id=<?= $f['id'] ?>">Lihat Detail & Availability</a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if (empty($facilities)): ?>
        <p style="color:#9ca3af;">Tidak ada fasilitas yang cocok dengan pencarian.</p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
