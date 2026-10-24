-- =========================================================
-- DATABASE CINEVERSE (13 Tabel Inti + Data Seed Lengkap)
-- Password disimpan PLAINTEXT sesuai aturan pembelajaran
-- =========================================================

CREATE DATABASE IF NOT EXISTS cineverse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cineverse;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS watchlists;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS booking_seats;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS promos;
DROP TABLE IF EXISTS showtimes;
DROP TABLE IF EXISTS seats;
DROP TABLE IF EXISTS studios;
DROP TABLE IF EXISTS movie_genres;
DROP TABLE IF EXISTS movies;
DROP TABLE IF EXISTS genres;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Tabel users (password plaintext)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL,
    role ENUM('user', 'operator', 'admin') NOT NULL DEFAULT 'user',
    phone VARCHAR(30) DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    points INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabel genres
CREATE TABLE genres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- 3. Tabel movies
CREATE TABLE movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    synopsis TEXT NOT NULL,
    duration_min INT NOT NULL DEFAULT 120,
    release_date DATE DEFAULT NULL,
    age_rating ENUM('SU', 'R13+', 'D17+', 'D21+') NOT NULL DEFAULT 'R13+',
    language VARCHAR(50) NOT NULL DEFAULT 'Indonesia',
    director VARCHAR(100) DEFAULT NULL,
    cast_list VARCHAR(255) DEFAULT NULL,
    poster VARCHAR(255) DEFAULT 'poster-1.svg',
    trailer_url VARCHAR(255) DEFAULT NULL,
    status ENUM('now_showing', 'coming_soon', 'archived') NOT NULL DEFAULT 'now_showing',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. Tabel movie_genres
