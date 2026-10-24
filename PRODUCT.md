# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

PHP 8.x native (no framework), MySQL / MariaDB via PDO, vanilla HTML5 / CSS3 (CSS Variables) / JavaScript (Fetch API), running locally on XAMPP (`http://localhost/BIOSKOP`). CDN libraries: Chart.js, qrcode.js, html5-qrcode.

## Users

1. **Penonton (Guest & Registered User):** Browse current and upcoming movies, watch trailers, inspect showtimes by date, pick up to 6 seats interactively on a studio seat map, complete simulated checkout within a 10-minute hold window, and present a printable/scannable QR e-ticket at the theater gate.
2. **Petugas Bioskop (Operator):** Validate customer e-tickets at the studio entrance via booking code input or camera QR scan, manage daily movie showtimes, and sell walk-in counter tickets for cash buyers without accounts.
3. **Pengelola Sistem (Admin):** Manage master data (movies with local poster uploads, studios with auto-generated seat grids, pricing, promos, user/operator accounts) and monitor revenue and ticket sales via charts and CSV reports.

## Product Purpose

CineVerse is a complete, realistic local cinema ticketing and management web application built to master full-stack PHP + MySQL fundamentals (role-based authentication, relational schema design, concurrency-safe seat booking transactions, QR e-ticketing, and operational dashboards) inside a cohesive product experience.

## Positioning

A lightweight, zero-build-tool cinema web app that combines a poster-first consumer ticketing experience with built-in box-office counter sales, live QR gate validation, and back-office studio/showtime management in one unified local codebase.

## Operating Context

- Runs locally on XAMPP/Laragon (`PHP 8.1+` and `MySQL 8 / MariaDB`), initialized from a single `database.sql` seed file.
- Responsive across mobile (`360px` width for e-ticket display and browsing) through desktop (`1200px` max grid for seat maps, operator panels, and admin dashboards).
- Supports both Light Mode and Dark Mode persisted in `localStorage`.

## Capabilities and Constraints

- **Concurrency-Safe Seat Locking:** `booking_seats` enforces `UNIQUE (showtime_id, seat_id)` at the database level so two users can never book the same seat for the same showtime.
- **10-Minute Payment Hold:** Pending bookings expire automatically after 10 minutes (`expires_at`), releasing held seats back to the pool.
- **Single-Use QR Validation:** Operator check-in records `checked_in_at` and `checked_in_by`; scanning a used ticket a second time is rejected.
- **Simulated Payments:** Checkout simulates QRIS, bank transfer, and e-wallet confirmation without external payment gateways.
- **Security Scope:** Uses `password_hash()` and PDO prepared statements throughout; omits external email/SMS gateways and production rate-limiting by design (`PRD Website Bioskop (PHP + MySQL).md`).

## Brand Commitments

- **Product Name:** CineVerse
- **Color Palette:** Cinematic Navy (`#14213D`) paired with Projector Amber (`#FCA311`) reserved strictly for primary interactive actions, selected seats, and rating stars.
  - Light surfaces: `#F7F7F5` page background, `#FFFFFF` cards, `#1F2937` primary text, `#6B7280` secondary text, `#E5E7EB` hairline borders.
  - Dark surfaces: `#0E1320` page background, `#171E30` cards, `#E8EAF0` primary text, `#9AA3B5` secondary text, `#2A3350` hairline borders.
  - Semantic states: `#2E9E6B` / `#3DB77F` (Success), `#D64545` / `#E5605F` (Danger / Occupied Seat / Invalid Ticket).
- **Typography:** `Plus Jakarta Sans` for headings and navigation, `Inter` for body and form controls, `DM Mono` for booking codes and seat coordinates (`A1`, `B4`).

## Evidence on Hand

- Full product specification and acceptance criteria in [`PRD Website Bioskop (PHP + MySQL).md`](./PRD%20Website%20Bioskop%20(PHP%20+%20MySQL).md).

## Product Principles

1. **Poster-First Clarity:** Movie posters and showtime availability lead the visual hierarchy; chrome stays quiet with generous whitespace and an 8px spacing rhythm.
2. **State Truthfulness in Seat & Ticket Flows:** Seat states (available, selected, occupied), 10-minute hold timers, and ticket validation statuses must always reflect real database truth and communicate clearly through color, label, and monospace identifiers.
3. **Role Isolation with Shared Foundations:** Guest/User, Operator, and Admin workflows live in cleanly separated directories (`public/`, `user/`, `operator/`, `admin/`) backed by shared PDO and auth helpers in `includes/`.
4. **Purposeful, Accessible Motion:** Animations use only `transform` and `opacity` (card reveal, subtle hover lift, seat selection pulse) and respect `prefers-reduced-motion`.
