# Pembaruan terakhir
- Persetujuan memeriksa status ACTIVE di bawah kunci transaksi dan tenggat tanggal.
- Slot maintenance ditandai tidak tersedia.
- Foto laporan wajib di browser dan server.
- Auto-cancel CLI dan pemasang jadwal Windows tersedia.

## Pasang auto-cancel tanpa kunjungan
Setelah menyalin proyek ke htdocs, buka PowerShell di folder proyek, lalu jalankan:
```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\pasang-jadwal.ps1
```
Jadwal dibuat pada 00.00 waktu Windows. Atur zona waktu Windows ke WIB; PHP memakai Asia/Jakarta. Komputer dan MySQL harus aktif. Jadwal ini menggunakan akun Windows yang sedang masuk; komputer mati/logout tidak menjamin eksekusi tepat tengah malam. StartWhenAvailable dan pemeriksaan setiap request tetap menangani eksekusi yang terlewat. Script belum memasang jadwal di komputer kamu sampai perintah tersebut dijalankan.
Uji manual: `C:\xampp\php\php.exe scripts\auto-cancel.php`.
Kelengkapan laporan/repository: lihat PENGUMPULAN.md.

## Perbaikan US3, US4, US5, US17 — 10 Oktober 2026

- US3: tujuan penggunaan wajib diisi, maksimal 255 karakter/byte pada validasi server.
- US4: pemilik hanya dapat membatalkan PENDING/APPROVED sebelum tanggal dan jam mulai (WIB). Batas diperiksa di controller, tampilan, dan query UPDATE atomik. Contoh: booking 12 Oktober 07:00 tidak dapat dibatalkan pada 12 Oktober 07:00 atau sesudahnya.
- US5: detail milik sendiri tersedia di `/reservation/detail?id=...`, mencakup fasilitas/lokasi, pemohon, tujuan, waktu, status, alasan dan waktu pembatalan. Akun lain menerima 404.
- US17: rekap berdasarkan periode inklusif; jam terpakai dari durasi APPROVED. Persentase = jam terpakai / (jumlah hari x 13 jam) x 100. Default periode adalah bulan berjalan. Penyebut belum mengurangi maintenance/nonaktif karena schema tidak menyimpan riwayat status fasilitas.
- Rekap dan ekspor kerusakan menyertakan lokasi; tersedia XLSX asli dan CSV UTF-8 (BOM, sep=;, pemisah titik koma). Tidak membutuhkan Composer atau ekstensi ZipArchive.
- Pengajuan/pembatalan pengguna menggunakan modal dua tahap di tengah layar.
- Tidak ada perubahan struktur database untuk empat perbaikan ini. Gunakan database yang sama; jika database versi lama belum mempunyai cancelled_at, jalankan migrasi yang telah disediakan sebelumnya.
- Folder paket ini bernama `facilia-penyempurnaan34517`, URL `/facilia-penyempurnaan34517/public/login`.

# FACILIA — Sistem Reservasi & Pelaporan Fasilitas Kampus

## Revisi hasil evaluasi 25 September 2026

- Pengajuan, persetujuan, penolakan, dan pembatalan reservasi memakai konfirmasi ganda.
- Pembatalan reservasi memakai modal dua tahap di tengah layar yang konsisten dengan UI FACILIA.
- Penanda konfirmasi turut diverifikasi di server; request tanpa konfirmasi ditolak.
- Reservasi hanya dapat dibuat minimal H-1.
- Reservasi `PENDING` otomatis menjadi `CANCELLED` ketika tanggal reservasi tiba, dengan alasan otomatis tersimpan di riwayat.
- Perhitungan tanggal aplikasi menggunakan zona waktu `Asia/Jakarta`.

Aplikasi lengkap **Modul 1 (Auth & Admin) + Modul 2 (Publik & Reservasi) + Modul 3 (Report & Petugas)**,
digabung jadi satu codebase PHP-MVC ringan (bukan Laravel penuh) dengan satu router (`routes/web.php`)
dan satu design system bersama (`public/assets/css/style.css`) untuk seluruh halaman.

