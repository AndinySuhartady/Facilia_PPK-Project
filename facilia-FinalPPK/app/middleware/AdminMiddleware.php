<?php

require_once __DIR__ . '/AuthMiddleware.php';

/**
 * Menolak akses /admin/* untuk role selain ADMIN.
 * Penting: ini dicek di SERVER, bukan hanya menyembunyikan tombol di UI.
 */
class AdminMiddleware
{
    public static function handle(): void
    {
        AuthMiddleware::handle();

        if (currentRole() !== 'ADMIN') {
            http_response_code(403);
            require __DIR__ . '/../../views/errors/403.php';
            exit;
        }
    }
}
