# PRD Website Bioskop (PHP + MySQL)

Oct 24, 2026 · @pais

## 1. Ringkasan dan Tujuan

CineVerse (nama sementara, silakan ganti) adalah website bioskop berbasis PHP dan MySQL yang berjalan di komputer lokal, dibuat untuk belajar membangun aplikasi web utuh: login berperan, CRUD, relasi database, transaksi pemesanan, dan laporan.

Tujuan produk:

- Pengunjung bisa melihat film, memilih jadwal dan kursi, lalu memesan tiket.
- Operator menjalankan kegiatan harian bioskop: validasi tiket dan pengelolaan jadwal tayang.
- Admin mengelola semua data master dan melihat laporan penjualan.
- Pembuat belajar PHP + PDO, SQL JOIN, session, pemrosesan form (`GET`/`POST`), upload file, dan CSS modern (murni HTML + CSS tanpa JavaScript) lewat satu proyek yang terasa seperti produk sungguhan.

## 2. Ruang Lingkup, Asumsi, dan Tech Stack

Proyek ini hanya berjalan lokal (misalnya `http://localhost/cineverse`), tanpa hosting atau deploy.

Asumsi:

- Keamanan tidak jadi fokus: tanpa token CSRF, tanpa pembatasan percobaan login, validasi input seperlunya.
- **Password tidak di-hash (plaintext):** Password disimpan dan dicocokkan langsung dalam bentuk teks biasa di database supaya mudah dicek lewat phpMyAdmin saat belajar. Query database tetap memakai prepared statement PDO.
- **Tanpa JavaScript (Zero JS):** Seluruh interaksi (pilih kursi dengan `<input type="checkbox">`, pencarian/filter form `GET`, validasi kode booking, hingga grafik bar di dashboard admin) dibangun murni dengan PHP, HTML5, dan CSS3.
- Pembayaran hanya simulasi: tombol "Bayar" mengubah status pesanan, tanpa payment gateway.
- Data film diisi manual oleh admin, poster di-upload ke folder lokal.
- Di luar lingkup: email atau SMS asli, aplikasi mobile, payment gateway nyata, deploy ke server.

| Komponen | Pilihan | Alasan |
| --- | --- | --- |
| Bahasa backend | PHP 8.x native, tanpa framework | Konsep dasar terlihat jelas saat belajar |
| Database | MySQL / MariaDB lewat PDO | Bawaan XAMPP dan Laragon |
| Server lokal | XAMPP atau Laragon | Apache + MySQL siap pakai |
| Frontend | HTML5 dan CSS3 vanilla (tanpa JavaScript) | Fokus penuh pada alur server-side PHP dan CSS modern |
| Visualisasi & Tiket | Grafik CSS murni & Kode Booking unik | Tanpa ketergantungan library JS eksternal |
| Font | Google Fonts | Lihat bagian Desain UI/UX |

## 3. Peran Pengguna dan Hak Akses

Ada tiga peran berakun, ditambah pengunjung tanpa login (guest) yang hanya boleh melihat katalog.

| Peran | Siapa | Tugas utama |
| --- | --- | --- |
| User | Penonton terdaftar | Mencari film, memesan tiket, melihat riwayat dan e-ticket |
| Operator | Petugas bioskop | Validasi tiket di pintu, kelola jadwal tayang, jual tiket di loket |
| Admin | Pengelola sistem | Kelola film, studio, harga, promo, akun, dan laporan |

| Kemampuan | Guest | User | Operator | Admin |
| --- | --- | --- | --- | --- |
| Lihat film dan jadwal | Ya | Ya | Ya | Ya |
| Pesan tiket online | - | Ya | - | - |
| Riwayat dan e-ticket sendiri | - | Ya | - | - |
| Beri rating dan ulasan | - | Ya | - | - |
| Validasi tiket (scan QR) | - | - | Ya | Ya |
| Kelola jadwal tayang | - | - | Ya | Ya |
| Jual tiket di loket | - | - | Ya | Ya |
| Kelola film, studio, harga | - | - | - | Ya |
| Kelola promo | - | - | - | Ya |
| Kelola akun user dan operator | - | - | - | Ya |
| Lihat laporan penjualan | - | - | - | Ya |

## 4. Fitur per Peran

Prioritas: **P1** inti, dibuat lebih dulu. **P2** fitur modern yang menaikkan kualitas. **P3** tantangan tambahan kalau waktu cukup.

### User dan guest

