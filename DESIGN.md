---
name: CineVerse
description: Sistem desain pemesanan tiket bioskop lokal dengan kontras sinematik Navy dan cahaya proyektor Amber.
colors:
  cinematic-navy: "#14213D"
  projector-amber: "#FCA311"
  projector-amber-deep: "#E59009"
  warm-paper: "#F7F7F5"
  card-surface: "#FFFFFF"
  subtle-ash: "#F1F2F6"
  charcoal-ink: "#1F2937"
  muted-slate: "#6B7280"
  hairline-gray: "#E5E7EB"
  theater-night: "#0E1320"
  booth-slate: "#171E30"
  booth-elevated: "#1E273D"
  screen-ivory: "#E8EAF0"
  dimmed-silver: "#9AA3B5"
  booth-border: "#2A3350"
  ticket-emerald: "#2E9E6B"
  velvet-crimson: "#D64545"
typography:
  display:
    fontFamily: "Plus Jakarta Sans, sans-serif"
    fontSize: "clamp(1.75rem, 3vw, 2.5rem)"
    fontWeight: 700
    lineHeight: 1.25
  headline:
    fontFamily: "Plus Jakarta Sans, sans-serif"
    fontSize: "clamp(1.35rem, 2.2vw, 1.75rem)"
    fontWeight: 700
    lineHeight: 1.25
  title:
    fontFamily: "Plus Jakarta Sans, sans-serif"
    fontSize: "1.25rem"
    fontWeight: 600
    lineHeight: 1.25
  body:
    fontFamily: "Inter, system-ui, -apple-system, sans-serif"
    fontSize: "16px"
    fontWeight: 400
    lineHeight: 1.6
  label:
    fontFamily: "DM Mono, monospace"
    fontSize: "14px"
    fontWeight: 500
    lineHeight: 1.4
rounded:
  sm: "6px"
  md: "8px"
  lg: "10px"
  xl: "12px"
  pass: "16px"
  pill: "999px"
spacing:
  xs: "8px"
  sm: "12px"
  md: "16px"
  lg: "24px"
  xl: "32px"
  xxl: "40px"
components:
  button-primary:
    backgroundColor: "{colors.projector-amber}"
    textColor: "{colors.cinematic-navy}"
    typography: "{typography.title}"
    rounded: "{rounded.lg}"
    padding: "10px 18px"
  button-primary-hover:
    backgroundColor: "{colors.projector-amber-deep}"
  button-secondary:
    backgroundColor: "{colors.cinematic-navy}"
    textColor: "{colors.card-surface}"
    rounded: "{rounded.lg}"
    padding: "10px 18px"
  card-surface:
    backgroundColor: "{colors.card-surface}"
    textColor: "{colors.charcoal-ink}"
    rounded: "{rounded.xl}"
    padding: "{spacing.lg}"
  seat-available:
    backgroundColor: "{colors.subtle-ash}"
    textColor: "{colors.charcoal-ink}"
    typography: "{typography.label}"
    rounded: "{rounded.md}"
    width: "42px"
    height: "38px"
  seat-selected:
    backgroundColor: "{colors.projector-amber}"
    textColor: "{colors.cinematic-navy}"
    typography: "{typography.label}"
    rounded: "{rounded.md}"
    width: "42px"
    height: "38px"
  seat-occupied:
    backgroundColor: "{colors.velvet-crimson}"
    textColor: "{colors.card-surface}"
    typography: "{typography.label}"
    rounded: "{rounded.md}"
    width: "42px"
    height: "38px"
---

# Design System: CineVerse

## Overview

**Creative North Star: "The Modern Projection Booth"**

CineVerse menggabungkan ketenangan ruang bioskop gelap dengan presisi instrumen ruang proyektor modern. Seluruh antarmuka dirancang agar poster film dan status ketersediaan kursi langsung memimpin hierarki visual, sementara elemen navigasi dan panel administrasi tetap tenang dengan ruang kosong yang lega.

Sistem ini menggunakan pendekatan **Flat-by-Default & Hairline-Framed** serta komponen yang **Tactile & High-Signal**: setiap permukaan kartu dibingkai garis tipis `1px` tanpa bayangan dekoratif berlebih, tombol dan kursi merespons sentuhan secara presisi, dan setiap data transaksi (nomor kursi, jam tayang, kode booking, dan harga) selalu ditampilkan dengan font monospasi tabular. Sistem ini menolak tegas gradien warna-warni, efek kaca buram (*glassmorphism*) dekoratif, dan bayangan keras (*hard offset shadows*).

