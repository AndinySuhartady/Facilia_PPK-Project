# Facilia_PPK-Project
Sistem reservasi dan pelaporan kerusakan fasilitas kampus untuk Project Pengembangan Platform Khusus (PPK) 2026, Kelompok 11 Kelas D.

FACILIA menggabungkan tiga modul dalam satu aplikasi PHP dengan pola MVC: autentikasi dan administrasi, layanan publik dan reservasi, serta laporan kerusakan dan layanan petugas.

## Anggota dan kontribusi

| Anggota | NIM | Kontribusi |
| --- | --- | --- |
| Andiny Khaerany Suhartady | 24060124140194 | Modul Laporan & Petugas; desain antarmuka dan CSS |
| Cindy Kurniawan | 24060124140201 | Modul Publik & Reservasi |
| Mischa Nathanael Lumban Tobing | 24060124140175 | Modul Auth & Admin; integrasi ketiga modul |

## Teknologi

- PHP 8+, PDO, dan MySQL/MariaDB.
- HTML, CSS, dan JavaScript.
- Apache dengan mod_rewrite; dapat dijalankan melalui XAMPP.
- Struktur MVC sederhana, tanpa Laravel dan tanpa kebutuhan Composer.

## Fitur

### Pengunjung dan pengguna

- Daftar dan pencarian fasilitas berdasarkan nama/lokasi, tipe, serta kapasitas minimum.
- Detail fasilitas dan ketersediaan per slot 30 menit, tanpa menampilkan identitas pemohon kepada pengunjung.
- Registrasi mandiri dengan verifikasi admin sebelum login.
- Pengajuan reservasi dengan tujuan penggunaan wajib diisi.
- Riwayat dan detail reservasi milik sendiri.
- Pembatalan reservasi milik sendiri melalui modal konfirmasi dua tahap.
- Laporan kerusakan dengan kategori, deskripsi, dan foto wajib.
- Pemantauan status laporan beserta catatan petugas.

### Petugas

- Dashboard dan antrean reservasi serta laporan.
- Persetujuan atau penolakan reservasi dengan pemeriksaan ulang status fasilitas dan bentrok jadwal.
- Pembatalan darurat reservasi yang sudah disetujui dengan alasan.
- Pemrosesan laporan dan catatan penyelesaian.
- Perubahan status fasilitas antara ACTIVE dan MAINTENANCE.

### Admin

- Pembuatan akun pengguna/petugas dan verifikasi registrasi mandiri.
- Pencarian serta penghapusan akun sesuai pembatasan riwayat transaksi.
- Penambahan, pengubahan, dan penonaktifan fasilitas.
- Rekap okupansi dan kerusakan berdasarkan periode, serta ekspor CSV dan Excel (XLSX).

## Aturan reservasi

- Jam operasional: **07.00–20.00 WIB**, dengan kelipatan **30 menit**.
- Reservasi minimal **H-1**; pemesanan untuk hari yang sama tidak diperbolehkan.
- Fasilitas hanya dapat dipesan saat berstatus **ACTIVE**.
- Reservasi yang sudah disetujui tidak boleh bertabrakan pada fasilitas yang sama.
- Pemilik hanya dapat membatalkan reservasi **PENDING/APPROVED sebelum tanggal dan jam mulai**.
- Reservasi PENDING yang belum disetujui ketika tanggal penggunaannya tiba dibatalkan otomatis.
- Zona waktu aplikasi: **Asia/Jakarta**.

## Struktur proyek

| Folder/file | Fungsi |
| --- | --- |
| app/controllers/ | Logika permintaan dan proses aplikasi |
| app/models/ | Akses data dan aturan model |
| app/middleware/ | Pembatasan akses berdasarkan autentikasi dan peran |
| app/helpers/ | Validasi, slot waktu, unggahan, dan ekspor |
| config/ | Konfigurasi aplikasi dan koneksi database |
| database/ | Skema SQL, seed akun/data demo, dan migrasi |
| public/ | Entry point, aset CSS/JavaScript, dan unggahan |
| routes/ | Pemetaan rute aplikasi |
| views/ | Tampilan publik, pengguna, petugas, dan admin |
| scripts/ | Auto-cancel melalui CLI dan pemasang jadwal opsional |

## Menjalankan dengan XAMPP