| Fitur | Deskripsi | Prioritas |
| --- | --- | --- |
| Daftar dan login | Registrasi, login, logout dengan session, ubah profil | P1 |
| Katalog film | Grid "Sedang Tayang" dan "Segera Hadir" dengan poster, genre, durasi, batas usia | P1 |
| Detail film | Sinopsis, pemeran, trailer YouTube (embed), jadwal tayang per hari | P1 |
| Pilih kursi interaktif | Denah kursi per studio dengan status kosong, dipilih, terisi; maksimal 6 kursi per pesanan | P1 |
| Simulasi pembayaran | Pilih metode (QRIS, transfer, e-wallet palsu) lalu klik Bayar | P1 |
| Riwayat pesanan | Daftar pesanan beserta status dan tiketnya | P1 |
| Pencarian dan filter | Cari judul, filter genre atau bahasa lewat form `GET` PHP | P2 |
| Batas waktu pembayaran | Kursi ditahan 10 menit (`expires_at`), dicek otomatis oleh PHP dan lewat batas pesanan kedaluwarsa | P2 |
| E-ticket | Tiket digital dengan kode booking unik, siap cetak lewat stylesheet `@media print` | P2 |
| Kode promo | Voucher diskon persen atau nominal | P2 |
| Watchlist | Simpan film ke daftar "Ingin ditonton" | P2 |
| Rating dan ulasan | Bintang 1 sampai 5 dan komentar untuk film yang sudah ditonton | P2 |
| Mode gelap | Toggle terang/gelap berbasis session/cookie PHP dan CSS Variables | P2 |
| Pre-order makanan | Tambah popcorn dan minuman ke pesanan | P3 |
| Poin loyalitas | 1 poin tiap Rp10.000, poin ditukar diskon | P3 |

### Operator

| Fitur | Deskripsi | Prioritas |
| --- | --- | --- |
| Validasi tiket | Input kode booking via form PHP; tiket ditandai terpakai dan ditolak jika dipakai dua kali | P1 |
| Kelola jadwal tayang | Tambah, ubah, batalkan jadwal (film, studio, jam, harga) | P1 |
| Jual tiket di loket | Pesan tiket atas nama pembeli tanpa akun, bayar tunai | P2 |
| Daftar penonton per sesi | Kursi terisi dan jumlah tiket tiap jadwal | P2 |
| Status studio | Tandai studio buka atau ditutup untuk perawatan | P3 |

### Admin

| Fitur | Deskripsi | Prioritas |
| --- | --- | --- |
| Dashboard | Kartu ringkas (pendapatan, tiket terjual, film terlaris) dan grafik bar CSS murni | P1 |
| CRUD film | Tambah, ubah, hapus film, upload poster, atur status tayang | P1 |
| CRUD studio dan kursi | Atur jumlah baris dan kolom kursi serta tipe studio (Regular, Premier) | P1 |
| Kelola akun | Lihat user dan operator, ubah peran, nonaktifkan akun | P1 |
| Kelola promo | Kode promo dengan masa berlaku dan kuota | P2 |
| Atur harga tiket | Harga per tipe studio dan per hari (weekday atau weekend) | P2 |
| Laporan penjualan | Filter tanggal, tabel ringkas, ekspor CSV | P2 |
| Moderasi ulasan | Hapus ulasan yang tidak pantas | P3 |
| Log aktivitas | Catatan siapa melakukan apa dan kapan | P3 |

## 5. Alur Pengguna Utama

Alur inti adalah pemesanan tiket, dari memilih film sampai dipindai operator di pintu studio.

&#91;embedded content: alur pemesanan tiket · 2 keputusan\]

Pesanan yang tidak dibayar dalam 10 menit kedaluwarsa dan kursinya bebas lagi. Tiket yang sudah dipindai sekali ditolak bila dipindai ulang.

## 6. Desain Database

Satu database bernama `cineverse` dengan 13 tabel inti (fitur P3 seperti makanan dan log menambah tabel sendiri nanti). Aturan terpenting: kombinasi `showtime_id` + `seat_id` di tabel `booking_seats` dibuat UNIQUE, sehingga database sendiri menolak dua pesanan untuk kursi yang sama di jadwal yang sama.

