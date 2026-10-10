USE facilia;

-- Password untuk semua akun demo di bawah: "password123"
-- Hash bcrypt asli (bisa diverifikasi dengan password_verify() di PHP)
INSERT INTO users (name, email, password, role, verification_status) VALUES
('Admin FACILIA', 'admin@facilia.test', '$2b$10$kNBZw388JC7Mi7Dxh6j91euLn5ht0oIwEKZlfzn7EK3ElpOx.QtTW', 'ADMIN', 'VERIFIED'),
('Petugas Satu', 'petugas@facilia.test', '$2b$10$kNBZw388JC7Mi7Dxh6j91euLn5ht0oIwEKZlfzn7EK3ElpOx.QtTW', 'PETUGAS', 'VERIFIED'),
('User Satu', 'user1@facilia.test', '$2b$10$kNBZw388JC7Mi7Dxh6j91euLn5ht0oIwEKZlfzn7EK3ElpOx.QtTW', 'USER', 'VERIFIED'),
('User Dua', 'user2@facilia.test', '$2b$10$kNBZw388JC7Mi7Dxh6j91euLn5ht0oIwEKZlfzn7EK3ElpOx.QtTW', 'USER', 'PENDING');

INSERT INTO facilities (name, type, location, capacity, status, description) VALUES
('Ruang Seminar A', 'Ruang Kelas', 'Gedung A Lantai 2', 40, 'ACTIVE', 'Ruang seminar ber-AC dengan proyektor.'),
('Lab Komputer 1', 'Laboratorium', 'Gedung B Lantai 1', 30, 'ACTIVE', 'Lab komputer untuk praktikum.'),
('Aula Serbaguna', 'Aula', 'Gedung Utama', 200, 'ACTIVE', 'Aula untuk acara besar.'),
('Lapangan Basket', 'Olahraga', 'Area Belakang Kampus', 20, 'MAINTENANCE', 'Sedang perbaikan lantai.'),
('Ruang Diskusi B', 'Ruang Kelas', 'Gedung A Lantai 3', 10, 'ACTIVE', 'Ruang diskusi kecil untuk kelompok.');