**Key Characteristics:**
- **Poster-First Hierarchy:** Poster film (`2:3`) dan status kursi menjadi jangkar visual utama di setiap halaman publik dan pemesanan.
- **Projector Amber Signal:** Warna aksen Amber dipakai secara disiplin hanya untuk aksi utama (CTA), kursi yang sedang dipilih, dan sorotan telemetri tiket.
- **High-Signal Monospace Telemetry:** Koordinat kursi (`A1`, `C4`), kode booking (`CV-DEMO01`), durasi, dan harga selalu menggunakan angka tabular monospasi.
- **Dual-Mode Native Parity:** Mendukung pertukaran instan antara Mode Terang (*Warm Paper*) dan Mode Gelap (*Theater Night*) lewat variabel CSS.

## Colors

Palet warna dibangun di atas kontras antara biru malam sinematik yang tenang dan sorot lampu proyektor kuning-oranye yang hangat.

### Primary
- **Cinematic Navy** (`#14213D`): Jangkar utama identitas merek; digunakan pada navbar *sticky*, *Hero banner*, pita kepala E-Ticket, dan tombol sekunder.
- **Projector Amber** (`#FCA311`): Satu-satunya warna aksen interaktif; digunakan eksklusif untuk tombol aksi utama, kursi yang dipilih, badge peran, dan bintang ulasan.
- **Projector Amber Deep** (`#E59009`): Status `:hover` pada tombol utama untuk memberi umpan balik tekan yang jelas.

### Secondary
- **Ticket Emerald** (`#2E9E6B`): Penanda status semantik berhasil; digunakan untuk badge pembayaran `PAID` dan hasil validasi tiket yang sah di pintu studio.
- **Velvet Crimson** (`#D64545`): Penanda status bahaya dan terkunci; digunakan pada kursi yang sudah terisi (`occupied`), tiket yang ditolak saat dipindai ulang, serta pesanan batal/kedaluwarsa.

### Neutral
- **Warm Paper** (`#F7F7F5`) & **Theater Night** (`#0E1320`): Latar halaman utama pada mode terang dan mode gelap.
- **Card Surface** (`#FFFFFF`) & **Booth Slate** (`#171E30`): Permukaan utama kartu film, form checkout, panel operator, dan tiket.
- **Subtle Ash** (`#F1F2F6`) & **Booth Elevated** (`#1E273D`): Latar sekunder untuk slot jadwal tayang, kursi tersedia, header tabel, dan potongan sobekan tiket (*gate stub*).
- **Charcoal Ink** (`#1F2937`) & **Screen Ivory** (`#E8EAF0`): Teks utama dengan kontras baca tinggi.
- **Muted Slate** (`#6B7280`) & **Dimmed Silver** (`#9AA3B5`): Teks keterangan sekunder, metadata film, dan label kolom.
- **Hairline Gray** (`#E5E7EB`) & **Booth Border** (`#2A3350`): Garis pembatas `1px` untuk kartu, input form, dan sel tabel.

### Named Rules
**The Projector Beam Rule.** Warna Projector Amber (`#FCA311`) hanya boleh muncul pada elemen yang dapat diklik, dipilih, atau menunjukkan nilai sorotan utama; jangan pernah menggunakannya sebagai latar kartu penuh atau hiasan dekoratif.

**The Semantic Seat Rule.** Pada denah studio, tiga warna memiliki arti mutlak yang tidak boleh ditukar: abu-abu terang/gelap untuk kursi kosong, Projector Amber untuk kursi dipilih, dan Velvet Crimson untuk kursi terisi.

## Typography

**Display Font:** `Plus Jakarta Sans` (dengan fallback `sans-serif`)
**Body Font:** `Inter` (dengan fallback `system-ui, -apple-system, sans-serif`)
**Label/Mono Font:** `DM Mono` (dengan fallback `monospace`)

**Character:** Kombinasi geometris modern yang tegas pada judul (`Plus Jakarta Sans`), keterbacaan netral yang bersih pada paragraf dan form (`Inter`), serta ketepatan teknis ala mesin cetak tiket pada kode dan angka (`DM Mono`).