| Tabel | Kolom penting | Relasi |
| --- | --- | --- |
| `users` | id, name, email, password, role (user / operator / admin), phone, points, is\_active, created\_at | 1 user punya banyak booking, review, watchlist |
| `genres` | id, name | Banyak ke banyak dengan movies |
| `movies` | id, title, synopsis, duration\_min, release\_date, age\_rating, language, director, cast\_list, poster, trailer\_url, status (now\_showing / coming\_soon / archived) | 1 film punya banyak showtime dan review |
| `movie_genres` | movie\_id, genre\_id | Tabel penghubung movies dan genres |
| `studios` | id, name, type (regular / premier), total\_rows, total\_cols, is\_active | 1 studio punya banyak seats dan showtimes |
| `seats` | id, studio\_id, row\_label, seat\_number, seat\_type | Milik satu studio |
| `showtimes` | id, movie\_id, studio\_id, start\_time, price, status | Menghubungkan film, studio, dan booking |
| `bookings` | id, booking\_code, user\_id (boleh kosong untuk loket), showtime\_id, promo\_id, total\_price, status (pending / paid / cancelled / expired), expires\_at, checked\_in\_at, checked\_in\_by, created\_at | Satu booking punya banyak booking\_seats dan satu payment |
| `booking_seats` | id, booking\_id, showtime\_id, seat\_id | UNIQUE (showtime\_id, seat\_id) |
| `payments` | id, booking\_id, method, amount, paid\_at | Satu per booking |
| `promos` | id, code, type (percent / fixed), value, quota, valid\_from, valid\_until, is\_active | Dipakai di bookings |
| `reviews` | id, user\_id, movie\_id, rating, comment, created\_at | UNIQUE (user\_id, movie\_id) |
| `watchlists` | user\_id, movie\_id | Tabel penghubung users dan movies |

Data contoh (seed) yang disediakan di `database.sql`: satu akun untuk tiap peran, 8 film, 3 studio dengan denah kursi, dan beberapa jadwal tayang untuk hari ini dan besok.

## 7. Desain UI/UX

Gaya visual: bersih, banyak ruang kosong, satu warna aksen, sudut membulat, dan poster film sebagai pusat perhatian. Warna dan font disimpan sebagai CSS variables di satu file `style.css` supaya mudah diganti dan mode gelap tinggal menukar nilainya.

### Palet warna

Kombinasi navy dan amber: navy memberi kesan sinematik yang tenang, amber seperti lampu proyektor dan dipakai hanya untuk hal yang perlu diklik atau dipilih.

| Peran warna | Terang | Gelap | Dipakai untuk |
| --- | --- | --- | --- |
| Latar halaman | `#F7F7F5` | `#0E1320` | Background utama |
| Permukaan (kartu) | `#FFFFFF` | `#171E30` | Kartu film, panel, form |
| Teks utama | `#1F2937` | `#E8EAF0` | Judul dan isi |
| Teks sekunder | `#6B7280` | `#9AA3B5` | Keterangan, label |
| Primer (navy) | `#14213D` | `#14213D` | Header, footer, tombol sekunder |
| Aksen (amber) | `#FCA311` | `#FCA311` | Tombol utama, kursi dipilih, bintang rating |
| Sukses | `#2E9E6B` | `#3DB77F` | Pembayaran berhasil, tiket valid |
| Bahaya | `#D64545` | `#E5605F` | Kursi terisi, pesanan batal, tiket ditolak |
| Garis tipis | `#E5E7EB` | `#2A3350` | Border kartu dan tabel |

### Tipografi

| Fungsi | Font | Ukuran dan berat |
| --- | --- | --- |
| Judul dan menu | Plus Jakarta Sans | H1 40px / 700, H2 28px / 700, H3 20px / 600 |
| Isi dan form | Inter | 16px / 400, label 14px / 500 |
| Kode booking dan nomor kursi | DM Mono | 14px / 500 |

Ketiganya tersedia gratis di Google Fonts. Kalau ingin bisa jalan tanpa internet, unduh file font dan pasang lewat `@font-face`.

### Animasi (sedikit dan halus, murni CSS)

- Kartu film muncul dengan animasi `@keyframes` fade dan geser naik 16px, 400 ms.
- Hover kartu film: naik 6px, poster membesar 5%, bayangan lembut, 200 ms.
- Kursi (`<input type="checkbox">` + `<label>`): membesar sedikit saat di-hover dan berubah warna saat dipilih (`:checked`), 150 ms.
- Tombol: warna berubah halus dan mengecil sedikit saat ditekan (`:active`).
- Semua animasi hanya memakai CSS `transform` dan `opacity`, dan dimatikan bila pengguna mengaktifkan `prefers-reduced-motion`.

### Halaman utama dan tata letak

Grid maksimal 1200px, sudut membulat 12px, jarak antar elemen kelipatan 8px, navbar tetap di atas, responsif dari lebar 360px.

