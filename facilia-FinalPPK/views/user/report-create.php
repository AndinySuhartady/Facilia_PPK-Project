<?php $pageTitle = 'Buat Laporan'; require __DIR__ . '/../layouts/header.php'; ?>

<h1>Laporkan Kerusakan</h1>
<p class="subtitle">Sampaikan kerusakan fasilitas agar dapat segera ditangani petugas.</p>

<div class="card" style="max-width:640px;">
    <?php if (empty($facilities)): ?>
        <div class="alert alert-error">Tidak ada fasilitas aktif yang dapat dilaporkan.</div>
    <?php else: ?>
        <form method="POST" action="<?= BASE_URL ?>/report/create" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="form-group">
                <label>Fasilitas <span class="required">*</span></label>
                <select name="facility_id" required>
                    <option value="">Pilih fasilitas</option>
                    <?php foreach ($facilities as $f): ?>
                        <option value="<?= $f['id'] ?>" <?= ((int) $old['facility_id'] === (int) $f['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['name']) ?> — <?= htmlspecialchars($f['location']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Kategori kerusakan <span class="required">*</span></label>
                <select name="category" required>
                    <option value="">Pilih kategori</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= ($old['category'] === $c) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Deskripsi kerusakan <span class="required">*</span></label>
                <textarea name="description" maxlength="2000" placeholder="Jelaskan lokasi dan kondisi kerusakan..." required><?= htmlspecialchars($old['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label>Foto kerusakan <span class="optional">(opsional)</span></label>
                <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                <p class="file-help">Boleh dikosongkan. Jika diunggah: format JPG, PNG, atau WEBP, maksimal 2MB.</p>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;">
                <a class="link" href="<?= BASE_URL ?>/report/my">Lihat laporan saya</a>
                <button type="submit">Kirim Laporan</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
