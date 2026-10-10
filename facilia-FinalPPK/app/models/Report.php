<?php

class Report
{
    /** Whitelist kategori laporan — dipakai form & validasi server (kontrak Modul 3). */
    public const CATEGORIES = ['Peralatan', 'Kebersihan', 'Kelistrikan', 'Jaringan', 'Lainnya'];

    /** Status yang masih dianggap "aktif" di antrean petugas. */
    public const ACTIVE_STATUSES = ['BARU', 'DIPROSES'];

    /** Transisi status yang sah — sumber kebenaran tunggal untuk validasi server. */
    public const ALLOWED_TRANSITIONS = [
        'BARU' => ['DIPROSES', 'DITOLAK'],
        'DIPROSES' => ['SELESAI', 'DITOLAK'],
        'SELESAI' => [],
        'DITOLAK' => [],
    ];

    /** Simpan laporan baru, selalu berstatus BARU. */
    public static function create(int $userId, int $facilityId, string $category, string $description, ?string $photoPath): bool
    {
        $stmt = getDB()->prepare(
            "INSERT INTO reports (user_id, facility_id, category, description, photo_path, status)
             VALUES (?, ?, ?, ?, ?, 'BARU')"
        );
        return $stmt->execute([$userId, $facilityId, $category, $description, $photoPath]);
    }

    /** Detail lengkap satu laporan (dengan nama pelapor & fasilitas) untuk halaman detail. */
    public static function findById(int $id): ?array
    {
        $stmt = getDB()->prepare(
            'SELECT rp.*, u.name AS reporter_name, u.email AS reporter_email,
                    f.name AS facility_name, f.location AS facility_location
             FROM reports rp
             JOIN users u ON u.id = rp.user_id
             JOIN facilities f ON f.id = rp.facility_id
             WHERE rp.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** "My Reports" — hanya laporan milik session.user_id. */
    public static function historyByUser(int $userId): array
    {
        $stmt = getDB()->prepare(
            'SELECT rp.*, f.name AS facility_name, f.location AS facility_location
             FROM reports rp JOIN facilities f ON f.id = rp.facility_id
             WHERE rp.user_id = ?
             ORDER BY rp.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Semua laporan (petugas), dengan filter status opsional. */
    public static function all(?string $status = null): array
    {
        $sql = "SELECT rp.*, u.name AS reporter_name, f.name AS facility_name
                FROM reports rp
                JOIN users u ON u.id = rp.user_id
                JOIN facilities f ON f.id = rp.facility_id";
        $params = [];

        if ($status) {
            $sql .= ' WHERE rp.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY rp.created_at DESC';

        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Queue petugas: laporan yang masih aktif ditangani (BARU/DIPROSES), lama dulu. */
    public static function activeQueue(): array
    {
        $stmt = getDB()->query(
            "SELECT rp.*, f.name AS facility_name, u.name AS reporter_name
             FROM reports rp
             JOIN facilities f ON f.id = rp.facility_id
             JOIN users u ON u.id = rp.user_id
             WHERE rp.status IN ('BARU', 'DIPROSES')
             ORDER BY rp.created_at ASC"
        );
        return $stmt->fetchAll();
    }

    public static function countByStatus(): array
    {
        $stmt = getDB()->query('SELECT status, COUNT(*) AS total FROM reports GROUP BY status');
        return $stmt->fetchAll();
    }

    /**
     * Ubah status laporan secara generik (BARU→DIPROSES→SELESAI/DITOLAK).
     * Validasi transisi (Report::ALLOWED_TRANSITIONS) dilakukan di controller
     * SEBELUM memanggil ini — model hanya menulis, bukan mengambil keputusan.
     * resolved_at diisi otomatis untuk status akhir (SELESAI/DITOLAK).
     */
    public static function updateStatus(int $id, string $currentStatus, string $status, ?string $resolutionNote): bool
    {
        $resolvedAt = in_array($status, ['SELESAI', 'DITOLAK'], true) ? date('Y-m-d H:i:s') : null;

        $stmt = getDB()->prepare(
            'UPDATE reports SET status = ?, resolution_note = ?, resolved_at = ? WHERE id = ? AND status = ?'
        );
        $stmt->execute([$status, $resolutionNote, $resolvedAt, $id, $currentStatus]);
        return $stmt->rowCount() > 0;
    }

    /** Frekuensi kerusakan per fasilitas & kategori, dengan filter periode opsional (Admin Analytics). */
    public static function frequencyByFacilityAndCategory(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = "SELECT f.id AS facility_id, f.name AS facility_name, f.location, rp.category, COUNT(*) AS total
                FROM reports rp
                JOIN facilities f ON f.id = rp.facility_id";

        $conditions = [];
        $params = [];

        if ($dateFrom) {
            $conditions[] = 'rp.created_at >= ?';
            $params[] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo) {
            $conditions[] = 'rp.created_at <= ?';
            $params[] = $dateTo . ' 23:59:59';
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' GROUP BY f.id, f.name, f.location, rp.category ORDER BY total DESC';

        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) $row['total'] = (int) $row['total'];
        unset($row);
        return $rows;
    }
}
