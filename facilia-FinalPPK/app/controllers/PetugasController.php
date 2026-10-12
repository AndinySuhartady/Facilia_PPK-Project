<?php

require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../models/Facility.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../helpers/validation.php';

class PetugasController
{
    /** GET /petugas/dashboard — ringkasan reservasi & laporan yang butuh tindakan. */
    public function dashboard(): void
    {
        Reservation::autoCancelExpiredPending();
        $reservationStats = ['PENDING' => 0, 'APPROVED' => 0, 'REJECTED' => 0, 'CANCELLED' => 0];
        foreach (Reservation::countByStatus() as $row) {
            $reservationStats[$row['status']] = (int) $row['total'];
        }

        $reportStats = ['BARU' => 0, 'DIPROSES' => 0, 'SELESAI' => 0, 'DITOLAK' => 0];
        foreach (Report::countByStatus() as $row) {
            $reportStats[$row['status']] = (int) $row['total'];
        }

        // Data ringkas dashboard diambil dari semua status agar tabel terbaru
        // tetap informatif, bukan hanya data yang masih pending/aktif.
        $latestReservations = array_slice(Reservation::all(), 0, 5);
        $latestReports = array_slice(Report::all(), 0, 5);
        $dashboardFacilities = Facility::all();
        $totalReservations = array_sum($reservationStats);
        $totalReports = array_sum($reportStats);

        require __DIR__ . '/../../views/petugas/dashboard.php';
    }

    // ---------------------------------------------------------------
    // RESERVATION QUEUE (Fase 6)
    // ---------------------------------------------------------------

    /** GET /petugas/reservations — antrean reservasi + filter status. */
    public function reservationQueue(): void
    {
        Reservation::autoCancelExpiredPending();
        $status = $_GET['status'] ?? '';
        if (!isInWhitelist($status, ['', 'PENDING', 'APPROVED', 'REJECTED', 'CANCELLED'])) {
            $status = '';
        }
        require_once __DIR__ . '/../helpers/slots.php';

        // Filter tambahan: fasilitas, tanggal (kalender), jam, kata kunci user, dan sorting.
        $facilityId = (int) ($_GET['facility_id'] ?? 0);
        $date = trim($_GET['date'] ?? '');
        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
            $date = '';
        }
        $timeOptions = generateTimeBoundaries();
        $time = trim($_GET['time'] ?? '');
        if (!in_array($time, $timeOptions, true)) {
            $time = '';
        }
        $userKeyword = mb_substr(trim($_GET['user'] ?? ''), 0, 100);
        $sort = array_key_exists($_GET['sort'] ?? '', Reservation::QUEUE_SORTS) ? $_GET['sort'] : 'date';
        $dir = strtolower($_GET['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $filters = [
            'status'      => $status,
            'facility_id' => $facilityId,
            'date'        => $date,
            'time'        => $time,
            'user'        => $userKeyword,
            'sort'        => $sort,
            'dir'         => $dir,
        ];
        $reservations = Reservation::search($filters);
        $facilities = Facility::all();
        $hasActiveFilter = $facilityId || $date !== '' || $time !== '' || $userKeyword !== '';

        require __DIR__ . '/../../views/petugas/reservation-queue.php';
    }

