<?php

/**
 * Helper upload file umum. Dipakai penuh oleh Modul 3 (foto laporan kerusakan).
 * Disediakan di sini agar kontrak folder app/helpers/ konsisten sejak awal.
 */

const ALLOWED_UPLOAD_MIME = ['image/jpeg', 'image/png', 'image/webp'];
const MAX_UPLOAD_SIZE_BYTES = 2 * 1024 * 1024; // 2MB

/**
 * Validasi dan pindahkan file upload ke folder tujuan.
 * Mengembalikan path relatif tersimpan, atau null jika gagal/tidak ada file.
 */
function handleUpload(array $file, string $destinationDir): ?string
{
    if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_UPLOAD_MIME, true)) {
        return null;
    }

    if ($file['size'] > MAX_UPLOAD_SIZE_BYTES) {
        return null;
    }

    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'bin',
    };

    $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
    $destinationPath = rtrim($destinationDir, '/') . '/' . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
        return null;
    }

    return $safeName;
}