### Hierarchy
- **Display** (`700`, `clamp(1.75rem, 3vw, 2.5rem)`, `1.25` line-height): Judul utama halaman dan judul film unggulan pada *Hero banner* (`text-wrap: balance`).
- **Headline** (`700`, `clamp(1.35rem, 2.2vw, 1.75rem)`, `1.25` line-height): Subjudul bagian (*Sedang Tayang*, *Segera Hadir*) dan judul film pada E-Ticket.
- **Title** (`600`, `1.25rem`, `1.25` line-height): Judul kartu panel, ringkasan pesanan, dan modal/form.
- **Body** (`400`, `16px`, `1.6` line-height): Sinopsis film, ulasan penonton, dan isi tabel (lebar baca optimal `65ch`, `text-wrap: pretty`).
- **Label** (`500`, `14px`, `1.4` line-height): Koordinat kursi, kode booking, jam tayang, durasi, dan nominal Rupiah (`font-variant-numeric: tabular-nums`).

### Named Rules
**The Tabular Telemetry Rule.** Seluruh harga Rupiah, penghitung waktu mundur 10 menit, koordinat kursi, dan angka laporan wajib menggunakan `font-variant-numeric: tabular-nums` dan/atau `DM Mono` agar kolom angka tidak bergeser saat nilainya berubah.

## Layout

Tata letak menggunakan kontainer terpusat dengan lebar maksimum `1200px` dan *padding* horizontal responsif yang menghormati `env(safe-area-inset-left/right)` (minimal `16px`). Seluruh jarak antar elemen mengikuti ritme kelipatan `8px` (`8px`, `12px`, `16px`, `24px`, `32px`, `40px`).

- **Katalog Film:** Menggunakan CSS Grid adaptif (`repeat(auto-fill, minmax(210px, 1fr))`) dengan jarak `24px` dan rasio poster tetap `2:3`.
- **Halaman Pemesanan & Operator:** Menggunakan grid dua kolom asimetris (`1fr 340px`) yang menempatkan denah kursi atau form validasi di kiri dan ringkasan tagihan di kanan.
- **Responsivitas Mobile (`≤768px` hingga `360px`):** Grid dua kolom (*Hero*, *Detail Film*, *Pilih Kursi*, *Profil*, dan *E-Ticket Pass*) otomatis melipat menjadi satu kolom vertikal (`1fr`), dengan garis perforasi tiket berubah dari vertikal menjadi horizontal.

## Elevation & Depth

CineVerse menganut filosofi **Flat-by-Default & Hairline-Framed**. Permukaan kartu tidak mengandalkan bayangan kabur yang bertumpuk, melainkan dipisahkan secara bersih dari latar halaman menggunakan kontras warna permukaan (`#FFFFFF` di atas `#F7F7F5` pada mode terang; `#171E30` di atas `#0E1320` pada mode gelap) serta *border* tipis `1px solid var(--border)`.

### Named Rules
**The Single Elevation Declaration Rule.** Setiap kontainer hanya boleh mendeklarasikan batasnya satu kali (menggunakan *border* `1px` saja tanpa `box-shadow` tambahan) untuk mencegah efek *ghost card*.

**The Compositor-Only Motion Rule.** Kedalaman interaktif pada kartu film (`translateY(-6px)` dan pembesaran poster `scale(1.05)` selama `200ms ease-out`) serta tombol/kursi (`150ms ease-out`) hanya boleh menganimasikan `transform` dan `opacity`, serta wajib dimatikan saat `@media (prefers-reduced-motion: reduce)` aktif.

## Shapes

Bahasa bentuk menggunakan sudut membulat yang hangat namun tetap terstruktur:
- **Kartu & Panel Utama:** Sudut membulat `12px` (`rounded.xl`) dengan *border* `1px`.
- **Tombol & Input Form:** Sudut membulat `10px` (`rounded.lg`).
- **Tombol Kursi & Navigasi Submenu:** Sudut membulat `8px` (`rounded.md`).
- **Badge Status & Rating Usia:** Sudut membulat `6px` (`rounded.sm`).
- **E-Ticket Admission Pass:** Sudut luar `16px` (`rounded.pass`) yang dipadukan dengan garis sobekan perforasi (`2px dashed`) dan lekukan setengah lingkaran (*punch-out notch* `24px`) di bagian atas dan bawah.