    /**
     * GET/POST /petugas/reservations/detail?id=..
     * Approve/reject (hanya jika PENDING) atau emergency-cancel (hanya jika APPROVED).
     * Overlap di-re-check tepat sebelum approve agar dua reservasi PENDING yang identik
     * tidak bisa dua-duanya disetujui (race condition dua petugas berbeda).
     */
    public function reservationDetail(): void
    {
        Reservation::autoCancelExpiredPending();
        $id = (int) ($_GET['id'] ?? 0);
        $reservation = $id ? Reservation::findById($id) : null;

        if (!$reservation) {
            http_response_code(404);
            echo 'Reservasi tidak ditemukan.';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? '';
            $decision = trim($_POST['decision'] ?? '');
            $rejectionReason = cleanInput($_POST['rejection_reason'] ?? '');
            $cancellationReason = cleanInput($_POST['cancellation_reason'] ?? '');
            $confirmed = ($_POST['confirmed'] ?? '') === '1';

            if (!verifyCsrfToken($csrf)) {
                setFlash('error', 'Permintaan tidak valid. Muat ulang halaman.');
            } elseif (!$confirmed) {
                setFlash('error', 'Tindakan belum melewati konfirmasi ganda.');
            } elseif ($decision === 'CANCELLED') {
                // Emergency cancellation — hanya untuk reservasi APPROVED.
                if ($reservation['status'] !== 'APPROVED') {
                    setFlash('error', 'Hanya reservasi APPROVED yang dapat dibatalkan.');
                } elseif (!isMeaningfulReason($cancellationReason, 10)) {
                    setFlash('error', 'Alasan pembatalan minimal 10 karakter dan harus berisi penjelasan, bukan hanya angka/simbol.');
                } elseif (!isWithinLength($cancellationReason, 255)) {
                    setFlash('error', 'Alasan pembatalan maksimal 255 karakter.');
                } elseif (Reservation::cancelApproved($id, $cancellationReason)) {
                    setFlash('success', 'Reservasi berhasil dibatalkan.');
                    redirect('/petugas/reservations/detail?id=' . $id);
                } else {
                    setFlash('error', 'Reservasi gagal dibatalkan atau status telah berubah.');
                }
            } elseif (in_array($decision, ['APPROVED', 'REJECTED'], true)) {
                if ($reservation['status'] !== 'PENDING') {
                    setFlash('error', 'Reservasi ini sudah pernah diproses.');
                } elseif ($decision === 'REJECTED' && !isMeaningfulReason($rejectionReason, 10)) {
                    setFlash('error', 'Alasan penolakan minimal 10 karakter dan harus berisi penjelasan.');
                } elseif ($decision === 'REJECTED' && !isWithinLength($rejectionReason, 255)) {
                    setFlash('error', 'Alasan penolakan maksimal 255 karakter.');
                } else {
                    $result = Reservation::decideSafely(
                        $id,
                        $decision,
                        $decision === 'REJECTED' ? $rejectionReason : null
                    );
                    if ($result === 'success') {
                        setFlash('success', 'Keputusan reservasi berhasil disimpan.');
                        redirect('/petugas/reservations/detail?id=' . $id);
                    } elseif ($result === 'unavailable') {
                        setFlash('error', 'Reservasi tidak dapat disetujui: fasilitas tidak aktif/dalam perbaikan atau batas persetujuan sudah lewat.');
                    } elseif ($result === 'conflict') {
                        setFlash('error', 'Reservasi tidak dapat disetujui karena jadwal bertabrakan dengan reservasi APPROVED lain.');
                    } else {
                        setFlash('error', 'Reservasi gagal diperbarui karena sudah diproses petugas lain atau statusnya berubah.');
                    }
                }
            } else {
                setFlash('error', 'Tindakan tidak valid.');
            }

            // Re-fetch data terbaru agar halaman tidak menampilkan state basi setelah error.
            $reservation = Reservation::findById($id);
        }

        require __DIR__ . '/../../views/petugas/reservation-detail.php';
    }

    // ---------------------------------------------------------------
    // REPORT QUEUE (Fase 7)
    // ---------------------------------------------------------------

    /** GET /petugas/reports — antrean laporan + filter status. */
    public function reportQueue(): void
    {
        $status = $_GET['status'] ?? '';
        if (!isInWhitelist($status, ['', 'BARU', 'DIPROSES', 'SELESAI', 'DITOLAK'])) {
            $status = '';
        }
        $categories = Report::CATEGORIES;
        $filters = [
            'q'        => listingKeyword('q'),
            'status'   => $status,
            'category' => listingChoice('category', Report::CATEGORIES),
            'date'     => listingDate('date'),
            'sort'     => listingChoice('sort', array_keys(Report::LIST_SORTS), 'created'),
            'dir'      => listingChoice('dir', ['asc', 'desc'], 'desc'),
        ];
        $hasActiveFilter = $filters['q'] !== '' || $filters['category'] !== '' || $filters['date'] !== '';
        $reports = Report::search($filters);
        require __DIR__ . '/../../views/petugas/report-queue.php';
    }

