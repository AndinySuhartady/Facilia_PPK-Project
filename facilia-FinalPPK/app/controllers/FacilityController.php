<?php

require_once __DIR__ . '/../models/Facility.php';
require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../helpers/slots.php';
require_once __DIR__ . '/../helpers/validation.php';

class FacilityController
{
    /** GET /facilities — daftar SEMUA fasilitas (termasuk INACTIVE) + search/filter. */
    public function index(): void
    {
        $keyword = trim($_GET['q'] ?? '');
        $type = trim($_GET['type'] ?? '');
        $minCapacity = isset($_GET['min_capacity']) && $_GET['min_capacity'] !== ''
            ? (int) $_GET['min_capacity']
            : null;

        $facilities = Facility::searchPublic($keyword ?: null, $type ?: null, $minCapacity);
        $types = Facility::distinctTypes();

        require __DIR__ . '/../../views/public/facilities.php';
    }

    /**
     * GET /facility/detail?id=..&date=.. — detail fasilitas + availability tanggal terpilih.
     * Availability ditampilkan sebagai grid 30 menit (read-only, untuk bantu keputusan),
     * sedangkan reservasi sesungguhnya memakai jam mulai/selesai bebas di ReservationController.
     */
    public function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $facility = $id ? Facility::findById($id) : null;

        if (!$facility) {
            http_response_code(404);
            echo 'Fasilitas tidak ditemukan.';
            return;
        }

        // Fasilitas INACTIVE tetap muncul di listing, tapi detail & availability-nya diblokir.
        if ($facility['status'] === 'INACTIVE') {
            require __DIR__ . '/../../views/public/facility-inactive.php';
            return;
        }

        $minimumReservationDate = date('Y-m-d', strtotime('+1 day'));
        $maximumReservationDate = date('Y-m-d', strtotime('+90 days'));
        $date = (string) ($_GET['date'] ?? $minimumReservationDate);
        // Reservasi wajib minimal H-1 dan maksimal 90 hari ke depan; tanggal tidak valid dikembalikan ke default.
        if (!isValidDateString($date) || $date < $minimumReservationDate) {
            $date = $minimumReservationDate;
        } elseif ($date > $maximumReservationDate) {
            $date = $maximumReservationDate;
        }

        $approved = Reservation::approvedByFacilityAndDate($id, $date);
        $availabilityGrid = buildAvailabilityGrid($approved);
        if ($facility['status'] !== 'ACTIVE') {
            foreach ($availabilityGrid as &$segment) $segment['available'] = false;
            unset($segment);
        }
        $timeOptions = generateTimeBoundaries();

        require __DIR__ . '/../../views/public/facility-detail.php';
    }
}
