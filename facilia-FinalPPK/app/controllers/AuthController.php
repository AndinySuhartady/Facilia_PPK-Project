<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/validation.php';

class AuthController
{
    /** GET /register — tampilkan form. */
    public function showRegister(): void
    {
        require __DIR__ . '/../../views/auth/register.php';
    }

    /** POST /register — Langkah 1–4 Fase 3. */
    public function register(): void
    {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Form kedaluwarsa. Muat ulang halaman lalu coba kembali.');
            redirect('/register');
        }
        $name = cleanInput($_POST['name'] ?? '');
        $email = cleanInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $errors = [];

        if ($name === '') {
            $errors[] = 'Nama wajib diisi.';
        }
        if (!isValidEmail($email)) {
            $errors[] = 'Format email tidak valid.';
        }
        if (!isValidPassword($password)) {
            $errors[] = 'Password minimal 8 karakter.';
        }
        if ($email !== '' && User::findByEmail($email)) {
            $errors[] = 'Email sudah terdaftar.';
        }

        if ($errors) {
            setFlash('errors', implode(' ', $errors));
            $_SESSION['old_input'] = ['name' => $name, 'email' => $email];
            redirect('/register');
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        User::createSelfRegister($name, $email, $hashed);

        setFlash('success', 'Registrasi berhasil. Akun Anda menunggu verifikasi admin sebelum bisa login.');
        redirect('/login');
    }

    /** GET /login — tampilkan form. */
    public function showLogin(): void
    {
        require __DIR__ . '/../../views/auth/login.php';
    }

    /** POST /login — Langkah 5–9 Fase 3. */
    public function login(): void
    {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Form kedaluwarsa. Muat ulang halaman lalu coba kembali.');
            redirect('/login');
        }
        $email = cleanInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            setFlash('error', 'Email atau password salah.');
            redirect('/login');
        }

        // Kebijakan: akun PENDING/REJECTED tidak boleh masuk sebagai user normal.
        if ($user['verification_status'] !== 'VERIFIED') {
            $reason = $user['verification_status'] === 'PENDING'
                ? 'Akun Anda masih menunggu verifikasi admin.'
                : 'Akun Anda ditolak. Hubungi admin untuk informasi lebih lanjut.';
            setFlash('error', $reason);
            redirect('/login');
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];

        $destinations = [
            'ADMIN' => '/admin/dashboard',
            'PETUGAS' => '/petugas/dashboard',
            'USER' => '/facilities',
        ];
        redirect($destinations[$user['role']] ?? '/');
    }

    /** POST /logout — hancurkan session setelah verifikasi CSRF. */
    public function logout(): void
    {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Permintaan logout tidak valid.');
            redirect('/facilities');
        }
        $_SESSION = [];
        session_destroy();
        redirect('/login');
    }
}
