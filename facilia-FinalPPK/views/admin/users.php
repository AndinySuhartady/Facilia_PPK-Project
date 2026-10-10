<?php $pageTitle = 'Manajemen User'; require __DIR__ . '/../layouts/header.php'; ?>

<h1>Manajemen User</h1>

<form method="GET" action="<?= BASE_URL ?>/admin/users" class="filter-bar" style="max-width:420px;">
    <?php if (!empty($filter)): ?>
        <input type="hidden" name="status" value="<?= htmlspecialchars($filter) ?>">
    <?php endif; ?>
    <div class="form-group">
        <label>Cari nama / email</label>
        <input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" placeholder="mis. user1@facilia.test">
    </div>
    <button type="submit">Cari</button>
</form>

<div class="filter-pills">
    <?php $qs = $keyword ? '&q=' . urlencode($keyword) : ''; ?>
    <a class="filter-pill <?= empty($filter) ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/users?<?= ltrim($qs, '&') ?>">Semua</a>
    <a class="filter-pill <?= $filter === 'PENDING' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/users?status=PENDING<?= $qs ?>">Pending</a>
    <a class="filter-pill <?= $filter === 'VERIFIED' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/users?status=VERIFIED<?= $qs ?>">Verified</a>
    <a class="filter-pill <?= $filter === 'REJECTED' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/users?status=REJECTED<?= $qs ?>">Rejected</a>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['role']) ?></td>
                    <td><span class="badge badge-<?= $u['verification_status'] ?>"><?= $u['verification_status'] ?></span></td>
                    <td>
                        <?php if ($u['verification_status'] === 'PENDING'): ?>
                            <form method="POST" action="<?= BASE_URL ?>/admin/users/verify" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="decision" value="VERIFIED">
                                <button type="submit">Approve</button>
                            </form>
                            <form method="POST" action="<?= BASE_URL ?>/admin/users/verify" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="decision" value="REJECTED">
                                <button type="submit" class="btn-danger">Reject</button>
                            </form>
                        <?php elseif ((int) $u['id'] === (int) $_SESSION['user_id']): ?>
                            <span class="muted">Akun Anda</span>
                        <?php else: ?>
                            <form method="POST" action="<?= BASE_URL ?>/admin/users/delete" style="display:inline"
                                  onsubmit="return confirm('Hapus permanen user ini? Aksi ini tidak bisa dibatalkan.');">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <button type="submit" class="btn-danger">Hapus</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
                <tr><td colspan="5" class="empty-state">Tidak ada user yang cocok.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="max-width:420px;">
    <h2>Buat Akun Langsung</h2>
    <form method="POST" action="<?= BASE_URL ?>/admin/users/create">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="form-group"><label>Nama</label><input type="text" name="name" required></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
        <div class="form-group"><label>Password</label><input type="password" name="password" minlength="8" required></div>
        <div class="form-group">
            <label>Role</label>
            <select name="role" required>
                <option value="USER">USER</option>
                <option value="PETUGAS">PETUGAS</option>
                <option value="ADMIN">ADMIN</option>
            </select>
        </div>
        <button type="submit">Buat Akun</button>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