1. Ekstrak atau salin proyek ke **C:\xampp\htdocs\facilia-FinalPPK**. Pastikan folder app, config, database, public, dan views langsung berada di dalamnya.
2. Jalankan **Apache** dan **MySQL** pada XAMPP Control Panel.
3. Buka **http://localhost/phpmyadmin**. Untuk instalasi baru, impor **database/schema.sql**, kemudian **database/seed.sql**.
4. Periksa **config/database.php**. Konfigurasi bawaan menggunakan host localhost, database facilia, pengguna root, dan kata sandi kosong. Sesuaikan dengan MySQL lokal jika berbeda.
5. Pastikan ekstensi PHP **pdo_mysql** dan **fileinfo**, serta Apache **mod_rewrite**, aktif.
6. Pastikan **public/uploads/reports/** dapat ditulis oleh Apache.
7. Buka **http://localhost/facilia-FinalPPK/public/login**.

Daftar fasilitas publik dapat dibuka melalui **http://localhost/facilia-FinalPPK/public/facilities**. Base URL terdeteksi dari lokasi folder; jika nama folder berubah, sesuaikan URL di browser.

### Database versi lama

Jika database sebelumnya belum memiliki kolom cancelled_at, jalankan **database/migration_01_add_cancelled_at.sql** satu kali. Jangan mengimpor ulang seed pada database berisi data kerja tanpa memeriksa isinya terlebih dahulu.

## Akun demo

Semua akun demo menggunakan kata sandi **password123**.

| Peran | Email | Status awal dari seed |
| --- | --- | --- |
| Admin | admin@facilia.test | VERIFIED |
| Petugas | petugas@facilia.test | VERIFIED |
| Pengguna | user1@facilia.test | VERIFIED |
| Pengguna tambahan | user2@facilia.test | PENDING; perlu verifikasi admin sebelum login |

Akun dan kata sandi ini disediakan untuk demonstrasi lokal.

## Auto-cancel

Pemeriksaan reservasi PENDING yang kedaluwarsa berjalan ketika aplikasi menerima permintaan. Agar pemeriksaan juga berjalan tanpa kunjungan, tersedia **jadwal Windows opsional**.

Jalankan manual dari PowerShell:

```powershell
cd C:\xampp\htdocs\facilia-FinalPPK
C:\xampp\php\php.exe .\scripts\auto-cancel.php
```

Pasang jadwal:

```powershell
cd C:\xampp\htdocs\facilia-FinalPPK
powershell -ExecutionPolicy Bypass -File .\scripts\pasang-jadwal.ps1
```

Jadwal menggunakan pukul 00.00 menurut waktu Windows. Gunakan zona waktu WIB; komputer dan MySQL harus aktif. Penjadwalan ini tidak wajib untuk menjalankan aplikasi, dan komputer mati atau akun logout tidak menjamin eksekusi tepat tengah malam.

Untuk menghapus jadwal pada folder bernama facilia-FinalPPK:

```powershell
Unregister-ScheduledTask -TaskName "FACILIA-AutoCancel-facilia-FinalPPK" -Confirm:$false
```

## Rekap okupansi

Jam terpakai dihitung dari durasi reservasi APPROVED dalam periode yang dipilih. Persentase okupansi dihitung sebagai:

**jam terpakai / (jumlah hari dalam periode × 13 jam operasional) × 100%**

Periode bersifat inklusif. Penyebut belum mengurangi durasi maintenance/nonaktif karena database belum menyimpan riwayat perubahan status fasilitas. Rekap kerusakan menyertakan fasilitas dan lokasi; ekspor tersedia dalam CSV UTF-8 dan XLSX.

## Pengujian sebelum demo

- Registrasi pengguna baru, verifikasi oleh admin, lalu login/logout.
- Cari fasilitas dan periksa slot ACTIVE serta MAINTENANCE.
- Ajukan reservasi untuk besok; uji tujuan kosong, waktu tidak valid, serta pemesanan hari yang sama.
- Setujui reservasi melalui petugas dan periksa penolakan jadwal yang bentrok.
- Periksa detail reservasi, dua tahap modal pembatalan, dan batas waktu pembatalan.
- Buat laporan dengan foto, proses melalui petugas, lalu periksa status serta catatan pada akun pengguna.
- Periksa rekap dan buka hasil ekspor XLSX/CSV.

Daftar ini merupakan panduan pengujian, bukan klaim bahwa seluruh skenario sudah lulus pada setiap instalasi.

## Repository

https://github.com/AndinySuhartady/Facilia_PPK-Project

Setiap anggota melakukan commit kontribusinya sendiri dengan pesan yang menjelaskan perubahan. Laporan PDF, SQL, source code, serta petunjuk instalasi disertakan sesuai ketentuan pengumpulan mata kuliah.