Modul 3 diintegrasikan dari implementasi tim yang sudah jadi (`Modul_3.zip`), disesuaikan ke struktur/konvensi
Modul 1 & 2 (session key, model `getDB()`, helper `redirect()`/`setFlash()`) dan UI-nya dijadikan acuan
untuk restyle Modul 1 & 2 — jadi seluruh aplikasi sekarang tampil dengan satu tampilan yang konsisten.

Seluruh alur di bawah **sudah diuji langsung** (bukan cuma dibaca kodenya) dengan MySQL + PHP built-in
server di lingkungan sandbox: register→verifikasi, login 3 role, browse & search fasilitas, submit
reservasi rentang jam bebas, validasi overlap, approve/reject petugas, buat laporan, proses laporan
sampai fasilitas otomatis ACTIVE⇄MAINTENANCE. Satu bug nyata ditemukan dan diperbaiki dalam proses ini
(lihat "Bug yang ditemukan & diperbaiki" di bawah).

## Struktur & alur lengkap

**Auth & Role Security (Modul 1)**
- Register mandiri → PENDING → perlu approve admin sebelum bisa login
- Password `password_hash()`/`password_verify()`, session `user_id`/`role`/`name`
- Middleware `AuthMiddleware`, `AdminMiddleware`, `PetugasMiddleware` (cek server-side)

**Admin (Modul 1)**
- Kelola user (approve/reject/buat akun langsung), CRUD fasilitas (nonaktifkan = `INACTIVE`, tidak pernah `DELETE`)
- Analytics occupancy (reservasi APPROVED) + frekuensi kerusakan per fasilitas/kategori, export CSV

**Publik & Reservasi (Modul 2)**
- Browse fasilitas ACTIVE/MAINTENANCE dengan search & filter
- Detail fasilitas + grid availability visual per 30 menit
- **Reservasi rentang jam bebas**: user memilih jam mulai & jam selesai sendiri (kelipatan 30 menit,
  07:00–20:00), bukan centang slot satu-satu — satu reservasi bisa langsung mencakup beberapa jam
  (`app/helpers/slots.php` + `Reservation::hasApprovedOverlap()`)
- Riwayat reservasi + cancel oleh pemilik

**Report & Petugas (Modul 3)**
- User (role USER) membuat laporan kerusakan: pilih fasilitas ACTIVE, kategori (whitelist), deskripsi,
  foto opsional (JPG/PNG/WEBP, maks 2MB, nama file di-randomize)
- "Laporan Saya" — riwayat & detail, dibatasi hanya milik sendiri
- Panel Petugas (khusus role PETUGAS — ADMIN dan USER mendapat 403 sesuai aturan akses terpisah):
  - **Antrean reservasi**: approve (dengan re-check overlap terhadap APPROVED lain tepat sebelum commit,
    sehingga dua reservasi identik yang diproses hampir bersamaan tidak bisa dua-duanya disetujui) /
    reject (wajib isi alasan) / emergency-cancel reservasi APPROVED (mis. fasilitas mendadak rusak)
  - **Antrean laporan**: BARU → DIPROSES → SELESAI/DITOLAK (`Report::ALLOWED_TRANSITIONS` sebagai
    satu-satunya sumber kebenaran transisi yang sah), dengan catatan wajib untuk status akhir
  - Toggle status fasilitas ACTIVE ⇄ MAINTENANCE dari halaman detail laporan, atomik terhadap status
    fasilitas saat itu (`Facility::updateOperationalStatus()`) — fasilitas MAINTENANCE otomatis tidak
    bisa direservasi (dicek ulang di `ReservationController::store()`)
  - Semua aksi kritikal (approve/reject/cancel/ubah status) dilindungi CSRF token

## Update setelah laporan bug & permintaan fitur dari lapangan