    /**
     * GET/POST /petugas/reports/detail?id=..
     * Dua jenis aksi: (1) ubah status laporan sesuai Report::ALLOWED_TRANSITIONS,
     * (2) ubah status fasilitas ACTIVE⇄MAINTENANCE lewat Facility::updateOperationalStatus().
     */
    public function reportDetail(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $report = $id ? Report::findById($id) : null;

        if (!$report) {
            http_response_code(404);
            echo 'Laporan tidak ditemukan.';
            return;
        }

        $facility = Facility::findById((int) $report['facility_id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? '';
            $actionType = $_POST['action_type'] ?? '';

            if (!verifyCsrfToken($csrf)) {
                setFlash('error', 'Permintaan tidak valid. Muat ulang halaman.');
            } elseif ($actionType === 'facility_status') {
                $newStatus = $_POST['facility_status'] ?? '';

                if ($newStatus === 'MAINTENANCE') {
                    if ($report['status'] !== 'DIPROSES') {
                        setFlash('error', 'Fasilitas hanya dapat ditandai dalam perbaikan saat laporan berstatus DIPROSES.');
                    } else {
                        $db = getDB();
                        try {
                            $db->beginTransaction();
                            if (!Facility::updateOperationalStatus((int) $facility['id'], 'ACTIVE', 'MAINTENANCE')) {
                                $db->rollBack();
                                setFlash('error', 'Status fasilitas gagal diperbarui: status saat ini bukan ACTIVE (mungkin sudah diubah petugas/admin lain).');
                            } else {
                                $cancelled = Reservation::cancelApprovedForMaintenance(
                                    (int) $facility['id'],
                                    'Dibatalkan karena fasilitas mendadak rusak dan sedang dalam perbaikan.'
                                );
                                $db->commit();
                                setFlash('success', 'Fasilitas ditandai dalam perbaikan. ' . $cancelled . ' reservasi mendatang dibatalkan otomatis.');
                                redirect('/petugas/reports/detail?id=' . $id);
                            }
                        } catch (Throwable $e) {
                            if ($db->inTransaction()) $db->rollBack();
                            setFlash('error', 'Status fasilitas gagal diperbarui. Silakan coba lagi.');
                        }
                    }
                } elseif ($newStatus === 'ACTIVE') {
                    if (!in_array($report['status'], ['SELESAI', 'DITOLAK'], true)) {
                        setFlash('error', 'Fasilitas dapat diaktifkan kembali setelah laporan ditutup (SELESAI/DITOLAK).');
                    } elseif (Report::countOpenByFacility((int) $facility['id']) > 0) {
                        setFlash('error', 'Fasilitas belum dapat diaktifkan: masih ada laporan lain (BARU/DIPROSES) untuk fasilitas ini.');
                    } elseif (Facility::updateOperationalStatus((int) $facility['id'], 'MAINTENANCE', 'ACTIVE')) {
                        setFlash('success', 'Fasilitas berhasil diaktifkan kembali.');
                        redirect('/petugas/reports/detail?id=' . $id);
                    } else {
                        setFlash('error', 'Status fasilitas gagal diperbarui (mungkin bukan lagi MAINTENANCE).');
                    }
                } else {
                    setFlash('error', 'Status fasilitas yang dipilih tidak valid.');
                }
            } elseif ($actionType === 'report_status') {
                $newStatus = trim($_POST['status'] ?? '');
                $resolutionNote = cleanInput($_POST['resolution_note'] ?? '');
                $allowed = Report::ALLOWED_TRANSITIONS[$report['status']] ?? [];

                if (!in_array($newStatus, $allowed, true)) {
                    setFlash('error', 'Perubahan status laporan tidak diperbolehkan dari status saat ini.');
                } elseif (in_array($newStatus, ['SELESAI', 'DITOLAK'], true) && !isMeaningfulReason($resolutionNote, 10)) {
                    setFlash('error', 'Catatan petugas untuk status ' . $newStatus . ' minimal 10 karakter dan harus berisi penjelasan.');
                } elseif (!isWithinLength($resolutionNote, 2000)) {
                    setFlash('error', 'Catatan petugas maksimal 2000 karakter.');
                } elseif (Report::updateStatus($id, $report['status'], $newStatus, $resolutionNote ?: null)) {
                    Notification::create((int) $report['user_id'], 'Laporan #' . $id . ' (' . $report['facility_name'] . ') kini berstatus ' . $newStatus . '.', '/report/detail?id=' . $id);
                    setFlash('success', 'Status laporan berhasil diperbarui.');
                    redirect('/petugas/reports/detail?id=' . $id);
                } else {
                    setFlash('error', 'Status laporan gagal diperbarui karena sudah diubah petugas lain. Muat ulang halaman.');
                }
            } else {
                setFlash('error', 'Tindakan tidak valid.');
            }

            $report = Report::findById($id);
            $facility = Facility::findById((int) $report['facility_id']);
        }

        $availableStatuses = Report::ALLOWED_TRANSITIONS[$report['status']] ?? [];

        require __DIR__ . '/../../views/petugas/report-detail.php';
    }
}
