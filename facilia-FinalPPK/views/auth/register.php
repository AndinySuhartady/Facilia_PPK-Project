<?php $pageTitle = 'Register'; require __DIR__ . '/../layouts/header.php'; ?>
<?php $old = $_SESSION['old_input'] ?? []; unset($_SESSION['old_input']); ?>

<div class="card" style="max-width:420px;margin:0 auto;">
    <h1>Buat Akun</h1>
    <p style="color:#6b7280;font-size:0.9rem;">Akun baru berstatus PENDING sampai diverifikasi admin.</p>
    <form method="POST" action="<?= BASE_URL ?>/register">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="form-group">
            <label>Nama</label>
            <input type="text" name="name" maxlength="100" value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" maxlength="150" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" minlength="8" maxlength="72" required>
        </div>
        <button type="submit">Daftar</button>
    </form>
    <p style="margin-top:14px;font-size:0.9rem;">Sudah punya akun? <a href="<?= BASE_URL ?>/login">Login</a></p>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
