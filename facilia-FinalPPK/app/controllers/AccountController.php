<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../helpers/validation.php';

/** Fitur akun untuk semua role yang sudah login: ubah password & notifikasi. */
class AccountController
{
    public function showPassword(): void
    {
        require __DIR__ . '/../../views/account/password.php';
    }

    public function changePassword(): void
    {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Permintaan tidak valid atau sesi telah berubah.');
            redirect('/account/password');
        }
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $user = User::findById((int) $_SESSION['user_id']);
        if (!$user || !password_verify($current, $user['password'])) {
            setFlash('error', 'Password saat ini salah.');
            redirect('/account/password');
        }
        if (!isValidNewPassword($new)) {
            setFlash('error', 'Password baru harus 8–72 karakter.');
            redirect('/account/password');
        }
        if ($new !== $confirm) {
            setFlash('error', 'Konfirmasi password tidak sama.');
            redirect('/account/password');
        }
        if (password_verify($new, $user['password'])) {
            setFlash('error', 'Password baru tidak boleh sama dengan password saat ini.');
            redirect('/account/password');
        }

        User::updatePassword((int) $user['id'], password_hash($new, PASSWORD_DEFAULT));
        session_regenerate_id(true);
        setFlash('success', 'Password berhasil diubah.');
        redirect('/account/password');
    }

    public function notifications(): void
    {
        $notifications = Notification::latestForUser((int) $_SESSION['user_id']);
        require __DIR__ . '/../../views/account/notifications.php';
    }

    /**
     * GET /notifications/open?id=.. — buka satu notifikasi: tandai terbaca lalu
     * arahkan ke halaman detailnya, sehingga angka notifikasi langsung berkurang.
     */
    public function openNotification(): void
    {
        $userId = (int) $_SESSION['user_id'];
        $notification = Notification::findForUser((int) ($_GET['id'] ?? 0), $userId);
        if (!$notification) {
            setFlash('error', 'Notifikasi tidak ditemukan.');
            redirect('/notifications');
        }
        Notification::markRead((int) $notification['id'], $userId);
        $link = (string) ($notification['link'] ?? '');
        // Hanya izinkan path internal aplikasi (cegah open redirect).
        if ($link !== '' && str_starts_with($link, '/') && !str_starts_with($link, '//')) {
            redirect($link);
        }
        redirect('/notifications');
    }

    public function markNotificationsRead(): void
    {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Permintaan tidak valid atau sesi telah berubah.');
            redirect('/notifications');
        }
        Notification::markAllRead((int) $_SESSION['user_id']);
        redirect('/notifications');
    }
}
