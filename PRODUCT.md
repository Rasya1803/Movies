# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

PHP 8.x native (no framework), MySQL / MariaDB via PDO, pure HTML5 and vanilla CSS3 (CSS Variables, **Zero JavaScript**), running locally on XAMPP (`http://localhost/BIOSKOP`).

## Users

1. **Penonton (Guest & Registered User):** Browse current and upcoming movies, inspect showtimes by date, select up to 6 seats using a CSS-styled checkbox seat map, complete simulated checkout within a 10-minute hold window, and view/print a digital e-ticket with a unique monospace booking code.
2. **Petugas Bioskop (Operator):** Validate customer e-tickets at the studio entrance via booking code form submission, manage daily movie showtimes, and sell walk-in counter tickets for cash buyers without accounts.
3. **Pengelola Sistem (Admin):** Manage master data (movies with local poster uploads, studios with auto-generated seat grids, pricing, promos, user/operator accounts) and monitor revenue and ticket sales via pure CSS bar charts and CSV reports.

## Product Purpose

CineVerse is a complete, realistic local cinema ticketing and management web application built to master full-stack PHP + MySQL + CSS fundamentals (role-based authentication, relational schema design, concurrency-safe seat booking transactions, e-ticketing, and operational dashboards) without relying on any client-side JavaScript.

## Positioning

A zero-JavaScript, zero-build-tool cinema web app that combines a poster-first consumer ticketing experience with built-in box-office counter sales, gate ticket validation, and back-office studio/showtime management using pure server-rendered PHP and modern CSS.

## Operating Context

- Runs locally on XAMPP/Laragon (`PHP 8.1+` and `MySQL 8 / MariaDB`), initialized from a single `database.sql` seed file.
- Responsive across mobile (`360px` width for e-ticket display and browsing) through desktop (`1200px` max grid for seat maps, operator panels, and admin dashboards).
- Supports both Light Mode and Dark Mode persisted via PHP session/cookie and CSS Variables.

## Capabilities and Constraints

- **Zero JavaScript Constraint:** Every interaction—seat selection (`<input type="checkbox">` + `:checked` CSS), movie search/filtering (`GET` forms), dark mode toggling (PHP session/cookie), e-ticket printing (`@media print`), and admin revenue charts (CSS flex/grid bar charts)—must work with pure PHP, HTML5, and CSS3.
- **Plaintext Passwords (Learning Mode):** User passwords in `users.password` are stored and verified as plaintext (no `password_hash()`) for easy inspection in phpMyAdmin, while all SQL queries strictly use PDO prepared statements.
- **Concurrency-Safe Seat Locking:** `booking_seats` enforces `UNIQUE (showtime_id, seat_id)` at the database level so two users can never book the same seat for the same showtime.
- **10-Minute Payment Hold:** Pending bookings expire automatically after 10 minutes (`expires_at`), checked server-side on page load to release held seats back to the pool.
- **Single-Use Ticket Validation:** Operator check-in records `checked_in_at` and `checked_in_by`; validating a used booking code a second time is rejected.

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
2. **Pure CSS Interactivity:** Leverage semantic HTML forms (`checkbox`, `radio`, `details/summary`) and modern CSS (`:checked`, `:focus-visible`, `@keyframes`, `@media print`) to deliver a tactile, responsive experience without a single line of JavaScript.
3. **State Truthfulness in Seat & Ticket Flows:** Seat states (available, selected, occupied), 10-minute hold expiration, and ticket validation statuses must always reflect real database truth and communicate clearly through color, label, and monospace identifiers.
4. **Purposeful, Accessible Motion:** CSS animations use only `transform` and `opacity` and respect `@media (prefers-reduced-motion: reduce)`.
