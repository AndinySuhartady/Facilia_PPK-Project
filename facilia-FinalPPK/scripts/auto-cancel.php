<?php
// Hanya dijalankan melalui PHP CLI, tidak melalui URL browser.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
date_default_timezone_set('Asia/Jakarta');
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../app/models/Reservation.php';
try {
    $count = Reservation::autoCancelExpiredPending();
    echo date('Y-m-d H:i:s') . " WIB: $count reservasi kedaluwarsa dibatalkan.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Auto-cancel gagal: " . $e->getMessage() . "\n");
    exit(1);
}