## Components

### Buttons
- **Shape:** Sudut membulat sedang (`10px` pada ukuran standar, `8px` pada `.btn-sm`).
- **Primary:** Latar Projector Amber (`#FCA311`) dengan teks Cinematic Navy (`#14213D`), *padding* `10px 18px`, font `Plus Jakarta Sans` `600`.
- **Hover / Focus:** Berubah ke Projector Amber Deep (`#E59009`) saat `:hover`, mengecil halus `scale(0.97)` saat `:active` (`150ms ease-out`), dan menampilkan cincin fokus `2px solid var(--accent)` dengan `outline-offset: 2px` saat `:focus-visible`.
- **Secondary & Outline:** Varian sekunder memakai latar Cinematic Navy (`#14213D`) dengan teks putih; varian *outline* memakai latar transparan dengan *border* `1px solid var(--border)`.

### Cards / Containers
- **Corner Style:** Sudut membulat `12px` (`border-radius: 12px`).
- **Background:** Card Surface (`#FFFFFF` pada mode terang, `#171E30` pada mode gelap).
- **Shadow Strategy:** Datar tanpa bayangan (*flat-by-default*).
- **Border:** Garis tipis `1px solid var(--border)`.
- **Internal Padding:** `24px` untuk kartu konten standar, `16px` untuk badan kartu film.

### Inputs / Fields
- **Style:** Latar `var(--surface)`, *border* `1px solid var(--border)`, sudut membulat `10px`, *padding* `10px 14px`, font `Inter` `15px`.
- **Focus:** Mengubah warna *border* menjadi Projector Amber (`#FCA311`) disertai cincin `:focus-visible` `2px solid var(--accent)`.

### Navigation
- **Style:** Navbar *sticky* (`z-index: var(--z-sticky)`) dengan latar Cinematic Navy (`#14213D`), tinggi minimum `64px`, tautan menu dengan sudut `8px` yang menyala (`rgba(255,255,255,0.1)`) saat aktif/di-hover, serta *user pill* berbentuk kapsul (`999px`) yang menampilkan foto profil bundar `28px` dan badge peran.

### Interactive Seat Map & E-Ticket Pass (Signature Components)
- **Interactive Seat Button (`.seat-btn`):** Tombol kursi berukuran tetap `42px × 38px` (`DM Mono` `0.8rem`) yang membesar `scale(1.08)` saat di-hover, berubah menjadi Projector Amber (`#FCA311`) saat dipilih, dan terkunci dalam warna Velvet Crimson (`#D64545`) saat sudah terisi.
- **Cinema Admission Pass (`.ticket-pass`):** Komponen tiket tiga kolom (`1fr 2px 240px`) dengan pita kepala Navy, poster film `2:3`, grid telemetri 4 sel monospasi, garis sobekan perforasi berlekuk, dan *Gate Scan Stub* berisi QR Code.

## Do's and Don'ts

### Do:
- **Do** gunakan `DM Mono` dengan `font-variant-numeric: tabular-nums` untuk seluruh nomor kursi, kode booking, jam tayang, dan nominal harga.
- **Do** batasi penggunaan warna Projector Amber (`#FCA311`) hanya untuk CTA utama, kursi yang dipilih, dan indikator penting agar sinyal visual tetap tajam.
- **Do** pertahankan rasio poster `2:3` (`aspect-ratio: 2 / 3`) dan *single-line truncation* pada judul/genre kartu film agar tinggi baris katalog selalu rata.
- **Do** sertakan *structural skeleton* saat memuat pencarian asinkron dan berikan tombol aksi pemulihan pada *empty state*.

### Don't:
- **Don't** menambahkan gradien warna-warni, teks bergradien, atau bayangan blok keras (*neobrutalist hard shadows*).
- **Don't** menggabungkan `border: 1px` dengan `box-shadow` lebar pada kartu yang sama (*The Single Elevation Declaration Rule*).
- **Don't** menganimasikan properti tata letak (`width`, `height`, `top`, `left`, `margin`, `padding`); gunakan hanya `transform` dan `opacity`.
- **Don't** menghapus indikator fokus keyboard (`:focus-visible`) pada tombol, kursi, maupun input form.
