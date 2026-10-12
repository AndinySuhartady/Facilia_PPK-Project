<?php

/**
 * Helper untuk slot waktu reservasi.
 *
 * Kebijakan: user TIDAK memilih slot 30-menit satu per satu, melainkan memilih
 * jam mulai & jam selesai bebas (kelipatan 30 menit) dalam rentang 07:00–20:00,
 * sehingga satu reservasi bisa mencakup beberapa slot sekaligus
 * (contoh: 07:00–09:00 = 4 slot berturutan / 2 jam).
 * Validasi "sah" tetap mengikuti aturan dokumen — lihat validateReservationTime().
 */

const RESERVATION_OPEN_TIME = '07:00';
const RESERVATION_CLOSE_TIME = '20:00';
const RESERVATION_SLOT_MINUTES = 30;
const RESERVATION_MIN_DAYS_AHEAD = 1;
const RESERVATION_MAX_DAYS_AHEAD = 90;

/** Ubah "HH:MM" menjadi total menit sejak 00:00, atau null jika format tidak valid. */
function timeToMinutes(string $time): ?int
{
    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $m)) {
        return null;
    }
    return ((int) $m[1]) * 60 + (int) $m[2];
}

/** Cek waktu berada tepat di kelipatan 30 menit (hanya menit 00 atau 30). */
function isOnHalfHourGrid(string $time): bool
{
    $minutes = timeToMinutes($time);
    return $minutes !== null && ($minutes % RESERVATION_SLOT_MINUTES === 0);
}

/**
 * Generate daftar titik waktu 07:00, 07:30, ..., 20:00 (boundary, bukan segmen).
 * Dipakai untuk mengisi pilihan dropdown jam mulai/selesai.
 */
function generateTimeBoundaries(): array
{
    $boundaries = [];
    $start = timeToMinutes(RESERVATION_OPEN_TIME);
    $end = timeToMinutes(RESERVATION_CLOSE_TIME);

    for ($m = $start; $m <= $end; $m += RESERVATION_SLOT_MINUTES) {
        $boundaries[] = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
    }
    return $boundaries;
}

/**
 * Validasi lengkap aturan waktu reservasi sesuai checklist storeReservation().
 * Mengembalikan array pesan error (kosong berarti valid).
 * Ini yang membuat rentang bebas (mis. 07:00–09:00) tetap "sah" karena
 * setiap syarat dicek langsung terhadap start_time & end_time yang dipilih,
 * bukan terhadap satu slot tunggal.
 */
function validateReservationTime(string $date, string $startTime, string $endTime): array
{
    $errors = [];

    $minimumDate = date('Y-m-d', strtotime('+' . RESERVATION_MIN_DAYS_AHEAD . ' day'));
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
        $errors[] = 'Tanggal tidak valid.';
    } elseif ($date > date('Y-m-d', strtotime('+' . RESERVATION_MAX_DAYS_AHEAD . ' day'))) {
        $errors[] = 'Reservasi maksimal ' . RESERVATION_MAX_DAYS_AHEAD . ' hari ke depan.';
    } elseif ($date < $minimumDate) {
        $errors[] = 'Reservasi minimal H-1. Pilih tanggal ' . date('d-m-Y', strtotime($minimumDate)) . ' atau setelahnya.';
    }

    $startMinutes = timeToMinutes($startTime);
    $endMinutes = timeToMinutes($endTime);
    $openMinutes = timeToMinutes(RESERVATION_OPEN_TIME);
    $closeMinutes = timeToMinutes(RESERVATION_CLOSE_TIME);

    if ($startMinutes === null || $endMinutes === null) {
        $errors[] = 'Format jam tidak valid.';
        return $errors; // tidak bisa lanjut cek lain tanpa waktu valid
    }

    if (!isOnHalfHourGrid($startTime) || !isOnHalfHourGrid($endTime)) {
        $errors[] = 'Jam mulai dan jam selesai harus pada kelipatan 30 menit (mis. 07:00, 07:30).';
    }
    if ($startMinutes < $openMinutes) {
        $errors[] = 'Jam mulai tidak boleh sebelum ' . RESERVATION_OPEN_TIME . '.';
    }
    if ($endMinutes > $closeMinutes) {
        $errors[] = 'Jam selesai tidak boleh setelah ' . RESERVATION_CLOSE_TIME . '.';
    }
    if ($startMinutes >= $endMinutes) {
        $errors[] = 'Jam mulai harus lebih awal dari jam selesai.';
    } elseif (($endMinutes - $startMinutes) % RESERVATION_SLOT_MINUTES !== 0) {
        $errors[] = 'Durasi reservasi harus kelipatan 30 menit.';
    }

    return $errors;
}

/**
 * Susun grid segmen 30 menit untuk ditampilkan sebagai info availability visual
 * (bukan untuk validasi — validasi overlap dilakukan langsung di query/model).
 * $approvedReservations: array of ['start_time' => 'HH:MM:SS', 'end_time' => 'HH:MM:SS']
 */
function buildAvailabilityGrid(array $approvedReservations): array
{
    $boundaries = generateTimeBoundaries();
    $segments = [];

    for ($i = 0; $i < count($boundaries) - 1; $i++) {
        $segStart = timeToMinutes($boundaries[$i]);
        $segEnd = timeToMinutes($boundaries[$i + 1]);

        $booked = false;
        foreach ($approvedReservations as $r) {
            $resStart = timeToMinutes(substr($r['start_time'], 0, 5));
            $resEnd = timeToMinutes(substr($r['end_time'], 0, 5));
            // Overlap rule kontrak: new_start < existing_end AND new_end > existing_start
            if ($segStart < $resEnd && $segEnd > $resStart) {
                $booked = true;
                break;
            }
        }

        $segments[] = [
            'start' => $boundaries[$i],
            'end' => $boundaries[$i + 1],
            'available' => !$booked,
        ];
    }

    return $segments;
}