| Halaman | Isi utama |
| --- | --- |
| Beranda | Hero film unggulan, baris "Sedang Tayang", baris "Segera Hadir" |
| Daftar film | Grid poster, kolom pencarian dan filter genre |
| Detail film | Poster besar, sinopsis, trailer, jadwal per hari, ulasan |
| Pilih kursi | Layar di atas, denah kursi (checkbox CSS), tombol lanjut ke checkout |
| Checkout | Informasi batas waktu 10 menit (`expires_at`), kode promo, metode bayar |
| E-ticket | Kartu tiket dengan kode booking unik dan tampilan siap cetak (`@media print`) |
| Login dan registrasi | Form di tengah dengan latar poster buram |
| Panel Operator | Kotak input validasi kode tiket, jadwal hari ini |
| Dashboard Admin | Kartu ringkas, grafik bar CSS, tabel kelola data dengan sidebar |

## 8. Arsitektur dan Struktur Folder

Aplikasi dibuat sederhana tanpa framework dan tanpa JavaScript: halaman PHP per peran memakai file bersama di `includes/` untuk koneksi database, cek login, dan fungsi umum.

&#91;embedded content: arsitektur aplikasi · 4 lapisan\]

Struktur folder yang disarankan:

```text
cineverse/
  database.sql          skema dan data contoh
  includes/
    db.php              koneksi PDO
    auth.php            session dan cek peran
    functions.php       harga, kode booking, upload
    layout/             header.php, footer.php, navbar.php
  public/               beranda, katalog, detail film, login, registrasi
  user/                 pilih kursi, checkout, e-ticket, riwayat, watchlist
  operator/             validasi tiket, jadwal tayang, penjualan loket
  admin/                dashboard, film, studio, akun, promo, laporan
  assets/
    css/style.css       CSS variables, komponen, animasi CSS
    img/
  uploads/              poster film
```

## 9. Kebutuhan Non-Fungsional dan Kriteria Penerimaan

- Jalan di PHP 8.1 ke atas dan MySQL 8 atau MariaDB lewat XAMPP atau Laragon; database dipasang dengan mengimpor satu file `database.sql`.
- Tampilan nyaman dari lebar 360px (HP) sampai desktop.
- Kode rapi: folder terpisah per peran, koneksi database satu file, semua query memakai PDO prepared statement.

Produk dianggap selesai bila semua poin ini lolos:

- [ ] User bisa daftar, login, memesan 2 kursi, membayar (simulasi), dan melihat e-ticket.
- [ ] Dua user tidak bisa memesan kursi yang sama di jadwal yang sama.
- [ ] Pesanan berstatus pending lewat 10 menit otomatis kedaluwarsa dan kursinya bebas lagi.
- [ ] Operator bisa memvalidasi kode tiket, dan tiket yang sama ditolak saat divalidasi kedua kali.
- [ ] Operator bisa menambah jadwal tayang dan jadwal itu langsung muncul di halaman film.
- [ ] Admin bisa menambah film beserta poster dan film langsung tampil di beranda.
- [ ] Dashboard admin menampilkan pendapatan dan grafik bar CSS yang sesuai data pesanan.
- [ ] User biasa tidak bisa membuka halaman operator atau admin lewat URL.
- [ ] Mode gelap berfungsi dan tampilan tetap rapi di layar HP.

## 10. Tahapan Pengerjaan

Urutan ini dibuat supaya tiap tahap menghasilkan sesuatu yang sudah bisa dicoba di browser.

1. **Fondasi.** Pasang XAMPP atau Laragon, buat struktur folder, impor `database.sql`, tulis `db.php` (koneksi PDO), template header dan footer, serta CSS variables.
2. **Login dan peran.** Registrasi, login (password plaintext), logout, session, dan penjaga halaman berdasarkan peran.
3. **Katalog film.** CRUD film oleh admin, halaman beranda, daftar film, dan detail film.
4. **Studio, kursi, dan jadwal.** CRUD studio dengan pembuatan kursi otomatis, lalu pengelolaan jadwal tayang oleh operator.
5. **Pemesanan.** Denah kursi dengan HTML checkbox + CSS, simpan pesanan, hitung harga, batas waktu 10 menit, dan simulasi pembayaran.
6. **E-ticket dan validasi.** E-ticket dengan kode booking unik, panel operator untuk memvalidasi kode tiket.
7. **Dashboard dan laporan.** Kartu ringkas, grafik bar CSS, laporan penjualan dan ekspor CSV.
8. **Poles.** Animasi CSS, mode gelap, responsif, lalu uji semua kriteria penerimaan.
9. **Opsional (P2 dan P3).** Promo, watchlist, ulasan, pre-order makanan, poin loyalitas.

Setelah semuanya jadi, langkah belajar berikutnya yang wajar: menambahkan keamanan (token CSRF, validasi input ketat, batas percobaan login), memindahkan proyek ke framework seperti Laravel, atau membuat API REST untuk aplikasi mobile.
