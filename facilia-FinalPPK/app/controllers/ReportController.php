<?php

require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../models/Facility.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/upload.php';

class ReportController
{
    /** Hanya role USER yang boleh membuat/melihat laporan miliknya sendiri. */
    private function requireUserRole(): void
    {
        if (currentRole() !== 'USER') {
            http_response_code(403);
            require __DIR__ . '/../../views/errors/403.php';
            exit;
        }
    }

    /** GET /report/create — tampilkan form laporan kerusakan. */
    public function showCreate(): void
    {
        $this->requireUserRole();

        $facilities = Facility::searchPublic(null, null, null); // ACTIVE & MAINTENANCE
        $facilities = array_values(array_filter($facilities, fn ($f) => $f['status'] === 'ACTIVE'));
        $categories = Report::CATEGORIES;
        $old = array_merge(['facility_id' => '', 'category' => '', 'description' => ''], $_SESSION['old_report'] ?? []);
        unset($_SESSION['old_report']);

        require __DIR__ . '/../../views/user/report-create.php';
    }

    /** POST /report/create — validasi, upload foto (opsional), simpan laporan BARU. */
    public function store(): void
    {
        $this->requireUserRole();

        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Permintaan tidak valid atau sesi telah berubah. Muat ulang halaman.');
            redirect('/report/create');
        }

        $userId = (int) $_SESSION['user_id'];
        $facilityId = (int) ($_POST['facility_id'] ?? 0);
        $category = cleanInput($_POST['category'] ?? '');
        $description = cleanInput($_POST['description'] ?? '');

        $_SESSION['old_report'] = ['facility_id' => $facilityId, 'category' => $category, 'description' => $description];

        $facility = $facilityId ? Facility::findById($facilityId) : null;

        $errors = [];
        if (!$facility) {
            $errors[] = 'Fasilitas wajib dipilih.';
        } elseif ($facility['status'] !== 'ACTIVE') {
            $errors[] = 'Fasilitas tidak ditemukan atau sedang tidak aktif.';
        }
        if (!isInWhitelist($category, Report::CATEGORIES)) {
            $errors[] = 'Kategori laporan tidak valid.';
        }
        if ($description === '') {
            $errors[] = 'Deskripsi kerusakan wajib diisi.';
        } elseif (strlen($description) < 5) {
            $errors[] = 'Deskripsi minimal 5 karakter.';
        } elseif (strlen($description) > 2000) {
            $errors[] = 'Deskripsi maksimal 2000 karakter.';
        }

        if ($errors) {
            setFlash('errors', implode(' ', $errors));
            redirect('/report/create');
        }

        // Foto bersifat OPSIONAL. Jika diunggah, tetap divalidasi format & ukurannya di server.
        $photoPath = null;
        if (!empty($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $photoPath = handleUpload($_FILES['photo'], __DIR__ . '/../../public/uploads/reports');
            if ($photoPath === null) {
                setFlash('error', 'Foto gagal diunggah. Pastikan format JPG/PNG/WEBP dan ukuran maksimal 2MB.');
                redirect('/report/create');
            }
            $photoPath = 'uploads/reports/' . $photoPath;
        }

        Report::create($userId, $facilityId, $category, $description, $photoPath);

        unset($_SESSION['old_report']);
        setFlash('success', 'Laporan kerusakan berhasil dikirim dan akan ditinjau petugas.');
        redirect('/report/create');
    }

    /** GET /report/my — daftar laporan milik user yang login. */
    public function myReports(): void
    {
        $this->requireUserRole();

        $userId = (int) $_SESSION['user_id'];
        $categories = Report::CATEGORIES;
        $filters = [
            'q'        => listingKeyword('q'),
            'status'   => listingChoice('status', ['BARU', 'DIPROSES', 'SELESAI', 'DITOLAK']),
            'category' => listingChoice('category', Report::CATEGORIES),
            'date'     => listingDate('date'),
            'sort'     => listingChoice('sort', ['created', 'facility', 'category', 'status'], 'created'),
            'dir'      => listingChoice('dir', ['asc', 'desc'], 'desc'),
        ];
        $hasActiveFilter = $filters['q'] !== '' || $filters['status'] !== '' || $filters['category'] !== '' || $filters['date'] !== '';
        $reports = Report::search($filters + ['user_id' => $userId]);
        require __DIR__ . '/../../views/user/reports.php';
    }

    /** GET /report/detail?id=.. — detail laporan, hanya untuk pemiliknya. */
    public function detail(): void
    {
        $this->requireUserRole();

        $id = (int) ($_GET['id'] ?? 0);
        $report = $id ? Report::findById($id) : null;

        if (!$report || (int) $report['user_id'] !== (int) $_SESSION['user_id']) {
            http_response_code(404);
            echo 'Laporan tidak ditemukan atau bukan milik Anda.';
            return;
        }

        // Detail sudah dibuka → notifikasi terkait laporan ini dianggap terbaca.
        Notification::markReadByLink((int) $_SESSION['user_id'], '/report/detail?id=' . $id);

        require __DIR__ . '/../../views/user/report-detail.php';
    }
}
