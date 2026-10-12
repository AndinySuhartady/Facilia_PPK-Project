<?php

require_once __DIR__ . '/Notification.php';
require_once __DIR__ . '/../helpers/listing.php';

class Reservation
{
    /**
     * Batalkan otomatis request yang masih PENDING ketika tanggal reservasinya
     * sudah tiba. Batas keputusan petugas adalah sebelum hari reservasi.
     */
    public static function autoCancelExpiredPending(?string $today = null): int
    {
        $today ??= date('Y-m-d');
        $reason = 'Dibatalkan otomatis karena belum disetujui petugas sebelum tanggal reservasi.';
        $db = getDB();
        $ownTransaction = !$db->inTransaction();
        if ($ownTransaction) {
            $db->beginTransaction();
        }
        try {
            $select = $db->prepare("SELECT id, user_id FROM reservations WHERE status = 'PENDING' AND reservation_date <= ? FOR UPDATE");
            $select->execute([$today]);
            $expired = $select->fetchAll();
            $count = 0;
            if ($expired) {
                $stmt = $db->prepare(
                    "UPDATE reservations
                     SET status = 'CANCELLED', cancellation_reason = ?, cancelled_at = NOW()
                     WHERE status = 'PENDING' AND reservation_date <= ?"
                );
                $stmt->execute([$reason, $today]);
                $count = $stmt->rowCount();
                foreach ($expired as $row) {
                    Notification::create((int) $row['user_id'], 'Reservasi #' . (int) $row['id'] . ' dibatalkan otomatis karena belum disetujui sebelum tanggal reservasi.', '/reservation/detail?id=' . (int) $row['id']);
                }
            }
            if ($ownTransaction) {
                $db->commit();
            }
            return $count;
        } catch (Throwable $e) {
            if ($ownTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Ambil semua reservasi APPROVED untuk facility+tanggal tertentu.
     * Dipakai untuk: (1) menyusun grid availability, (2) cek overlap saat submit/approve.
     */
    public static function approvedByFacilityAndDate(int $facilityId, string $date): array
    {
        $stmt = getDB()->prepare(
            "SELECT start_time, end_time FROM reservations
             WHERE facility_id = ? AND reservation_date = ? AND status = 'APPROVED'
             ORDER BY start_time ASC"
        );
        $stmt->execute([$facilityId, $date]);
        return $stmt->fetchAll();
    }

    /**
     * Cek apakah rentang start-end yang diminta bentrok dengan reservasi APPROVED lain
     * pada facility+tanggal yang sama. $excludeId dipakai saat re-check approval
     * agar reservasi yang sedang diproses tidak membandingkan dengan dirinya sendiri.
     */
    public static function hasApprovedOverlap(int $facilityId, string $date, string $startTime, string $endTime, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM reservations
                WHERE facility_id = ? AND reservation_date = ? AND status = 'APPROVED'
                  AND start_time < ? AND end_time > ?";
        $params = [$facilityId, $date, $endTime, $startTime];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetch()['total'] > 0;
    }

    /** Batas jumlah pengajuan PENDING per user, mencegah spam antrean petugas. */
    public const MAX_PENDING_PER_USER = 5;

    public static function countPendingByUser(int $userId): int
    {
        $stmt = getDB()->prepare("SELECT COUNT(*) AS total FROM reservations WHERE user_id = ? AND status = 'PENDING'");
        $stmt->execute([$userId]);
        return (int) $stmt->fetch()['total'];
    }

    /** User sudah punya reservasi PENDING/APPROVED yang bentrok di tanggal+jam yang sama (fasilitas apa pun). */
    public static function userHasActiveOverlap(int $userId, string $date, string $startTime, string $endTime): bool
    {
        $stmt = getDB()->prepare(
            "SELECT COUNT(*) AS total FROM reservations
             WHERE user_id = ? AND reservation_date = ? AND status IN ('PENDING', 'APPROVED')
               AND start_time < ? AND end_time > ?"
        );
        $stmt->execute([$userId, $date, $endTime, $startTime]);
        return (int) $stmt->fetch()['total'] > 0;
    }

    /** Simpan reservasi baru dengan status PENDING. */
    public static function create(int $userId, int $facilityId, string $date, string $startTime, string $endTime, ?string $purpose): int
    {
        $stmt = getDB()->prepare(
            "INSERT INTO reservations (user_id, facility_id, reservation_date, start_time, end_time, purpose, status)
             VALUES (?, ?, ?, ?, ?, ?, 'PENDING')"
        );
        $stmt->execute([$userId, $facilityId, $date, $startTime, $endTime, $purpose]);
        return (int) getDB()->lastInsertId();
    }

    public static function findById(int $id): ?array
{
    $stmt = getDB()->prepare(
        'SELECT
            r.*,
            f.name AS facility_name,
            f.location AS facility_location, f.type AS facility_type,
            u.name AS user_name
         FROM reservations r
         JOIN facilities f ON f.id = r.facility_id
         JOIN users u ON u.id = r.user_id
         WHERE r.id = ?
         LIMIT 1'
    );

    $stmt->execute([$id]);

    $row = $stmt->fetch();

    return $row ?: null;
}

    /** Riwayat reservasi milik satu user (dashboard/history). */
    public static function historyByUser(int $userId): array
    {
        $stmt = getDB()->prepare(
            'SELECT r.*, f.name AS facility_name
             FROM reservations r JOIN facilities f ON f.id = r.facility_id
             WHERE r.user_id = ?
             ORDER BY r.reservation_date DESC, r.start_time DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Sorting riwayat reservasi user (whitelist). */
    public const HISTORY_SORTS = [
        'date'     => 'r.reservation_date %s, r.start_time %s',
        'created'  => 'r.created_at %s, r.id %s',
        'facility' => 'f.name %s, r.reservation_date DESC, r.start_time DESC',
        'status'   => 'r.status %s, r.reservation_date DESC, r.start_time DESC',
    ];

    /**
     * Riwayat reservasi user dengan search & sort.
     * $filters: q (fasilitas/tujuan), status, date (Y-m-d), sort, dir.
     */
    public static function searchHistoryByUser(int $userId, array $filters): array
    {
        $sql = 'SELECT r.*, f.name AS facility_name
                FROM reservations r JOIN facilities f ON f.id = r.facility_id
                WHERE r.user_id = ?';
        $params = [$userId];

        if (!empty($filters['q'])) {
            $sql .= ' AND (f.name LIKE ? OR r.purpose LIKE ?)';
            $params[] = likeKeyword($filters['q']);
            $params[] = likeKeyword($filters['q']);
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND r.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['date'])) {
            $sql .= ' AND r.reservation_date = ?';
            $params[] = $filters['date'];
        }

        $sort = array_key_exists($filters['sort'] ?? '', self::HISTORY_SORTS) ? $filters['sort'] : 'date';
        $dir = ($filters['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        $sql .= ' ORDER BY ' . sprintf(self::HISTORY_SORTS[$sort], $dir, $dir);

        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Batas pembatalan: sebelum waktu mulai, menurut timezone kampus (WIB). */
    public static function canOwnerCancel(array $reservation): bool
    {
        return in_array($reservation['status'], ['PENDING', 'APPROVED'], true)
            && $reservation['reservation_date'] . ' ' . $reservation['start_time'] > date('Y-m-d H:i:s');
    }

    /** Cancel milik user secara atomik; mencegah pembatalan ganda/state basi. */
    public static function cancelByOwner(int $id, int $userId, ?string $reason): bool
    {
        $stmt = getDB()->prepare(
            "UPDATE reservations
             SET status = 'CANCELLED', cancellation_reason = ?, cancelled_at = NOW()
             WHERE id = ? AND user_id = ? AND status IN ('PENDING', 'APPROVED')
               AND TIMESTAMP(reservation_date, start_time) > ?"
        );
        $stmt->execute([$reason, $id, $userId, date('Y-m-d H:i:s')]);
        return $stmt->rowCount() > 0;
    }

    /** Semua reservasi (petugas), dengan filter status opsional. */
    public static function all(?string $status = null): array
    {
        $sql = "SELECT r.*, u.name AS user_name, f.name AS facility_name
                FROM reservations r
                JOIN users u ON u.id = r.user_id
                JOIN facilities f ON f.id = r.facility_id";
        $params = [];

        if ($status) {
            $sql .= ' WHERE r.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY r.reservation_date ASC, r.start_time ASC';

        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Kolom yang boleh dipakai untuk sorting antrean (whitelist → aman dari SQL injection). */
    public const QUEUE_SORTS = [
        'date'     => 'r.reservation_date %s, r.start_time %s',
        'time'     => 'r.start_time %s, r.reservation_date %s',
        'facility' => 'f.name %s, r.reservation_date ASC, r.start_time ASC',
        'user'     => 'u.name %s, r.reservation_date ASC, r.start_time ASC',
        'created'  => 'r.created_at %s, r.id %s',
    ];

    /**
     * Antrean reservasi petugas dengan filter & sorting.
     * $filters: status, facility_id, date (Y-m-d), time (HH:MM — reservasi yang
     * sedang berlangsung pada jam tsb), user (kata kunci nama/email), sort, dir.
     */
    public static function search(array $filters): array
    {
        $sql = "SELECT r.*, u.name AS user_name, u.email AS user_email, f.name AS facility_name
                FROM reservations r
                JOIN users u ON u.id = r.user_id
                JOIN facilities f ON f.id = r.facility_id
                WHERE 1 = 1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= ' AND r.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['facility_id'])) {
            $sql .= ' AND r.facility_id = ?';
            $params[] = (int) $filters['facility_id'];
        }
        if (!empty($filters['date'])) {
            $sql .= ' AND r.reservation_date = ?';
            $params[] = $filters['date'];
        }
        if (!empty($filters['time'])) {
            // Reservasi yang mencakup jam tersebut (mulai <= jam < selesai).
            $sql .= ' AND r.start_time <= ? AND r.end_time > ?';
            $params[] = $filters['time'] . ':00';
            $params[] = $filters['time'] . ':00';
        }
        if (!empty($filters['user'])) {
            $sql .= ' AND (u.name LIKE ? OR u.email LIKE ?)';
            $keyword = '%' . addcslashes($filters['user'], '%_\\') . '%';
            $params[] = $keyword;
            $params[] = $keyword;
        }

        $sort = array_key_exists($filters['sort'] ?? '', self::QUEUE_SORTS) ? $filters['sort'] : 'date';
        $dir = strtoupper($filters['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $sql .= ' ORDER BY ' . sprintf(self::QUEUE_SORTS[$sort], $dir, $dir);

        $stmt = getDB()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Jumlah reservasi PENDING (badge sidebar petugas). */
    public static function countPending(): int
    {
        return (int) getDB()->query("SELECT COUNT(*) FROM reservations WHERE status = 'PENDING'")->fetchColumn();
    }

    /** Queue PENDING untuk petugas. */
    public static function pendingQueue(): array
    {
        return self::all('PENDING');
    }

    public static function countByStatus(): array
    {
        $stmt = getDB()->query('SELECT status, COUNT(*) AS total FROM reservations GROUP BY status');
        return $stmt->fetchAll();
    }

    /**
     * Memutuskan reservasi di dalam transaksi. Baris fasilitas dikunci agar dua
     * petugas tidak dapat meloloskan dua jadwal bertabrakan pada saat bersamaan.
     * Return: success | stale | conflict | invalid.
     */
    public static function decideSafely(int $id, string $status, ?string $rejectionReason): string
    {
        if (!in_array($status, ['APPROVED', 'REJECTED'], true)) {
            return 'invalid';
        }

        $db = getDB();
        try {
            $db->beginTransaction();

            $stmt = $db->prepare('SELECT * FROM reservations WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $reservation = $stmt->fetch();
            if (!$reservation || $reservation['status'] !== 'PENDING') {
                $db->rollBack();
                return 'stale';
            }

            if ($status === 'APPROVED') {
                $lock = $db->prepare('SELECT id, status FROM facilities WHERE id = ? FOR UPDATE');
                $lock->execute([(int) $reservation['facility_id']]);
                $facility = $lock->fetch();
                if (!$facility || $facility['status'] !== 'ACTIVE' || $reservation['reservation_date'] <= date('Y-m-d')) {
                    $db->rollBack();
                    return 'unavailable';
                }

                if (self::hasApprovedOverlap(
                    (int) $reservation['facility_id'],
                    $reservation['reservation_date'],
                    $reservation['start_time'],
                    $reservation['end_time'],
                    $id
                )) {
                    $db->rollBack();
                    return 'conflict';
                }
            }

            $update = $db->prepare(
                "UPDATE reservations SET status = ?, rejection_reason = ?
                 WHERE id = ? AND status = 'PENDING'"
            );
            $update->execute([$status, $rejectionReason, $id]);
            if ($update->rowCount() !== 1) {
                $db->rollBack();
                return 'stale';
            }

            $db->commit();
            if ($status === 'APPROVED') {
                Notification::create((int) $reservation['user_id'], 'Reservasi #' . $id . ' Anda telah DISETUJUI.', '/reservation/detail?id=' . $id);
            } else {
                Notification::create((int) $reservation['user_id'], 'Reservasi #' . $id . ' Anda DITOLAK. Alasan: ' . $rejectionReason, '/reservation/detail?id=' . $id);
            }
            return 'success';
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /** Batalkan reservasi APPROVED mendatang saat fasilitas masuk maintenance. */
    public static function cancelApprovedForMaintenance(int $facilityId, string $reason): int
    {
        $db = getDB();
        $select = $db->prepare("SELECT id, user_id FROM reservations WHERE facility_id = ? AND status = 'APPROVED' AND reservation_date >= CURDATE() FOR UPDATE");
        $select->execute([$facilityId]);
        $affected = $select->fetchAll();
        if (!$affected) {
            return 0;
        }
        $stmt = $db->prepare(
            "UPDATE reservations
             SET status = 'CANCELLED', cancellation_reason = ?, cancelled_at = NOW()
             WHERE facility_id = ? AND status = 'APPROVED' AND reservation_date >= CURDATE()"
        );
        $stmt->execute([$reason, $facilityId]);
        foreach ($affected as $row) {
            Notification::create((int) $row['user_id'], 'Reservasi #' . (int) $row['id'] . ' dibatalkan. Alasan: ' . $reason, '/reservation/detail?id=' . (int) $row['id']);
        }
        return $stmt->rowCount();
    }

    /**
     * Emergency cancel oleh petugas/admin terhadap reservasi yang SUDAH APPROVED
     * (mis. fasilitas mendadak rusak). Atomik: hanya berlaku jika status masih APPROVED.
     */
    public static function cancelApproved(int $id, string $cancellationReason): bool
    {
        $stmt = getDB()->prepare(
            "UPDATE reservations SET status = 'CANCELLED', cancellation_reason = ?, cancelled_at = NOW()
             WHERE id = ? AND status = 'APPROVED'"
        );
        $stmt->execute([$cancellationReason, $id]);
        if ($stmt->rowCount() === 0) {
            return false;
        }
        $owner = getDB()->prepare('SELECT user_id FROM reservations WHERE id = ?');
        $owner->execute([$id]);
        if ($row = $owner->fetch()) {
            Notification::create((int) $row['user_id'], 'Reservasi #' . $id . ' dibatalkan oleh petugas. Alasan: ' . $cancellationReason, '/reservation/detail?id=' . $id);
        }
        return true;
    }

    /**
     * Hitung occupancy per fasilitas dari reservations APPROVED saja
     * (PENDING tidak boleh dihitung — sesuai kontrak dokumen).
     */
    public static function analyticsPeriod(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $dateFrom = $dateFrom ?: date('Y-m-01');
        $dateTo = $dateTo ?: date('Y-m-t');
        foreach ([$dateFrom, $dateTo] as $date) {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (!$parsed || $parsed->format('Y-m-d') !== $date) {
                throw new InvalidArgumentException('Periode harus berupa tanggal yang valid.');
            }
        }
        if ($dateFrom > $dateTo) throw new InvalidArgumentException('Tanggal awal tidak boleh setelah tanggal akhir.');
        return [$dateFrom, $dateTo];
    }

    public static function occupancyByFacility(?string $dateFrom = null, ?string $dateTo = null): array
    {
        [$dateFrom, $dateTo] = self::analyticsPeriod($dateFrom, $dateTo);
        $days = (new DateTimeImmutable($dateFrom))->diff(new DateTimeImmutable($dateTo))->days + 1;
        $availableHours = $days * 13; // 07:00–20:00, semua hari pada periode inklusif.
        $stmt = getDB()->prepare("SELECT f.id, f.name, f.location,
            COUNT(r.id) AS approved_count,
            COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(r.end_time, r.start_time))) / 3600, 0) AS used_hours
            FROM facilities f LEFT JOIN reservations r ON r.facility_id = f.id
            AND r.status = 'APPROVED' AND r.reservation_date BETWEEN ? AND ?
            GROUP BY f.id, f.name, f.location ORDER BY used_hours DESC, f.id");
        $stmt->execute([$dateFrom, $dateTo]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['approved_count'] = (int) $row['approved_count'];
            $row['used_hours'] = round((float) $row['used_hours'], 2);
            $row['available_hours'] = $availableHours;
            $row['occupancy_percent'] = round($row['used_hours'] / $availableHours * 100, 2);
            $row['period_from'] = $dateFrom;
            $row['period_to'] = $dateTo;
        }
        unset($row);
        return $rows;
    }
}
