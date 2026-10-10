# Kelengkapan pengumpulan FACILIA

Deadline sesuai brief: 11 Oktober 2026 pukul 12.00 WIB.

## Yang masih perlu diisi tim
- Nama dan NIM seluruh anggota serta pembagian tugas sebenarnya.
- URL repository GitHub/GitLab bersama. Setiap anggota melakukan commit pekerjaannya sendiri dengan pesan jelas; jangan membuat riwayat kontribusi fiktif.
- URL Google Drive berisi source code terbaru, SQL, serta seluruh berkas pendukung. Pastikan akses dapat dibuka dosen.
- Laporan PDF: identitas, pembagian tugas, link program, setting XAMPP/database/penjadwalan, akun demo tiap peran, screenshot dan penjelasan US1–US17.
- Bukti izin kelompok tiga orang dari koordinator, karena brief umum meminta 4/5 orang.

## Uji sebelum dikumpulkan
1. Registrasi USER menunggu verifikasi; PENDING/REJECTED gagal login. Admin dapat membuat USER/PETUGAS langsung.
2. Guest dapat mencari tipe/lokasi/kapasitas dan melihat slot tanpa data pemohon.
3. Tujuan kosong, tanggal hari ini, jam di luar 07–20, slot bukan 30 menit ditolak.
4. Pemilik dapat membuka detail dan membatalkan sebelum mulai melalui dua modal; akun lain tidak dapat mengakses detail.
5. Dua PENDING bentrok: hanya satu boleh disetujui. Fasilitas MAINTENANCE/INACTIVE tidak dapat disetujui.
6. Foto wajib: file kosong, jenis salah, ukuran >2 MB ditolak. Laporan dapat diproses/ditutup dengan catatan.
7. Maintenance membuat slot tidak tersedia, membatalkan booking mendatang; aktivasi setelah laporan ditutup.
8. Uji auto-cancel CLI dan Task Scheduler. Uji CSV/XLSX untuk periode berisi dan kosong.

## Catatan batas implementasi
Okupansi adalah jam booking APPROVED dibagi jam operasional periode, bukan sensor kehadiran. Riwayat waktu maintenance belum disimpan dan tidak dikurangi dari penyebut.
