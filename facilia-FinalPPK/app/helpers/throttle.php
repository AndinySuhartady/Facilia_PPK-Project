<?php

/**
 * Pembatas percobaan login berbasis file sementara (tanpa perubahan skema DB).
 * Kunci = hash(email + IP), sehingga tidak bisa dilewati dengan membuang cookie session.
 */
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_WINDOW_SECONDS = 300;

function loginThrottleFile(string $email): string
{
    $key = hash('sha256', strtolower($email) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'));
    return rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'facilia_login_' . $key . '.json';
}

function loginThrottleLoad(string $email): array
{
    $file = loginThrottleFile($email);
    $data = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
    if (!is_array($data) || (time() - (int) ($data['first'] ?? 0)) > LOGIN_WINDOW_SECONDS) {
        return ['count' => 0, 'first' => time()];
    }
    return $data;
}

/** Detik tersisa sebelum boleh mencoba lagi, atau 0 bila belum terkunci. */
function loginLockedSeconds(string $email): int
{
    $data = loginThrottleLoad($email);
    if ($data['count'] < LOGIN_MAX_ATTEMPTS) {
        return 0;
    }
    return max(1, LOGIN_WINDOW_SECONDS - (time() - (int) $data['first']));
}

function loginThrottleFail(string $email): void
{
    $data = loginThrottleLoad($email);
    $data['count']++;
    @file_put_contents(loginThrottleFile($email), json_encode($data), LOCK_EX);
}

function loginThrottleClear(string $email): void
{
    @unlink(loginThrottleFile($email));
}
