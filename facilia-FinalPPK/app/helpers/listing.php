<?php

/**
 * Helper bersama untuk halaman daftar yang punya search, filter, dan sorting
 * (riwayat reservasi user, laporan saya, antrean laporan petugas).
 */

/** Ambil tanggal Y-m-d yang valid dari query string, atau '' jika tidak valid. */
function listingDate(string $key): string
{
    $value = trim($_GET[$key] ?? '');
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return ($parsed && $parsed->format('Y-m-d') === $value) ? $value : '';
}

/** Ambil nilai query string yang harus ada di whitelist, atau $default. */
function listingChoice(string $key, array $allowed, string $default = ''): string
{
    $value = (string) ($_GET[$key] ?? '');
    return in_array($value, $allowed, true) ? $value : $default;
}

/** Kata kunci pencarian (dipotong 100 karakter). */
function listingKeyword(string $key = 'q'): string
{
    return mb_substr(trim((string) ($_GET[$key] ?? '')), 0, 100);
}

/** Escape karakter wildcard LIKE agar kata kunci dicari apa adanya. */
function likeKeyword(string $keyword): string
{
    return '%' . addcslashes($keyword, '%_\\') . '%';
}

/**
 * Bangun URL daftar dengan mempertahankan filter aktif.
 * Nilai kosong dibuang dan sort default tidak ditulis agar URL tetap bersih.
 */
function listingUrl(string $path, array $current, array $override = [], array $defaultSort = []): string
{
    $query = array_merge($current, $override);
    if ($defaultSort && ($query['sort'] ?? '') === $defaultSort[0] && ($query['dir'] ?? '') === $defaultSort[1]) {
        unset($query['sort'], $query['dir']);
    }
    $query = array_filter($query, static fn ($v) => $v !== '' && $v !== null && $v !== 0);
    return BASE_URL . $path . ($query ? '?' . http_build_query($query) : '');
}

/** Judul kolom tabel yang bisa diklik untuk sorting (klik lagi = balik arah). */
function sortHeaderLink(string $path, array $current, string $key, string $label, array $defaultSort = []): string
{
    $isCurrent = ($current['sort'] ?? '') === $key;
    $nextDir = ($isCurrent && ($current['dir'] ?? '') === 'asc') ? 'desc' : 'asc';
    $arrow = $isCurrent ? (($current['dir'] ?? '') === 'asc' ? ' ▲' : ' ▼') : ' <span class="sort-idle">↕</span>';
    $url = listingUrl($path, $current, ['sort' => $key, 'dir' => $nextDir], $defaultSort);
    return '<a class="sort-link' . ($isCurrent ? ' is-active' : '') . '" href="' . htmlspecialchars($url) . '">'
        . htmlspecialchars($label) . $arrow . '</a>';
}

/** Badge angka seragam untuk menu sidebar (merah jika > 0, abu-abu jika 0). */
function menuCount(int $count): string
{
    return '<b class="menu-count' . ($count > 0 ? ' has-items' : '') . '">' . ($count > 99 ? '99+' : $count) . '</b>';
}
