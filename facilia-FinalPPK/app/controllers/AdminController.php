<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Facility.php';
require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../helpers/validation.php';
require_once __DIR__ . '/../helpers/export.php';

class AdminController
{
    private function requireValidCsrf(string $redirectTo): void
    {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Permintaan tidak valid atau sesi telah berubah.');
            redirect($redirectTo);
        }
    }
    /** GET /admin/dashboard — ringkasan singkat. */
    public function dashboard(): void
    {
        $stats = [
            'total_users' => User::countAll(),
            'pending_users' => User::countAll('PENDING'),
            'total_petugas' => User::countByRole('PETUGAS'),
            'total_facilities' => count(Facility::all()),
        ];
        require __DIR__ . '/../../views/admin/dashboard.php';
    }

    // ---------------------------------------------------------------
    // USER & FACILITY MANAGEMENT
    // ---------------------------------------------------------------

    /** GET /admin/users — daftar user + filter verification_status + search nama/email. */
    public function users(): void
    {
        $filter = $_GET['status'] ?? null;
        if ($filter && !isInWhitelist($filter, ['PENDING', 'VERIFIED', 'REJECTED'])) {
            $filter = null;
        }
        $keyword = trim($_GET['q'] ?? '');
        $users = User::all($filter, $keyword ?: null);
        require __DIR__ . '/../../views/admin/users.php';
    }

    /**
     * POST /admin/users/delete — hapus permanen user berstatus VERIFIED/REJECTED.
     * PENDING tidak dihapus dari sini (alurnya lewat approve/reject dulu).
     * Tidak bisa hapus akun sendiri, dan tidak bisa hapus user yang masih
     * punya riwayat reservasi/laporan (data historis tidak boleh hilang).
     */
    public function deleteUser(): void
    {
        $this->requireValidCsrf('/admin/users');
        $id = (int) ($_POST['id'] ?? 0);
        $target = $id ? User::findById($id) : null;

        if (!$target) {
            setFlash('error', 'User tidak ditemukan.');
            redirect('/admin/users');
        }

        if ($id === (int) $_SESSION['user_id']) {
            setFlash('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
            redirect('/admin/users');
        }

        if (!isInWhitelist($target['verification_status'], ['VERIFIED', 'REJECTED'])) {
            setFlash('error', 'Hanya user berstatus VERIFIED atau REJECTED yang bisa dihapus.');
            redirect('/admin/users');
        }

        if (User::isReferenced($id)) {
            setFlash('error', 'User ini masih memiliki riwayat reservasi/laporan dan tidak bisa dihapus.');
            redirect('/admin/users');
        }

        User::delete($id);
        setFlash('success', 'User berhasil dihapus.');
        redirect('/admin/users');
    }

    /** POST /admin/users/verify — approve/reject registrasi PENDING. */
    public function verifyUser(): void
    {
        $this->requireValidCsrf('/admin/users');
        $id = (int) ($_POST['id'] ?? 0);
        $decision = $_POST['decision'] ?? '';

        if (!$id || !isInWhitelist($decision, ['VERIFIED', 'REJECTED'])) {
            setFlash('error', 'Permintaan tidak valid.');
            redirect('/admin/users');
        }

        $target = User::findById($id);
        if (!$target || $target['verification_status'] !== 'PENDING') {
            setFlash('error', 'User tidak ditemukan atau bukan status PENDING.');
            redirect('/admin/users');
        }

        User::updateVerificationStatus($id, $decision);
        setFlash('success', 'Status user berhasil diperbarui menjadi ' . $decision . '.');
        redirect('/admin/users');
    }

    /** POST /admin/users/create — admin membuat akun langsung (user/petugas/admin). */
    public function createUser(): void
    {
        $this->requireValidCsrf('/admin/users');
        $name = cleanInput($_POST['name'] ?? '');
        $email = cleanInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';

        $errors = [];
        if ($name === '') $errors[] = 'Nama wajib diisi.';
        elseif (!isWithinLength($name, 100)) $errors[] = 'Nama maksimal 100 karakter.';
        if (!isValidEmail($email) || !isWithinLength($email, 150)) $errors[] = 'Email tidak valid (maksimal 150 karakter).';
        if (!isValidNewPassword($password)) $errors[] = 'Password harus 8–72 karakter.';
        if (!isInWhitelist($role, ['USER', 'PETUGAS', 'ADMIN'])) $errors[] = 'Role tidak valid.';
        if ($email !== '' && User::findByEmail($email)) $errors[] = 'Email sudah terdaftar.';

        if ($errors) {
            setFlash('errors', implode(' ', $errors));
            redirect('/admin/users');
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        // Akun yang dibuat admin langsung VERIFIED (tidak perlu approval sendiri).
        try {
            User::createByAdmin($name, $email, $hashed, $role, 'VERIFIED');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                setFlash('errors', 'Email sudah terdaftar.');
                redirect('/admin/users');
            }
            throw $e;
        }

        setFlash('success', 'Akun ' . $role . ' berhasil dibuat.');
        redirect('/admin/users');
    }

    /**
     * POST /admin/users/status — nonaktifkan (VERIFIED→REJECTED) atau aktifkan kembali
     * (REJECTED→VERIFIED). Tanpa perubahan skema: REJECTED sudah ditolak saat login.
     */
    public function setUserStatus(): void
    {
        $this->requireValidCsrf('/admin/users');
        $id = (int) ($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $target = $id ? User::findById($id) : null;

        if (!$target || !isInWhitelist($action, ['activate', 'deactivate'])) {
            setFlash('error', 'Permintaan tidak valid.');
            redirect('/admin/users');
        }
        if ($id === (int) $_SESSION['user_id']) {
            setFlash('error', 'Anda tidak bisa mengubah status akun Anda sendiri.');
            redirect('/admin/users');
        }
        if ($action === 'deactivate' && $target['verification_status'] === 'VERIFIED') {
            User::updateVerificationStatus($id, 'REJECTED');
            setFlash('success', 'Akun dinonaktifkan. User tidak dapat login lagi.');
        } elseif ($action === 'activate' && $target['verification_status'] === 'REJECTED') {
            User::updateVerificationStatus($id, 'VERIFIED');
            setFlash('success', 'Akun diaktifkan kembali.');
        } else {
            setFlash('error', 'Status akun saat ini tidak sesuai dengan aksi yang diminta.');
        }
        redirect('/admin/users');
    }

    /** POST /admin/users/role — ubah role user lain (tidak boleh akun sendiri). */
    public function changeUserRole(): void
    {
        $this->requireValidCsrf('/admin/users');
        $id = (int) ($_POST['id'] ?? 0);
        $role = $_POST['role'] ?? '';
        $target = $id ? User::findById($id) : null;

        if (!$target || !isInWhitelist($role, ['USER', 'PETUGAS', 'ADMIN'])) {
            setFlash('error', 'Permintaan tidak valid.');
            redirect('/admin/users');
        }
        if ($id === (int) $_SESSION['user_id']) {
            setFlash('error', 'Anda tidak bisa mengubah role akun Anda sendiri.');
            redirect('/admin/users');
        }
        User::updateRole($id, $role);
        setFlash('success', 'Role ' . $target['name'] . ' diubah menjadi ' . $role . '.');
        redirect('/admin/users');
    }

    /** POST /admin/users/reset-password — admin menetapkan password baru untuk user lain. */
    public function resetUserPassword(): void
    {
        $this->requireValidCsrf('/admin/users');
        $id = (int) ($_POST['id'] ?? 0);
        $password = $_POST['new_password'] ?? '';
        $target = $id ? User::findById($id) : null;

        if (!$target) {
            setFlash('error', 'User tidak ditemukan.');
            redirect('/admin/users');
        }
        if ($id === (int) $_SESSION['user_id']) {
            setFlash('error', 'Gunakan halaman Ubah Password untuk akun Anda sendiri.');
            redirect('/admin/users');
        }
        if (!isValidNewPassword($password)) {
            setFlash('error', 'Password baru harus 8–72 karakter.');
            redirect('/admin/users');
        }
        User::updatePassword($id, password_hash($password, PASSWORD_DEFAULT));
        setFlash('success', 'Password ' . $target['name'] . ' berhasil direset.');
        redirect('/admin/users');
    }

    /** GET /admin/facilities — daftar fasilitas. */
    public function facilities(): void
    {
        $facilities = Facility::all();
        require __DIR__ . '/../../views/admin/facilities.php';
    }

    /** GET /admin/facilities/create & /admin/facilities/edit — form CRUD. */
    public function facilityForm(): void
    {
        $facility = null;
        if (!empty($_GET['id'])) {
            $facility = Facility::findById((int) $_GET['id']);
            if (!$facility) {
                http_response_code(404);
                echo 'Fasilitas tidak ditemukan.';
                return;
            }
        }
        require __DIR__ . '/../../views/admin/facility-form.php';
    }

    /** POST /admin/facilities/save — create atau update tergantung ada id atau tidak. */
    public function saveFacility(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $this->requireValidCsrf($id ? '/admin/facilities/edit?id=' . $id : '/admin/facilities/create');
        $name = cleanInput($_POST['name'] ?? '');
        $type = cleanInput($_POST['type'] ?? '');
        $location = cleanInput($_POST['location'] ?? '');
        $capacity = $_POST['capacity'] ?? '';
        $status = $_POST['status'] ?? '';
        $description = cleanInput($_POST['description'] ?? '');

        $errors = [];
        if ($name === '') $errors[] = 'Nama fasilitas wajib diisi.';
        elseif (!isWithinLength($name, 150)) $errors[] = 'Nama fasilitas maksimal 150 karakter.';
        if ($type === '') $errors[] = 'Tipe fasilitas wajib diisi.';
        elseif (!isWithinLength($type, 50)) $errors[] = 'Tipe fasilitas maksimal 50 karakter.';
        if ($location === '') $errors[] = 'Lokasi wajib diisi.';
        elseif (!isWithinLength($location, 150)) $errors[] = 'Lokasi maksimal 150 karakter.';
        if (!isValidCapacityRange($capacity)) $errors[] = 'Kapasitas harus angka bulat antara 1 dan 100.000.';
        if (!isWithinLength($description, 5000)) $errors[] = 'Deskripsi maksimal 5.000 karakter.';
        if (!isInWhitelist($status, Facility::STATUSES)) $errors[] = 'Status tidak valid.';

        if ($errors) {
            setFlash('errors', implode(' ', $errors));
            redirect($id ? '/admin/facilities/edit?id=' . $id : '/admin/facilities/create');
        }

        if ($id) {
            $previous = Facility::findById($id);
            if (!$previous) {
                setFlash('error', 'Fasilitas tidak ditemukan.');
                redirect('/admin/facilities');
            }
            $db = getDB();
            try {
                $db->beginTransaction();
                Facility::update($id, $name, $type, $location, (int) $capacity, $status, $description);
                $cancelled = 0;
                if ($previous && $previous['status'] === 'ACTIVE' && in_array($status, ['MAINTENANCE', 'INACTIVE'], true)) {
                    $cancelled = Reservation::cancelApprovedForMaintenance(
                        $id,
                        $status === 'MAINTENANCE'
                            ? 'Dibatalkan karena fasilitas sedang dalam perbaikan.'
                            : 'Dibatalkan karena fasilitas dinonaktifkan oleh admin.'
                    );
                }
                $db->commit();
                setFlash('success', 'Fasilitas berhasil diperbarui. ' . ($cancelled ? $cancelled . ' reservasi mendatang dibatalkan.' : ''));
            } catch (Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
                setFlash('error', 'Fasilitas gagal diperbarui. Silakan coba lagi.');
            }
        } else {
            Facility::create($name, $type, $location, (int) $capacity, $status, $description);
            setFlash('success', 'Fasilitas berhasil ditambahkan.');
        }

        redirect('/admin/facilities');
    }

    /**
     * POST /admin/facilities/deactivate — "hapus" versi aman.
     * Tidak pernah DELETE baris; hanya set status INACTIVE.
     */
    public function deactivateFacility(): void
    {
        $this->requireValidCsrf('/admin/facilities');
        $id = (int) ($_POST['id'] ?? 0);
        $facility = $id ? Facility::findById($id) : null;

        if (!$facility) {
            setFlash('error', 'Fasilitas tidak ditemukan.');
            redirect('/admin/facilities');
        }

        $db = getDB();
        try {
            $db->beginTransaction();
            Facility::deactivate($id);
            $cancelled = Reservation::cancelApprovedForMaintenance(
                $id,
                'Dibatalkan karena fasilitas dinonaktifkan oleh admin.'
            );
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            setFlash('error', 'Fasilitas gagal dinonaktifkan. Silakan coba lagi.');
            redirect('/admin/facilities');
        }

        $note = Facility::isReferenced($id)
            ? ' (fasilitas ini memiliki riwayat reservasi/laporan, data historis tetap tersimpan)'
            : '';
        setFlash('success', 'Fasilitas dinonaktifkan. ' . $cancelled . ' reservasi mendatang dibatalkan.' . $note);
        redirect('/admin/facilities');
    }

    // ---------------------------------------------------------------
    // ANALYTICS & EXPORT
    // ---------------------------------------------------------------

    /** GET /admin/analytics — dashboard analytics dengan filter periode. */
    public function analytics(): void
    {
        try {
            [$dateFrom, $dateTo] = Reservation::analyticsPeriod($_GET['from'] ?? null, $_GET['to'] ?? null);
        } catch (InvalidArgumentException $e) {
            setFlash('error', $e->getMessage());
            redirect('/admin/analytics');
        }

        $occupancy = Reservation::occupancyByFacility($dateFrom, $dateTo);
        $damageFrequency = Report::frequencyByFacilityAndCategory($dateFrom, $dateTo);

        require __DIR__ . '/../../views/admin/analytics.php';
    }

    private function exportAnalytics(string $name, array $rows): void
    {
        $filename = $name . '_' . date('Ymd_His');
        if (($_GET['format'] ?? 'csv') === 'xlsx') exportXlsx($filename . '.xlsx', $rows);
        exportCsv($filename . '.csv', $rows);
    }

    /** GET /admin/analytics/export — export CSV occupancy (baseline termudah). */
    public function exportOccupancy(): void
    {
        try {
            [$dateFrom, $dateTo] = Reservation::analyticsPeriod($_GET['from'] ?? null, $_GET['to'] ?? null);
        } catch (InvalidArgumentException $e) {
            setFlash('error', $e->getMessage());
            redirect('/admin/analytics');
        }
        $rows = Reservation::occupancyByFacility($dateFrom, $dateTo);
        $this->exportAnalytics('okupansi', $rows);
    }

    /** GET /admin/analytics/export-damage — export CSV frekuensi kerusakan. */
    public function exportDamage(): void
    {
        try {
            [$dateFrom, $dateTo] = Reservation::analyticsPeriod($_GET['from'] ?? null, $_GET['to'] ?? null);
        } catch (InvalidArgumentException $e) {
            setFlash('error', $e->getMessage());
            redirect('/admin/analytics');
        }
        $rows = Report::frequencyByFacilityAndCategory($dateFrom, $dateTo);
        $this->exportAnalytics('kerusakan', $rows);
    }
}
