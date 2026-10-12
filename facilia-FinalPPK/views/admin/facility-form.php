<?php $pageTitle = $facility ? 'Edit Fasilitas' : 'Tambah Fasilitas'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="card" style="max-width:480px;">
    <h1><?= $facility ? 'Edit Fasilitas' : 'Tambah Fasilitas' ?></h1>
    <form method="POST" action="<?= BASE_URL ?>/admin/facilities/save">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <?php if ($facility): ?>
            <input type="hidden" name="id" value="<?= $facility['id'] ?>">
        <?php endif; ?>

        <div class="form-group">
            <label>Nama</label>
            <input type="text" name="name" maxlength="150" value="<?= htmlspecialchars($facility['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Tipe</label>
            <input type="text" name="type" maxlength="50" value="<?= htmlspecialchars($facility['type'] ?? '') ?>" placeholder="Ruang Kelas / Laboratorium / Aula ..." required>
        </div>
        <div class="form-group">
            <label>Lokasi</label>
            <input type="text" name="location" maxlength="150" value="<?= htmlspecialchars($facility['location'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Kapasitas</label>
            <input type="number" name="capacity" min="1" max="100000" value="<?= htmlspecialchars((string) ($facility['capacity'] ?? '')) ?>" required>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status" required>
                <?php foreach (['ACTIVE', 'MAINTENANCE', 'INACTIVE'] as $s): ?>
                    <option value="<?= $s ?>" <?= (($facility['status'] ?? 'ACTIVE') === $s) ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Deskripsi</label>
            <textarea name="description" rows="3" maxlength="5000"><?= htmlspecialchars($facility['description'] ?? '') ?></textarea>
        </div>
        <button type="submit">Simpan</button>
        <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/facilities">Batal</a>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
