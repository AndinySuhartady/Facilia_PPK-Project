<?php

/**
 * Notifikasi dalam aplikasi. Semua method aman dipanggil pada database lama
 * yang belum punya tabel `notifications`: kegagalan ditelan agar alur utama
 * (approve/cancel/dll.) tidak ikut gagal.
 */
class Notification
{
    public static function create(int $userId, string $message, ?string $link = null): void
    {
        try {
            $stmt = getDB()->prepare('INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)');
            $stmt->execute([$userId, mb_substr($message, 0, 500), $link]);
        } catch (Throwable $e) {
            // Abaikan: notifikasi bersifat pelengkap.
        }
    }

    public static function unreadCount(int $userId): int
    {
        try {
            $stmt = getDB()->prepare('SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0');
            $stmt->execute([$userId]);
            return (int) $stmt->fetch()['total'];
        } catch (Throwable $e) {
            return 0;
        }
    }

    public static function latestForUser(int $userId, int $limit = 50): array
    {
        try {
            $stmt = getDB()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT ' . (int) $limit);
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    /** Satu notifikasi milik user (null jika bukan miliknya / tidak ada). */
    public static function findForUser(int $id, int $userId): ?array
    {
        try {
            $stmt = getDB()->prepare('SELECT * FROM notifications WHERE id = ? AND user_id = ? LIMIT 1');
            $stmt->execute([$id, $userId]);
            return $stmt->fetch() ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /** Tandai satu notifikasi sudah dibaca (hanya milik user tsb). */
    public static function markRead(int $id, int $userId): void
    {
        try {
            getDB()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ? AND is_read = 0')->execute([$id, $userId]);
        } catch (Throwable $e) {
        }
    }

    /**
     * Tandai dibaca semua notifikasi yang menunjuk ke halaman detail tertentu,
     * mis. '/reservation/detail?id=5'. Dipanggil saat user membuka halaman detail
     * itu, sehingga angka notifikasi berkurang walau dibuka bukan dari menu Notifikasi.
     */
    public static function markReadByLink(int $userId, string $link): void
    {
        try {
            getDB()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND link = ? AND is_read = 0')->execute([$userId, $link]);
        } catch (Throwable $e) {
        }
    }

    public static function markAllRead(int $userId): void
    {
        try {
            getDB()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0')->execute([$userId]);
        } catch (Throwable $e) {
        }
    }
}
