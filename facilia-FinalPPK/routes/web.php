<?php

/**
 * Router sederhana tanpa dependency eksternal.
 * Cocok untuk struktur "MVC ringan" sesuai kontrak dokumen (bukan framework Laravel penuh).
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/middleware/AdminMiddleware.php';
require_once __DIR__ . '/../app/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/FacilityController.php';
require_once __DIR__ . '/../app/controllers/ReservationController.php';
require_once __DIR__ . '/../app/controllers/ReportController.php';
require_once __DIR__ . '/../app/controllers/PetugasController.php';
require_once __DIR__ . '/../app/middleware/PetugasMiddleware.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim(str_replace(BASE_URL, '', $uri), '/');
if ($uri === '') {
    $uri = '/';
}
$method = $_SERVER['REQUEST_METHOD'];

$auth = new AuthController();
$admin = new AdminController();
$facilityCtrl = new FacilityController();
$reservationCtrl = new ReservationController();
$reportCtrl = new ReportController();
$petugasCtrl = new PetugasController();

// Menjalankan aturan tenggat pada setiap request aplikasi, sehingga request
// PENDING yang sudah mencapai hari reservasi tidak menunggu halaman tertentu
// dibuka dahulu untuk berubah menjadi CANCELLED.
Reservation::autoCancelExpiredPending();

// -------------------- PUBLIC / AUTH --------------------
if ($uri === '/' && $method === 'GET') {
    redirect('/login');
}
if ($uri === '/register' && $method === 'GET') {
    $auth->showRegister();
    exit;
}
if ($uri === '/register' && $method === 'POST') {
    $auth->register();
    exit;
}
if ($uri === '/login' && $method === 'GET') {
    $auth->showLogin();
    exit;
}
if ($uri === '/login' && $method === 'POST') {
    $auth->login();
    exit;
}
if ($uri === '/logout' && $method === 'POST') {
    AuthMiddleware::handle();
    $auth->logout();
    exit;
}

// -------------------- ADMIN (dilindungi AdminMiddleware) --------------------
if (str_starts_with($uri, '/admin')) {
    AdminMiddleware::handle();

    match (true) {
        $uri === '/admin/dashboard' && $method === 'GET' => $admin->dashboard(),
        $uri === '/admin/users' && $method === 'GET' => $admin->users(),
        $uri === '/admin/users/verify' && $method === 'POST' => $admin->verifyUser(),
        $uri === '/admin/users/create' && $method === 'POST' => $admin->createUser(),
        $uri === '/admin/users/delete' && $method === 'POST' => $admin->deleteUser(),
        $uri === '/admin/facilities' && $method === 'GET' => $admin->facilities(),
        $uri === '/admin/facilities/create' && $method === 'GET' => $admin->facilityForm(),
        $uri === '/admin/facilities/edit' && $method === 'GET' => $admin->facilityForm(),
        $uri === '/admin/facilities/save' && $method === 'POST' => $admin->saveFacility(),
        $uri === '/admin/facilities/deactivate' && $method === 'POST' => $admin->deactivateFacility(),
        $uri === '/admin/analytics' && $method === 'GET' => $admin->analytics(),
        $uri === '/admin/analytics/export' && $method === 'GET' => $admin->exportOccupancy(),
        $uri === '/admin/analytics/export-damage' && $method === 'GET' => $admin->exportDamage(),
        default => http_response_code(404),
    };
    exit;
}

// -------------------- MODUL 2: PUBLIK & RESERVASI --------------------
if ($uri === '/facilities' && $method === 'GET') {
    $facilityCtrl->index();
    exit;
}
if ($uri === '/facility/detail' && $method === 'GET') {
    $facilityCtrl->show();
    exit;
}

// Submit, history, dan cancel reservasi butuh session aktif (siapa pun role-nya).
if (str_starts_with($uri, '/reservation')) {
    AuthMiddleware::handle();

    match (true) {
        $uri === '/reservation/create' && $method === 'POST' => $reservationCtrl->store(),
        $uri === '/reservation/history' && $method === 'GET' => $reservationCtrl->history(),
        $uri === '/reservation/detail' && $method === 'GET' => $reservationCtrl->detail(),
        $uri === '/reservation/cancel' && $method === 'POST' => $reservationCtrl->cancel(),
        default => http_response_code(404),
    };
    exit;
}

// -------------------- MODUL 3: LAPORAN (sisi USER) --------------------
if (str_starts_with($uri, '/report')) {
    AuthMiddleware::handle();

    match (true) {
        $uri === '/report/create' && $method === 'GET' => $reportCtrl->showCreate(),
        $uri === '/report/create' && $method === 'POST' => $reportCtrl->store(),
        $uri === '/report/my' && $method === 'GET' => $reportCtrl->myReports(),
        $uri === '/report/detail' && $method === 'GET' => $reportCtrl->detail(),
        default => http_response_code(404),
    };
    exit;
}

// -------------------- MODUL 3: PANEL PETUGAS --------------------
if (str_starts_with($uri, '/petugas')) {
    PetugasMiddleware::handle();

    match (true) {
        $uri === '/petugas/dashboard' && $method === 'GET' => $petugasCtrl->dashboard(),
        $uri === '/petugas/reservations' && $method === 'GET' => $petugasCtrl->reservationQueue(),
        $uri === '/petugas/reservations/detail' && in_array($method, ['GET', 'POST'], true) => $petugasCtrl->reservationDetail(),
        $uri === '/petugas/reports' && $method === 'GET' => $petugasCtrl->reportQueue(),
        $uri === '/petugas/reports/detail' && in_array($method, ['GET', 'POST'], true) => $petugasCtrl->reportDetail(),
        default => http_response_code(404),
    };
    exit;
}

http_response_code(404);
echo '404 — Halaman tidak ditemukan.';
