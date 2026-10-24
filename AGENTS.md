# Project Rules & PRD Reference — CineVerse (Website Bioskop)

> **Single Source of Truth:** Selalu gunakan dokumen [`PRD Website Bioskop (PHP + MySQL).md`](./PRD%20Website%20Bioskop%20(PHP%20+%20MySQL).md) sebagai acuan utama untuk fitur, skema database, arsitektur folder, alur bisnis, dan desain UI/UX.

## 1. Ringkasan Produk & Tech Stack
- **Nama Produk:** CineVerse (Website Pemesanan Tiket Bioskop Lokal)
- **Backend:** PHP 8.x native (tanpa framework), berjalan di XAMPP (`c:\xampp\htdocs\BIOSKOP`).
- **Database:** MySQL / MariaDB (`cineverse`) menggunakan **PDO prepared statements** untuk seluruh query.
- **Autentikasi (Plaintext Password):** Password pengguna disimpan dan dicocokkan langsung dalam bentuk **teks biasa (tanpa `password_hash()` / tanpa di-hash)** agar mudah dilihat dan diuji di database selama pembelajaran.
- **Frontend:** HTML5, CSS3 vanilla (dengan CSS Variables di `assets/css/style.css`), JavaScript vanilla (`fetch` + JSON, tanpa build tool).
- **CDN Libraries:** `Chart.js` (grafik dashboard admin), `qrcode.js` (QR code e-ticket), `html5-qrcode` (scan tiket via kamera oleh operator).

## 2. Peran Pengguna (Role-Based Access Control)
1. **Guest:** Melihat beranda, katalog film ("Sedang Tayang" & "Segera Hadir"), detail film, dan jadwal tayang.
2. **User:** Registrasi/login (plaintext password), pilih kursi interaktif (maksimal 6 kursi/pesanan), hold kursi 10 menit dengan *countdown timer*, simulasi pembayaran, lihat riwayat & cetak E-Ticket QR, pakai kode promo, watchlist, serta beri rating & ulasan.
3. **Operator:** Validasi tiket via input kode booking atau scan QR kamera (tolak jika sudah pernah di-scan), kelola jadwal tayang (`showtimes`), penjualan tiket langsung di loket (walk-in tanpa akun user), dan pantau okupansi kursi per sesi.
4. **Admin:** Dashboard ringkasan & grafik penjualan (`Chart.js`), CRUD film + upload poster lokal, CRUD studio + generate denah kursi otomatis (Regular/Premier), kelola harga tiket, kelola promo, kelola akun user/operator, dan ekspor laporan penjualan ke CSV.

## 3. Aturan Penting Database & Transaksi
- Database memiliki **13 tabel inti**: `users`, `genres`, `movies`, `movie_genres`, `studios`, `seats`, `showtimes`, `bookings`, `booking_seats`, `payments`, `promos`, `reviews`, `watchlists`.
- **Anti Double-Booking:** Tabel `booking_seats` wajib memiliki constraint `UNIQUE (showtime_id, seat_id)` agar dua pengguna tidak bisa memesan kursi yang sama pada jadwal yang sama.
- **Hold Timer 10 Menit:** Pesanan berstatus `pending` yang melewati `expires_at` (10 menit) otomatis diubah menjadi `expired` dan kursinya kembali tersedia.
- **Single Check-In:** Tiket hanya bisa divalidasi satu kali (`checked_in_at` & `checked_in_by`); percobaan scan kedua harus ditolak.

## 4. Sistem Desain UI/UX (Acuan untuk `impeccable` & `baseline-ui`)
### Palet Warna (Navy & Amber)
| Peran Warna | Mode Terang | Mode Gelap | Penggunaan |
| :--- | :--- | :--- | :--- |
| Background Utama | `#F7F7F5` | `#0E1320` | Latar halaman |
| Surface / Kartu | `#FFFFFF` | `#171E30` | Kartu film, panel, form |
| Teks Utama | `#1F2937` | `#E8EAF0` | Judul dan isi |
| Teks Sekunder | `#6B7280` | `#9AA3B5` | Keterangan dan label |
| Primer (Navy) | `#14213D` | `#14213D` | Header, footer, tombol sekunder |
| Aksen (Amber) | `#FCA311` | `#FCA311` | CTA utama, kursi dipilih, bintang rating |
| Sukses | `#2E9E6B` | `#3DB77F` | Pembayaran berhasil, tiket valid |
| Bahaya | `#D64545` | `#E5605F` | Kursi terisi, pesanan batal, tiket ditolak |
| Garis / Border | `#E5E7EB` | `#2A3350` | Border kartu dan tabel |

### Tipografi (Google Fonts)
- **Heading & Navigasi:** `Plus Jakarta Sans` — H1 `40px/700`, H2 `28px/700`, H3 `20px/600`
- **Body & Form:** `Inter` — Body `16px/400`, Label `14px/500`
- **Kode Booking & Nomor Kursi:** `DM Mono` — `14px/500`

### Tata Letak & Interaksi
- Lebar kontainer maksimal `1200px`, `border-radius: 12px`, sistem spacing kelipatan `8px`, navbar tetap di atas (*sticky*), responsif mulai dari lebar `360px`.
- Mode gelap (*dark mode*) menggunakan pertukaran nilai CSS Variables dan disimpan di `localStorage`.
- Animasi halus hanya menggunakan `transform` dan `opacity` serta wajib mematuhi `@media (prefers-reduced-motion: reduce)`.

## 5. Struktur Folder Target
```text
/
├── database.sql          # Skema 13 tabel inti + data seed (password plaintext)
├── includes/
│   ├── db.php            # Koneksi PDO
│   ├── auth.php          # Session & middleware cek peran
│   ├── functions.php     # Helper harga, kode booking, upload poster, auto-expire
│   └── layout/           # header.php, footer.php, navbar.php
├── public/               # Beranda, katalog, detail film, login, registrasi
├── user/                 # Pilih kursi, checkout, e-ticket, riwayat, watchlist
├── operator/             # Scan tiket, jadwal tayang, penjualan loket
├── admin/                # Dashboard, film, studio, akun, promo, laporan
├── api/                  # Endpoint JSON untuk fetch (pencarian, kursi, validasi)
├── assets/
│   ├── css/style.css     # CSS variables, komponen, animasi
│   ├── js/               # app.js, seat-map.js, scanner.js, charts.js
│   └── img/
└── uploads/              # Poster film yang di-upload admin
```
