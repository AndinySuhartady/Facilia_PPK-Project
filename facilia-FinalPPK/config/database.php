<?php
/**
 * Konfigurasi koneksi database (PDO/MySQL).
 * Sesuaikan kredensial di bawah dengan environment lokal (XAMPP default).
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'facilia');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Mengembalikan instance PDO singleton.
 * Menggunakan exception mode agar error query tidak silent.
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            // Samakan zona waktu sesi MySQL dengan PHP (WIB) agar NOW()/CURDATE() konsisten.
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+07:00'",
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (PHP_SAPI === 'cli') {
                throw $e;
            }
            http_response_code(500);
            die('Koneksi database gagal: ' . $e->getMessage());
        }
    }

    return $pdo;
}
