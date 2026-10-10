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
