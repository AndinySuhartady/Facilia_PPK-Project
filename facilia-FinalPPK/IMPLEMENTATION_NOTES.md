# Penyempurnaan Aturan Bisnis FACILIA

Implementasi ini mencakup butir 1–7 dan 9–12 dari daftar evaluasi. Butir 8
(validasi laporan/foto) sengaja tidak diubah.

## Perubahan utama

- Alasan pembatalan, penolakan, dan catatan status final harus berupa penjelasan
  minimal 10 karakter serta tidak boleh hanya angka/simbol.
- Pembatalan user, pembatalan darurat, keputusan reservasi, perubahan laporan,
  dan perubahan status fasilitas dibuat atomik terhadap status terakhir.
- Approval reservasi mengunci data fasilitas di dalam transaksi dan memeriksa
  ulang bentrok jadwal sebelum menyimpan keputusan.
- Aturan H-1, jam 07:00–20:00, serta kelipatan 30 menit tetap divalidasi di server.
- PENDING yang mencapai tanggal reservasi otomatis menjadi CANCELLED pada request
  aplikasi berikutnya, lengkap dengan alasan otomatis.
- Saat fasilitas menjadi MAINTENANCE/INACTIVE, reservasi APPROVED mendatang pada
  fasilitas tersebut otomatis dibatalkan dan memperoleh alasan.
- Akses dipisahkan tegas: USER untuk fitur user, PETUGAS untuk panel petugas,
  dan ADMIN untuk panel admin.
- Semua form POST memiliki CSRF token; logout diubah menjadi POST.
- Seluruh output teks dinamis tetap di-escape dengan `htmlspecialchars()` dan
  seluruh query yang menerima input menggunakan prepared statement.

## Skenario uji singkat

1. Masukkan alasan pembatalan `12344555555`: harus ditolak.
2. Masukkan alasan `Proyektor mendadak rusak`: modal dua tahap tampil dan aksi berhasil.
3. Coba menyetujui dua reservasi yang bertabrakan: keputusan kedua harus ditolak.
4. Coba memesan tanggal hari ini: harus ditolak oleh server.
5. Buka URL `/admin/dashboard` sebagai USER/PETUGAS: harus mendapat 403.
6. Buka URL `/petugas/dashboard` sebagai USER/ADMIN: harus mendapat 403.
7. Tandai fasilitas sebagai MAINTENANCE: reservasi APPROVED mendatang harus berubah
   menjadi CANCELLED dengan alasan maintenance.
8. Buka detail laporan yang sama pada dua sesi petugas; setelah sesi pertama
   mengubah status, aksi sesi kedua harus ditolak karena status sudah berubah.
