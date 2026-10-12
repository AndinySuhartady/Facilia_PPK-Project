<?php $pageTitle = 'Login'; require __DIR__ . '/../layouts/header.php'; ?>

<div class="card" style="max-width:400px;margin:0 auto;">
    <h1>Login</h1>
    <form method="POST" action="<?= BASE_URL ?>/login">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required autofocus>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Masuk</button>
    </form>
    <p style="margin-top:14px;font-size:0.9rem;">Belum punya akun? <a href="<?= BASE_URL ?>/register">Register</a></p>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
