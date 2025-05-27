-- Buat database
CREATE DATABASE phpus;

-- Gunakan database tersebut
USE phpus;

-- Tabel users
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  nama_lengkap VARCHAR(100) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin', 'user') NOT NULL
);

-- Tabel buku
CREATE TABLE buku (
  id INT AUTO_INCREMENT PRIMARY KEY,
  judul VARCHAR(255) NOT NULL,
  pengarang VARCHAR(255) NOT NULL,
  penerbit VARCHAR(255) NOT NULL,
  tahun_terbit INT NOT NULL DEFAULT 2000,
  genre VARCHAR(100) NOT NULL,
  stok INT NOT NULL,
  gambar_path VARCHAR(255)
);

-- Insert with proper nama_lengkap values and hashed passwords
INSERT INTO users (username, nama_lengkap, password, role) VALUES
('admin', 'Administrator Sistem', 'admin', 'admin'), -- Replace with actual hashed password
('user', 'User Contoh', 'user', 'user'); -- Replace with actual hashed password

-- Tabel peminjaman
CREATE TABLE IF NOT EXISTS peminjaman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    buku_id INT NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    tanggal_kembali DATE NOT NULL,
    status ENUM('dipinjam', 'dikembalikan') NOT NULL DEFAULT 'dipinjam',
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (buku_id) REFERENCES buku(id)
);
-- hello
-- ONLY RUN THIS IF YOU'RE UPDATING AN EXISTING DATABASE WITHOUT THE gambar_path COLUMN
-- ALTER TABLE buku ADD COLUMN gambar_path VARCHAR(255) DEFAULT 'images/default_book.jpg';

