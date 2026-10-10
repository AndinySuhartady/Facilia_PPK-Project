<?php $pageTitle = 'Fasilitas'; require __DIR__ . '/../layouts/header.php'; ?>

<h1>Master Fasilitas</h1>
<a class="btn" href="<?= BASE_URL ?>/admin/facilities/create" style="margin-bottom:16px;display:inline-block;">+ Tambah Fasilitas</a>

<div class="card">
    <table>
        <thead>
            <tr><th>Nama</th><th>Tipe</th><th>Lokasi</th><th>Kapasitas</th><th>Status</th><th>Aksi</th></tr>
        </thead>
        <tbody>
        <?php foreach ($facilities as $f): ?>
            <tr>
                <td><?= htmlspecialchars($f['name']) ?></td>
                <td><?= htmlspecialchars($f['type']) ?></td>
                <td><?= htmlspecialchars($f['location']) ?></td>
                <td><?= (int) $f['capacity'] ?></td>
                <td><span class="badge badge-<?= $f['status'] ?>"><?= $f['status'] ?></span></td>
                <td>
                    <a class="btn btn-secondary" href="<?= BASE_URL ?>/admin/facilities/edit?id=<?= $f['id'] ?>">Edit</a>
                    <?php if ($f['status'] !== 'INACTIVE'): ?>
                        <form method="POST" action="<?= BASE_URL ?>/admin/facilities/deactivate" style="display:inline"
                              onsubmit="return confirm('Nonaktifkan fasilitas ini?');">
                            <input type="hidden" name="id" value="<?= $f['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                            <button type="submit" class="btn-danger">Nonaktifkan</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
