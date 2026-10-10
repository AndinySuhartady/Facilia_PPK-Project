<?php
/**
 * Konfigurasi aplikasi umum: nama app, base URL, dan bootstrap session.
 */

define('APP_NAME', 'FACILIA');
// Seluruh aturan tanggal reservasi mengikuti waktu kampus (WIB), bukan timezone server.
date_default_timezone_set('Asia/Jakarta');
// Deteksi otomatis nama folder proyek, sehingga aplikasi tetap berjalan saat
// folder bernama facilia, facilia-ui-sidebar, atau nama lain di htdocs.
$scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/facilia/public/index.php'));
define('BASE_URL', rtrim($scriptDirectory, '/'));

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Helper redirect sederhana.
 */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

/**
 * Helper flash message (disimpan 1x tayang di session).
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function getFlash(): array
{
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Helper cek role & auth dari session (dipakai di view untuk tampilan kondisional).
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentRole(): ?string
{
    return $_SESSION['role'] ?? null;
}

/** Ambil (atau buat) CSRF token untuk form aksi kritikal (approve/reject/cancel/status). */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Verifikasi token dari form dengan yang tersimpan di session (timing-safe). */
function verifyCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