**Bug fatal error saat cancel reservasi (SUDAH DIPERBAIKI)**
Kalau database Anda sudah terlanjur dibuat dari `schema.sql` versi sebelum `cancelled_at`
ditambahkan, jalankan `database/migration_01_add_cancelled_at.sql` SEKALI di phpMyAdmin
(tab SQL). Sudah diuji ulang — emergency-cancel oleh petugas berjalan normal setelah migrasi.

**Fitur baru — Admin**
- Hapus user permanen (`/admin/users/delete`), khusus status VERIFIED/REJECTED. Diblokir otomatis
  jika: user masih PENDING (harus di-approve/reject dulu), mencoba hapus akun sendiri, atau user
  masih punya riwayat reservasi/laporan (data historis tidak boleh hilang)
- Search user by nama/email (`?q=...`) di halaman `/admin/users`, bisa dikombinasi dengan filter status

**Fitur baru — Publik**
- Fasilitas berstatus INACTIVE sekarang tetap muncul di `/facilities` (dengan label "nonaktif"),
  tapi tombol detail disembunyikan dan akses langsung ke `/facility/detail?id=..` diblokir
  (`views/public/facility-inactive.php`) — sebelumnya fasilitas INACTIVE hilang total dari listing

## Bug yang ditemukan & diperbaiki saat integrasi
- `mb_strlen()` dipakai di validasi `ReportController`/`PetugasController` menyebabkan **Fatal Error**
  di lingkungan tanpa extension `mbstring` aktif. Diganti ke `strlen()` (tidak butuh extension tambahan,
  cukup untuk sekadar batas panjang teks). Sudah diuji ulang dan submit laporan berjalan normal.
- Versi asli `PetugasMiddleware` (dari `Modul_3.zip`) memakai session key `user_role`, sedangkan
  `ReportController`-nya memakai `role` — tidak konsisten satu sama lain. Disatukan ke `role` mengikuti
  konvensi `AuthController` Modul 1 di seluruh codebase gabungan ini.

## Setup lokal (XAMPP)

0. Butuh **PHP 8.1+** (memakai `match`, `str_starts_with`, dan parameter `eol` pada `fputcsv`).
1. Salin folder proyek (mis. `facilia-FinalPPK/`) ke `htdocs/`.
2. Buka phpMyAdmin, jalankan `database/schema.sql` lalu `database/seed.sql`.
2b. Jika memakai database lama, jalankan juga `database/migration_02_notifications_and_lengths.sql` (tabel notifikasi).
3. Cek kredensial di `config/database.php` (default: `root` tanpa password, nama DB `facilia`).
4. Sesuaikan `BASE_URL` di `config/app.php` dengan path akses lokal Anda, mis. `/facilia/public`.
5. Pastikan `mod_rewrite` Apache aktif (dipakai `.htaccess` di folder `public/`).
6. Pastikan extension `pdo_mysql` dan `fileinfo` aktif di `php.ini` (default aktif di XAMPP).
7. Akses `http://localhost/<nama-folder-proyek>/public/login`.

**Akun demo** (password semua: `password123`):
| Role    | Email                  |
|---------|------------------------|
| ADMIN   | admin@facilia.test     |
| PETUGAS | petugas@facilia.test   |
| USER (verified) | user1@facilia.test |
| USER (pending)  | user2@facilia.test |

## Kontrak penting (jangan diubah sepihak tanpa koordinasi tim)
- Session key: `user_id`, `role`, `name` — dipakai di seluruh middleware & controller
- Nama field/enum status: lihat `database/schema.sql`
- Transisi status laporan: `Report::ALLOWED_TRANSITIONS` di `app/models/Report.php`
- Transisi status fasilitas: `Facility::updateOperationalStatus()` di `app/models/Facility.php`
- Design system: `public/assets/css/style.css` — semua view (Modul 1/2/3) memakai file ini,
  jangan menyisipkan `<style>` inline baru di view agar tampilan tetap konsisten