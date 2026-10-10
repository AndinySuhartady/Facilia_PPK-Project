<?php

/**
 * Menolak akses ke halaman yang butuh login.
 * Dipanggil di awal controller/route yang butuh session aktif.
 */
class AuthMiddleware
{
    public static function handle(): void
    {
        if (!isLoggedIn()) {
            setFlash('error', 'Silakan login terlebih dahulu.');
            redirect('/login');
        }
    }
}
