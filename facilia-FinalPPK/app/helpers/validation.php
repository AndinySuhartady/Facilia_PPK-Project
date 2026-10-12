<?php

/** Validasi format email sederhana. */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** Password minimal 8 karakter (sesuaikan kebijakan tim bila perlu). */
function isValidPassword(string $password): bool
{
    return strlen($password) >= 8;
}

/** Cek nilai ada di whitelist (dipakai untuk role, status, dsb). */
function isInWhitelist(string $value, array $whitelist): bool
{
    return in_array($value, $whitelist, true);
}

/** Validasi capacity: harus integer dan > 0. */
function isValidCapacity($value): bool
{
    return filter_var($value, FILTER_VALIDATE_INT) !== false && (int) $value > 0;
}

/** Bersihkan string input dasar (trim + strip tags). */
function cleanInput(string $value): string
{
    return trim(strip_tags($value));
}

/**
 * Alasan keputusan harus berupa kalimat yang masuk akal, bukan sekadar angka,
 * simbol, atau input sangat pendek. Validasi ini tetap ramah untuk bahasa
 * Indonesia dan singkatan seperti "AC"/"LCD".
 */
function isMeaningfulReason(string $value, int $minimumLength = 10): bool
{
    $value = cleanInput($value);
    if (strlen($value) < $minimumLength) {
        return false;
    }

    preg_match_all('/[A-Za-z]/', $value, $letters);
    return count($letters[0]) >= 5;
}

/** Panjang dalam karakter (mbstring bila ada, jika tidak jatuh ke strlen). */
function textLength(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

/** Cek panjang teks tidak melebihi batas kolom database. */
function isWithinLength(string $value, int $max): bool
{
    return textLength($value) <= $max;
}

/** Kapasitas harus bulat > 0 dan masuk akal (kolom INT MySQL). */
function isValidCapacityRange($value, int $max = 100000): bool
{
    return isValidCapacity($value) && (int) $value <= $max;
}

/** Validasi tanggal Y-m-d yang benar-benar ada di kalender. */
function isValidDateString(string $value): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $value);
    return $d !== false && $d->format('Y-m-d') === $value;
}

/** Password baru: minimal 8 karakter dan maksimal 72 byte (batas bcrypt). */
function isValidNewPassword(string $password): bool
{
    return strlen($password) >= 8 && strlen($password) <= 72;
}
