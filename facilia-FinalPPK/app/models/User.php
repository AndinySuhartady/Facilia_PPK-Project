<?php

class User
{
    /** Cari user berdasarkan email (dipakai saat login & cek unique). */
    public static function findByEmail(string $email): ?array
    {
        $stmt = getDB()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = getDB()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Registrasi mandiri: selalu role USER, verification_status PENDING. */
    public static function createSelfRegister(string $name, string $email, string $hashedPassword): int
    {
        $stmt = getDB()->prepare(
            'INSERT INTO users (name, email, password, role, verification_status)
             VALUES (?, ?, ?, \'USER\', \'PENDING\')'
        );
        $stmt->execute([$name, $email, $hashedPassword]);
        return (int) getDB()->lastInsertId();
    }

    /** Dibuat langsung oleh admin: role & verification_status bebas ditentukan admin. */
    public static function createByAdmin(string $name, string $email, string $hashedPassword, string $role, string $verificationStatus): int
    {
        $stmt = getDB()->prepare(
            'INSERT INTO users (name, email, password, role, verification_status)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, $hashedPassword, $role, $verificationStatus]);
        return (int) getDB()->lastInsertId();
    }

    /** Daftar user dengan filter opsional verification_status + search nama/email, untuk admin. */
    public static function all(?string $verificationStatus = null, ?string $keyword = null): array
    {
        $sql = 'SELECT * FROM users WHERE 1=1';
        $params = [];

        if ($verificationStatus) {
            $sql .= ' AND verification_status = ?';
            $params[] = $verificationStatus;
        }
        if ($keyword) {
            $sql .= ' AND (name LIKE ? OR email LIKE ?)';
            $like = '%' . $keyword . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function updateVerificationStatus(int $id, string $status): void
    {
        $stmt = getDB()->prepare('UPDATE users SET verification_status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public static function updateRole(int $id, string $role): void
    {
        $stmt = getDB()->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$role, $id]);
    }

    public static function updatePassword(int $id, string $hashedPassword): void
    {
        $stmt = getDB()->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hashedPassword, $id]);
    }

    /** Statistik ringan untuk dashboard (menggantikan count(User::all())). */
    public static function countAll(?string $verificationStatus = null): int
    {
        if ($verificationStatus) {
            $stmt = getDB()->prepare('SELECT COUNT(*) AS total FROM users WHERE verification_status = ?');
            $stmt->execute([$verificationStatus]);
        } else {
            $stmt = getDB()->query('SELECT COUNT(*) AS total FROM users');
        }
        return (int) $stmt->fetch()['total'];
    }

    public static function countByRole(string $role): int
    {
        $stmt = getDB()->prepare('SELECT COUNT(*) AS total FROM users WHERE role = ?');
        $stmt->execute([$role]);
        return (int) $stmt->fetch()['total'];
    }

    /** Cek apakah user masih punya riwayat reservasi/laporan (mencegah hapus data historis). */
    public static function isReferenced(int $id): bool
    {
        $stmt = getDB()->prepare(
            'SELECT
                (SELECT COUNT(*) FROM reservations WHERE user_id = ?) +
                (SELECT COUNT(*) FROM reports WHERE user_id = ?) AS total'
        );
        $stmt->execute([$id, $id]);
        return (int) $stmt->fetch()['total'] > 0;
    }

    /** Hapus user permanen. Hanya dipanggil setelah lolos semua pengecekan di controller. */
    public static function delete(int $id): void
    {
        $stmt = getDB()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }
}
