# Patch FACILIA — 12 Oktober 2026

Diuji dengan PHP 8.3 + MariaDB 10.11 (skenario HTTP end-to-end, 59 pengecekan lolos).
Database lama: jalankan `database/migration_02_notifications_and_lengths.sql` sekali.

## Bug yang diperbaiki
| # | Masalah | Perbaikan |
|---|---------|-----------|
| 1 | Alasan penolakan/pembatalan 256–1000 karakter → error 500 (kolom `VARCHAR(255)`) | Batas server & form jadi 255 (`PetugasController`, view petugas, `ReservationController`) |
| 2 | `?q[]=x`, `?date[]=x`, `?status[]=x` → `TypeError` fatal | Router menolak parameter array dengan HTTP 400 |
| 3 | `?date=zzz` lolos & semua slot hijau; tanggal tanpa batas atas | `isValidDateString()`; default H+1; maksimum 90 hari (juga di server `validateReservationTime`) |
| 4 | Form laporan kehilangan pilihan fasilitas + warning `Undefined array key` | Kunci `facility_id` konsisten |
| 5 | `NOW()`/`CURDATE()` MySQL beda zona dengan PHP (WIB) | `SET time_zone='+07:00'` pada koneksi PDO |
| 6 | Input terlalu panjang (nama/tipe/lokasi fasilitas, nama/email user, kapasitas raksasa) → 500 | Validasi panjang & rentang; race email unik ditangkap |
| 7 | Cancel milik orang lain: status 403 + redirect → halaman kosong | 403 dihapus, pesan flash tampil |

## Keamanan
- `session_regenerate_id(true)` saat login dan saat ganti password.
- Pembatas login: 5 gagal / 5 menit per (email+IP), berbasis file sementara (tidak bisa dilewati dengan membuang cookie).
- `public/uploads/reports/.htaccess` melarang eksekusi skrip.
- File `.lnk` pribadi dan foto upload uji dihapus; `.gitignore` diperbarui.

## Fitur baru
- **Notifikasi dalam aplikasi** (tabel `notifications`) untuk USER: reservasi disetujui/ditolak/dibatalkan petugas/dibatalkan maintenance/auto-cancel, dan perubahan status laporan. Badge jumlah belum dibaca di sidebar.
- **Alasan pembatalan oleh user** (opsional; jika diisi min. 10 karakter bermakna, maks 255).
- **Ubah password** untuk semua role (`/account/password`).
- **Admin:** nonaktifkan/aktifkan akun, ubah role, reset password, aktifkan kembali user REJECTED.
- **Reservasi:** maksimal 5 PENDING per user dan tidak boleh bentrok dengan reservasi milik sendiri.
- **Petugas:** fasilitas tidak bisa diaktifkan kembali jika masih ada laporan BARU/DIPROSES lain.
- Edit fasilitas yang tidak ada → 404. Bar visual okupansi pada Analytics.

## Kebersihan kode
- Hapus `Reservation::cancel()`/`decide()` (tidak terpakai) dan fungsi `confirmReservationAction` ganda.
- Sidebar "Fasilitas" tidak lagi ikut aktif di `/admin/facilities`.
- Dashboard admin memakai `COUNT(*)`, bukan memuat seluruh tabel user.
- Auto-cancel per request dibatasi 1x/60 detik dan tidak mematikan halaman jika DB bermasalah (halaman riwayat/antrean tetap memanggil langsung).
- Dokumentasi: versi PHP, nama folder, dan peran ADMIN pada panel petugas diselaraskan.

## Sengaja belum diubah
- Okupansi belum mengurangi waktu maintenance (perlu tabel riwayat status fasilitas).
- ADMIN tetap 403 di panel petugas (sesuai skenario uji #6 di `IMPLEMENTATION_NOTES.md`); README yang disesuaikan.
- `views/petugas/dashboard.php` masih memakai `<style>` inline sendiri (sekarang menampilkan flash).
- Notifikasi email tidak ada (XAMPP tanpa SMTP); notifikasi hanya dalam aplikasi.

## Patch — 12 Oktober 2026

### Fitur baru
- **Antrean Reservasi (petugas)**: filter & sort by fasilitas, tanggal (input kalender `type=date`, sama seperti saat reservasi), jam (menampilkan reservasi yang sedang berlangsung pada jam tsb), dan pencarian user (nama/email). Header tabel bisa diklik untuk sort naik/turun; filter status tetap tersimpan saat filter lain dipakai. Semua input divalidasi/whitelist di `PetugasController::reservationQueue()` dan query memakai prepared statement (`Reservation::search()`).

### Perbaikan bug
- **Dialog konfirmasi reservasi muncul di kiri bawah & tidak bisa diklik**: penyebabnya browser memakai `style.css` versi lama dari cache sehingga gaya `.confirm-modal` tidak ada dan dialog tertutup sidebar. Sekarang `style.css` diberi versi otomatis (`?v=filemtime`) dan gaya inti dialog ditanam langsung di `layouts/footer.php`.
- **UI petugas berubah saat membuka Dashboard Petugas**: dashboard kini memakai layout bersama (`layouts/header.php`), sehingga sidebar sama di semua halaman petugas. Sidebar petugas menampilkan angka antrean: reservasi PENDING dan laporan kerusakan BARU (merah jika > 0).

## Patch 2 — 12 Oktober 2026

### Fitur baru
- **Search & sort Riwayat Reservasi (user)**: cari nama fasilitas/tujuan, filter status & tanggal (kalender), urutkan tanggal/waktu pengajuan/fasilitas/status. Judul kolom bisa diklik.
- **Search & sort Laporan Saya (user) dan Antrean Laporan (petugas)**: cari fasilitas/kategori/deskripsi (petugas juga nama/email pelapor), filter status, kategori & tanggal lapor, urutkan per kolom.
- Helper bersama `app/helpers/listing.php` (validasi input, URL filter, header sort, `menuCount()`).

### Notifikasi
- Angka notifikasi user **berkurang saat detail dibuka**: tombol "Lihat detail" melewati `/notifications/open?id=..` (tandai satu notifikasi terbaca lalu redirect, hanya ke path internal), dan membuka halaman detail reservasi/laporan juga otomatis menandai notifikasi terkait sebagai terbaca.
- Notifikasi belum dibaca ditandai titik merah.
- **Semua angka notifikasi memakai bentuk yang sama** (`menuCount()`: pill merah jika > 0, abu-abu jika 0): Notifikasi user, Antrean Reservasi & Laporan Kerusakan petugas, kartu dashboard petugas, dan **Dashboard Admin (jumlah user menunggu verifikasi)**.

### Laporan kerusakan
- **Foto kerusakan kini opsional** (form & validasi server). Jika diunggah tetap divalidasi JPG/PNG/WEBP maks. 2MB. Halaman detail menampilkan "Tidak ada foto yang dilampirkan" bila kosong.
