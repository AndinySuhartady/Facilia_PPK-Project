<?php $pageTitle = 'Ubah Password'; require __DIR__ . '/../layouts/header.php'; ?>

<h1>Ubah Password</h1>
<div class="card" style="max-width:420px;">
    <form method="POST" action="<?= BASE_URL ?>/account/password">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="form-group">
            <label>Password saat ini</label>
            <input type="password" name="current_password" required autocomplete="current-password">
        </div>
        <div class="form-group">
            <label>Password baru</label>
            <input type="password" name="new_password" minlength="8" maxlength="72" required autocomplete="new-password">
        </div>
        <div class="form-group">
            <label>Konfirmasi password baru</label>
            <input type="password" name="confirm_password" minlength="8" maxlength="72" required autocomplete="new-password">
        </div>
        <button type="submit">Simpan Password</button>
    </form>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