CREATE TABLE movie_genres (
    movie_id INT NOT NULL,
    genre_id INT NOT NULL,
    PRIMARY KEY (movie_id, genre_id),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Tabel studios
CREATE TABLE studios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    type ENUM('regular', 'premier') NOT NULL DEFAULT 'regular',
    total_rows INT NOT NULL DEFAULT 6,
    total_cols INT NOT NULL DEFAULT 8,
    base_price INT NOT NULL DEFAULT 40000,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- 6. Tabel seats
CREATE TABLE seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    studio_id INT NOT NULL,
    row_label CHAR(2) NOT NULL,
    seat_number INT NOT NULL,
    seat_type ENUM('regular', 'premier') NOT NULL DEFAULT 'regular',
    UNIQUE KEY uq_studio_seat (studio_id, row_label, seat_number),
    FOREIGN KEY (studio_id) REFERENCES studios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Tabel showtimes
CREATE TABLE showtimes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    studio_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    price INT NOT NULL DEFAULT 40000,
    status ENUM('scheduled', 'cancelled') NOT NULL DEFAULT 'scheduled',
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (studio_id) REFERENCES studios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Tabel promos
CREATE TABLE promos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    type ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
    value INT NOT NULL,
    quota INT NOT NULL DEFAULT 100,
    valid_from DATE NOT NULL,
    valid_until DATE NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- 9. Tabel bookings
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_code VARCHAR(30) NOT NULL UNIQUE,
    user_id INT DEFAULT NULL,
    customer_name VARCHAR(100) DEFAULT NULL,
    showtime_id INT NOT NULL,
    promo_id INT DEFAULT NULL,
    discount_amount INT NOT NULL DEFAULT 0,
    points_used INT NOT NULL DEFAULT 0,
    total_price INT NOT NULL,
    status ENUM('pending', 'paid', 'cancelled', 'expired') NOT NULL DEFAULT 'pending',
    expires_at DATETIME NOT NULL,
    checked_in_at DATETIME DEFAULT NULL,
    checked_in_by INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id) ON DELETE CASCADE,
    FOREIGN KEY (promo_id) REFERENCES promos(id) ON DELETE SET NULL,
    FOREIGN KEY (checked_in_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 10. Tabel booking_seats (UNIQUE showtime_id + seat_id anti double-booking)
CREATE TABLE booking_seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    showtime_id INT NOT NULL,
    seat_id INT NOT NULL,
    UNIQUE KEY uq_showtime_seat (showtime_id, seat_id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id) ON DELETE CASCADE,
    FOREIGN KEY (seat_id) REFERENCES seats(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 11. Tabel payments
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    method ENUM('QRIS', 'Transfer Bank', 'E-Wallet', 'Tunai Loket') NOT NULL DEFAULT 'QRIS',
    amount INT NOT NULL,
    paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 12. Tabel reviews
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_movie_review (user_id, movie_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 13. Tabel watchlists
CREATE TABLE watchlists (
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, movie_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- SEED DATA
-- =========================================================

-- Seed Users (Password Plaintext)
INSERT INTO users (id, name, email, password, role, phone, points, is_active) VALUES
(1, 'Admin CineVerse', 'admin@cineverse.id', 'admin123', 'admin', '08111000111', 0, 1),
(2, 'Raka (Operator)', 'operator@cineverse.id', 'operator123', 'operator', '08122000222', 0, 1),
(3, 'Budi Santoso', 'budi@gmail.com', 'user123', 'user', '08133000333', 12, 1),
(4, 'Siti Rahma', 'siti@gmail.com', 'user123', 'user', '08144000444', 8, 1);

-- Seed Genres
INSERT INTO genres (id, name) VALUES
(1, 'Action'),
(2, 'Sci-Fi'),
(3, 'Drama'),
(4, 'Horror'),
(5, 'Thriller'),
(6, 'Animation'),
(7, 'Comedy'),
(8, 'Adventure');

-- Seed 8 Movies (6 Sedang Tayang, 2 Segera Hadir)
INSERT INTO movies (id, title, synopsis, duration_min, release_date, age_rating, language, director, cast_list, poster, trailer_url, status) VALUES
(1, 'Garuda di Ujung Langit', 'Seorang pilot uji coba pesawat tempur generasi baru harus memimpin misi penyelamatan darurat di perbatasan kepulauan saat badai geomagnetik memutus seluruh jalur komunikasi satelit.', 132, '2026-10-01', 'R13+', 'Indonesia', 'Timo Tjahjanto', 'Iko Uwais, Aghniny Haque, Rio Dewanto', 'poster-1.svg', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'now_showing'),
(2, 'Orbital Horizon', 'Stasiun penelitian di orbit Jupiter menangkap sinyal berulang dari bawah lapisan es Europa. Tim ekspedisi dikirim untuk mengungkap sumber transmisi sebelum orbit stasiun runtuh.', 148, '2026-10-05', 'R13+', 'Inggris', 'Denis Villeneuve', 'Cillian Murphy, Rebecca Ferguson, Oscar Isaac', 'poster-2.svg', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'now_showing'),
(3, 'Senja di Stasiun Kota', 'Pertemuan tak sengaja dua arsitek muda di kereta malam terakhir dari Jakarta ke Yogyakarta membuka kembali surat-surat keluarga yang hilang selama tiga dekade.', 115, '2026-10-10', 'SU', 'Indonesia', 'Yandy Laurens', 'Dion Wiyoko, Sheila Dara, Ringgo Agus Rahman', 'poster-3.svg', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'now_showing'),
(4, 'Pabrik Gula Tua', 'Sekelompok mahasiswa dokumentasi sejarah menginap di kompleks pabrik gula peninggalan kolonial yang sudah tutup sejak 1978, tanpa menyadari mesin gilingnya masih berbunyi setiap tengah malam.', 108, '2026-10-12', 'D17+', 'Indonesia', 'Joko Anwar', 'Tara Basro, Marissa Anita, Muzakki Ramdhan', 'poster-4.svg', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'now_showing'),
(5, 'Protokol Bayangan', 'Analis keamanan siber menemukan celah di jaringan perbankan nasional yang mengarah pada sindikat pencucian uang lintas negara. Ia punya waktu 24 jam sebelum identitasnya dihapus.', 124, '2026-10-15', 'D17+', 'Inggris', 'Christopher McQuarrie', 'Dev Patel, Florence Pugh, Hiroyuki Sanada', 'poster-5.svg', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'now_showing'),
(6, 'Petualangan Kancil & Bintang', 'Di hutan Nusantara yang mulai kehilangan mata airnya, seekor kancil cerdik bersama burung hantu penjaga rasi bintang melakukan perjalanan menuju Puncak Awan.', 98, '2026-10-18', 'SU', 'Indonesia', 'Ryan Adriandhy', 'Iqbaal Ramadhan, Sherina Munaf, Nirina Zubir', 'poster-6.svg', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'now_showing'),
(7, 'Warung Kopi Detektif', 'Tiga pensiunan polisi membuka warung kopi di pinggir pasar tradisional, namun pelanggan yang datang justru membawa teka-teki barang hilang yang kocak sekaligus pelik.', 110, '2026-11-10', 'R13+', 'Indonesia', 'Bene Dion Rajagukguk', 'Boris Bokir, Indra Jegel, Oki Rengga, Tora Sudiro', 'poster-7.svg', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'coming_soon'),
(8, 'Cyber Nusantara 2099', 'Di Neo-Surabaya tahun 2099, seorang kurir mekanik menemukan cip memori kuno yang menyimpan cetak biru energi bersih untuk seluruh Asia Tenggara.', 140, '2026-11-25', 'R13+', 'Indonesia', 'Angga Dwimas Sasongko', 'Chicco Jerikho, Pevita Pearce, Reza Rahadian', 'poster-8.svg', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'coming_soon');

-- Seed Movie Genres
INSERT INTO movie_genres (movie_id, genre_id) VALUES
(1, 1), (1, 8),
(2, 2), (2, 8),
(3, 3),
(4, 4), (4, 5),
(5, 1), (5, 5),
(6, 6), (6, 8),
(7, 7), (7, 5),
(8, 1), (8, 2);

-- Seed 3 Studios
INSERT INTO studios (id, name, type, total_rows, total_cols, base_price, is_active) VALUES
(1, 'Studio 1 Regular', 'regular', 6, 8, 40000, 1),
(2, 'Studio 2 Regular', 'regular', 6, 8, 45000, 1),
(3, 'Studio 3 Premier', 'premier', 4, 6, 75000, 1);

-- Seed Seats untuk Studio 1 (6 baris A-F x 8 kolom = 48 kursi)
INSERT INTO seats (studio_id, row_label, seat_number, seat_type) VALUES
(1,'A',1,'regular'),(1,'A',2,'regular'),(1,'A',3,'regular'),(1,'A',4,'regular'),(1,'A',5,'regular'),(1,'A',6,'regular'),(1,'A',7,'regular'),(1,'A',8,'regular'),
(1,'B',1,'regular'),(1,'B',2,'regular'),(1,'B',3,'regular'),(1,'B',4,'regular'),(1,'B',5,'regular'),(1,'B',6,'regular'),(1,'B',7,'regular'),(1,'B',8,'regular'),
(1,'C',1,'regular'),(1,'C',2,'regular'),(1,'C',3,'regular'),(1,'C',4,'regular'),(1,'C',5,'regular'),(1,'C',6,'regular'),(1,'C',7,'regular'),(1,'C',8,'regular'),
(1,'D',1,'regular'),(1,'D',2,'regular'),(1,'D',3,'regular'),(1,'D',4,'regular'),(1,'D',5,'regular'),(1,'D',6,'regular'),(1,'D',7,'regular'),(1,'D',8,'regular'),
(1,'E',1,'regular'),(1,'E',2,'regular'),(1,'E',3,'regular'),(1,'E',4,'regular'),(1,'E',5,'regular'),(1,'E',6,'regular'),(1,'E',7,'regular'),(1,'E',8,'regular'),
(1,'F',1,'regular'),(1,'F',2,'regular'),(1,'F',3,'regular'),(1,'F',4,'regular'),(1,'F',5,'regular'),(1,'F',6,'regular'),(1,'F',7,'regular'),(1,'F',8,'regular');

-- Seed Seats untuk Studio 2 (6 baris A-F x 8 kolom = 48 kursi)
INSERT INTO seats (studio_id, row_label, seat_number, seat_type) VALUES
(2,'A',1,'regular'),(2,'A',2,'regular'),(2,'A',3,'regular'),(2,'A',4,'regular'),(2,'A',5,'regular'),(2,'A',6,'regular'),(2,'A',7,'regular'),(2,'A',8,'regular'),
(2,'B',1,'regular'),(2,'B',2,'regular'),(2,'B',3,'regular'),(2,'B',4,'regular'),(2,'B',5,'regular'),(2,'B',6,'regular'),(2,'B',7,'regular'),(2,'B',8,'regular'),
(2,'C',1,'regular'),(2,'C',2,'regular'),(2,'C',3,'regular'),(2,'C',4,'regular'),(2,'C',5,'regular'),(2,'C',6,'regular'),(2,'C',7,'regular'),(2,'C',8,'regular'),
(2,'D',1,'regular'),(2,'D',2,'regular'),(2,'D',3,'regular'),(2,'D',4,'regular'),(2,'D',5,'regular'),(2,'D',6,'regular'),(2,'D',7,'regular'),(2,'D',8,'regular'),
(2,'E',1,'regular'),(2,'E',2,'regular'),(2,'E',3,'regular'),(2,'E',4,'regular'),(2,'E',5,'regular'),(2,'E',6,'regular'),(2,'E',7,'regular'),(2,'E',8,'regular'),
(2,'F',1,'regular'),(2,'F',2,'regular'),(2,'F',3,'regular'),(2,'F',4,'regular'),(2,'F',5,'regular'),(2,'F',6,'regular'),(2,'F',7,'regular'),(2,'F',8,'regular');

-- Seed Seats untuk Studio 3 Premier (4 baris A-D x 6 kolom = 24 kursi)
INSERT INTO seats (studio_id, row_label, seat_number, seat_type) VALUES
(3,'A',1,'premier'),(3,'A',2,'premier'),(3,'A',3,'premier'),(3,'A',4,'premier'),(3,'A',5,'premier'),(3,'A',6,'premier'),
(3,'B',1,'premier'),(3,'B',2,'premier'),(3,'B',3,'premier'),(3,'B',4,'premier'),(3,'B',5,'premier'),(3,'B',6,'premier'),
(3,'C',1,'premier'),(3,'C',2,'premier'),(3,'C',3,'premier'),(3,'C',4,'premier'),(3,'C',5,'premier'),(3,'C',6,'premier'),
(3,'D',1,'premier'),(3,'D',2,'premier'),(3,'D',3,'premier'),(3,'D',4,'premier'),(3,'D',5,'premier'),(3,'D',6,'premier');

-- Seed Showtimes (Hari ini dan Besok secara dinamis menggunakan CURDATE())
INSERT INTO showtimes (id, movie_id, studio_id, start_time, price, status) VALUES
(1, 1, 1, CONCAT(CURDATE(), ' 13:00:00'), 40000, 'scheduled'),
(2, 1, 1, CONCAT(CURDATE(), ' 16:00:00'), 40000, 'scheduled'),
(3, 1, 3, CONCAT(CURDATE(), ' 19:30:00'), 75000, 'scheduled'),
(4, 2, 2, CONCAT(CURDATE(), ' 14:00:00'), 45000, 'scheduled'),
(5, 2, 3, CONCAT(CURDATE(), ' 21:00:00'), 75000, 'scheduled'),
(6, 3, 1, CONCAT(CURDATE(), ' 19:00:00'), 40000, 'scheduled'),
(7, 4, 2, CONCAT(CURDATE(), ' 17:00:00'), 45000, 'scheduled'),
(8, 4, 2, CONCAT(CURDATE(), ' 20:30:00'), 45000, 'scheduled'),
(9, 5, 1, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 14:30:00'), 40000, 'scheduled'),
(10, 5, 3, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 19:00:00'), 75000, 'scheduled'),
(11, 6, 2, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 11:00:00'), 45000, 'scheduled'),
(12, 1, 1, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 18:30:00'), 40000, 'scheduled');

-- Seed Promos
INSERT INTO promos (id, code, type, value, quota, valid_from, valid_until, is_active) VALUES
(1, 'HEMAT20', 'percent', 20, 50, DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1),
(2, 'NONTON10K', 'fixed', 10000, 100, DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1);

-- Seed Bookings & Payments (Contoh transaksi berhasil agar grafik Dashboard Admin langsung terisi)
INSERT INTO bookings (id, booking_code, user_id, customer_name, showtime_id, promo_id, discount_amount, total_price, status, expires_at, checked_in_at, checked_in_by, created_at) VALUES
(1, 'CV-DEMO01', 3, 'Budi Santoso', 1, NULL, 0, 80000, 'paid', DATE_ADD(NOW(), INTERVAL 10 MINUTE), NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(2, 'CV-DEMO02', 4, 'Siti Rahma', 3, 2, 10000, 140000, 'paid', DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW(), 2, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'CV-DEMO03', 3, 'Budi Santoso', 4, NULL, 0, 90000, 'paid', DATE_ADD(NOW(), INTERVAL 10 MINUTE), NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Kursi yang terisi dari contoh booking di atas
-- Showtime 1 (Studio 1): kursi C4 (id 20) & C5 (id 21)
-- Showtime 3 (Studio 3): kursi B3 (id 105) & B4 (id 106)
-- Showtime 4 (Studio 2): kursi D4 (id 76) & D5 (id 77)
INSERT INTO booking_seats (booking_id, showtime_id, seat_id) VALUES
(1, 1, 20), (1, 1, 21),
(2, 3, 105), (2, 3, 106),
(3, 4, 76), (3, 4, 77);

INSERT INTO payments (booking_id, method, amount, paid_at) VALUES
(1, 'QRIS', 80000, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(2, 'E-Wallet', 140000, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'Transfer Bank', 90000, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Seed Reviews & Watchlists
INSERT INTO reviews (user_id, movie_id, rating, comment) VALUES
(3, 1, 5, 'Adegan manuver pesawat tempurnya tegang banget dan tata suaranya mantap!'),
(4, 2, 5, 'Visual luar angkasa dan misteri di bawah es Europa bikin merinding sepanjang film.'),
(3, 3, 4, 'Ceritanya hangat dan sinematografi kereta malamnya terasa sangat dekat.');

INSERT INTO watchlists (user_id, movie_id) VALUES
(3, 2), (3, 8), (4, 1);
