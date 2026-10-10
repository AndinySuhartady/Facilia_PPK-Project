<?php

require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../models/Facility.php';
require_once __DIR__ . '/../helpers/slots.php';
require_once __DIR__ . '/../helpers/validation.php';

class ReservationController
{
    private function requireUserRole(): void
    {
        if (currentRole() !== 'USER') {
            http_response_code(403);
            require __DIR__ . '/../../views/errors/403.php';
            exit;
        }
    }
    /**
     * POST /reservation/create
     * Checklist server wajib (storeReservation) — lihat komentar inline.
     * Jam mulai/selesai bebas dipilih user (mis. 07:00–09:00 = 2 jam / 4 slot),
     * tetap tervalidasi penuh terhadap aturan 07:00–20:00, kelipatan 30 menit, dan overlap.
     */
    public function store(): void
    {
        $this->requireUserRole();
        // 1) Session user aktif — dijamin oleh AuthMiddleware di router sebelum method ini dipanggil.
        $userId = (int) $_SESSION['user_id'];

        $facilityId = (int) ($_POST['facility_id'] ?? 0);
        $date = cleanInput($_POST['reservation_date'] ?? '');
        $startTime = cleanInput($_POST['start_time'] ?? '');
        $endTime = cleanInput($_POST['end_time'] ?? '');
        $purpose = cleanInput($_POST['purpose'] ?? '');
        $confirmed = ($_POST['confirmed'] ?? '') === '1';
        $csrf = $_POST['csrf_token'] ?? '';

        $redirectBack = '/facility/detail?id=' . $facilityId . '&date=' . $date;

        // Konfirmasi UI tetap diperkuat di server agar request tidak bisa langsung
        // dikirim tanpa melewati crosscheck pengguna.
        if (!verifyCsrfToken($csrf)) {
            setFlash('error', 'Permintaan tidak valid atau sesi telah berubah. Muat ulang halaman.');
            redirect($redirectBack);
        }

        if (!$confirmed) {
            setFlash('error', 'Reservasi belum dikonfirmasi. Silakan periksa data lalu konfirmasi kembali.');
            redirect($redirectBack);
        }

        // 2) Facility ditemukan.
        $facility = $facilityId ? Facility::findById($facilityId) : null;
        if (!$facility) {
            setFlash('error', 'Fasilitas tidak ditemukan.');
            redirect('/facilities');
        }

        // 3) Facility status harus ACTIVE (MAINTENANCE/INACTIVE tidak boleh dipesan).
        if ($facility['status'] !== 'ACTIVE') {
            setFlash('error', 'Fasilitas sedang tidak tersedia untuk reservasi (status: ' . $facility['status'] . ').');
            redirect($redirectBack);
        }

        // 4–9) Validasi tanggal, jam, kelipatan 30 menit, rentang 07:00–20:00 — lihat helper slots.php.
        $errors = validateReservationTime($date, $startTime, $endTime);
        if ($purpose === '') $errors[] = 'Tujuan penggunaan wajib diisi.';
        elseif (strlen($purpose) > 255) $errors[] = 'Tujuan penggunaan maksimal 255 karakter.';

        if ($errors) {
            setFlash('errors', implode(' ', $errors));
            redirect($redirectBack);
        }

        // 10) Tidak boleh overlap dengan reservasi APPROVED lain pada facility+tanggal yang sama.
        //     Karena user boleh memilih rentang bebas (multi-slot), overlap dicek langsung
        //     terhadap start_time/end_time yang dipilih — bukan per slot 30 menit satu-satu.
        if (Reservation::hasApprovedOverlap($facilityId, $date, $startTime, $endTime)) {
            setFlash('error', 'Jadwal bentrok dengan reservasi lain yang sudah disetujui pada rentang tersebut.');
            redirect($redirectBack);
        }

        // 11) Lolos semua validasi → simpan PENDING.
        Reservation::create($userId, $facilityId, $date, $startTime, $endTime, $purpose);

        setFlash('success', 'Reservasi berhasil diajukan dan menunggu persetujuan petugas.');
        redirect('/reservation/history');
    }

    /** GET /reservation/history — riwayat reservasi milik user yang login. */
    public function history(): void
    {
        $this->requireUserRole();
        Reservation::autoCancelExpiredPending();
        $userId = (int) $_SESSION['user_id'];
        $reservations = Reservation::historyByUser($userId);
        require __DIR__ . '/../../views/user/reservation-history.php';
    }

    /** GET /reservation/detail: detail hanya untuk pemilik reservasi. */
    public function detail(): void
    {
        $this->requireUserRole();
        Reservation::autoCancelExpiredPending();
        $id = (int) ($_GET['id'] ?? 0);
        $reservation = $id ? Reservation::findById($id) : null;
        if (!$reservation || (int) $reservation['user_id'] !== (int) $_SESSION['user_id']) {
            http_response_code(404);
            echo 'Reservasi tidak ditemukan.';
            return;
        }
        require __DIR__ . '/../../views/user/reservation-detail.php';
    }

    /** POST /reservation/cancel — hanya pemilik reservasi yang boleh membatalkan. */
    public function cancel(): void
    {
        $this->requireUserRole();
        $id = (int) ($_POST['id'] ?? 0);
        $reason = cleanInput($_POST['cancellation_reason'] ?? '') ?: null;
        $confirmed = ($_POST['confirmed'] ?? '') === '1';
        $userId = (int) $_SESSION['user_id'];
        $csrf = $_POST['csrf_token'] ?? '';

        if (!verifyCsrfToken($csrf)) {
            setFlash('error', 'Permintaan tidak valid atau sesi telah berubah.');
            redirect('/reservation/history');
        }

        if (!$confirmed) {
            setFlash('error', 'Pembatalan belum dikonfirmasi.');
            redirect('/reservation/history');
        }

        $reservation = $id ? Reservation::findById($id) : null;

        if (!$reservation) {
            setFlash('error', 'Reservasi tidak ditemukan.');
            redirect('/reservation/history');
        }

        // Ownership check — user tidak boleh membatalkan reservasi milik user lain.
        if ((int) $reservation['user_id'] !== $userId) {
            http_response_code(403);
            setFlash('error', 'Anda tidak berhak membatalkan reservasi ini.');
            redirect('/reservation/history');
        }

        if (!in_array($reservation['status'], ['PENDING', 'APPROVED'], true)) {
            setFlash('error', 'Reservasi ini tidak bisa dibatalkan lagi (status: ' . $reservation['status'] . ').');
            redirect('/reservation/history');
        }

        if (!Reservation::canOwnerCancel($reservation)) {
            setFlash('error', 'Pembatalan hanya diperbolehkan sebelum jam mulai reservasi (WIB).');
            redirect('/reservation/history');
        }

        if (!Reservation::cancelByOwner($id, $userId, $reason)) {
            setFlash('error', 'Reservasi gagal dibatalkan karena statusnya sudah berubah. Muat ulang riwayat.');
            redirect('/reservation/history');
        }

        setFlash('success', 'Reservasi berhasil dibatalkan.');
        redirect('/reservation/history');
    }
}
