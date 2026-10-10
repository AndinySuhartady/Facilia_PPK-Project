-- Jalankan ini SEKALI di phpMyAdmin (tab SQL) kalau database `facilia` Anda
-- sudah dibuat sebelum kolom cancelled_at ditambahkan ke schema.sql.
-- Aman dijalankan berkali-kali (ALTER akan gagal dengan pesan jelas jika kolom sudah ada,
-- tinggal diabaikan).

USE facilia;

ALTER TABLE reservations
    ADD COLUMN cancelled_at TIMESTAMP NULL AFTER cancellation_reason;
