<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$user = currentUser();

$bookingId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT b.*, s.start_time, m.title AS movie_title, m.poster, m.age_rating, m.duration_min, m.language,
           st.name AS studio_name, st.type AS studio_type, p.method AS payment_method
    FROM bookings b
    JOIN showtimes s ON s.id = b.showtime_id
    JOIN movies m ON m.id = s.movie_id
    JOIN studios st ON st.id = s.studio_id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.id = ?
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    setFlash('danger', 'Tiket tidak ditemukan.');
    header('Location: history.php');
    exit;
}

// Hanya pemilik tiket, operator, atau admin yang boleh melihat e-ticket
if ($user['role'] === 'user' && (int)$booking['user_id'] !== (int)$user['id']) {
    setFlash('danger', 'Anda tidak berhak mengakses tiket ini.');
    header('Location: history.php');
    exit;
}

$seatStmt = $pdo->prepare("
    SELECT CONCAT(s.row_label, s.seat_number) AS seat_label
    FROM booking_seats bs
    JOIN seats s ON s.id = bs.seat_id
    WHERE bs.booking_id = ?
    ORDER BY s.row_label ASC, s.seat_number ASC
");
$seatStmt->execute([$bookingId]);
$seatLabels = $seatStmt->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'E-Ticket ' . $booking['booking_code'];
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="section-header no-print">
    <h1>E-Ticket Digital</h1>
    <div style="display:flex;gap:10px;">
        <button type="button" onclick="window.print()" class="btn btn-primary btn-sm">Cetak Tiket (Print)</button>
        <?php if ($user['role'] === 'user'): ?>
            <a href="history.php" class="btn btn-outline btn-sm">Ke Riwayat Pesanan</a>
        <?php else: ?>
            <a href="<?= baseUrl('operator/pos.php') ?>" class="btn btn-outline btn-sm">Kembali ke Loket</a>
        <?php endif; ?>
    </div>
</div>

<div class="ticket-pass">
    <!-- Bagian Utama Tiket -->
    <div class="ticket-main">
        <div class="ticket-banner">
            <div class="ticket-brand">
                <span class="brand-mark">CV</span>
                <div>
                    <strong style="color:#FFFFFF;font-size:0.95rem;display:block;line-height:1.1;">CINEVERSE ADMISSION PASS</strong>
                    <span class="mono" style="color:rgba(255,255,255,0.7);font-size:0.75rem;">
                        <?= e(strtoupper($booking['studio_name'])) ?> • <?= e(strtoupper($booking['studio_type'])) ?> CLASS
                    </span>
                </div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <span class="badge badge-paid"><?= strtoupper(e($booking['status'])) ?></span>
                <?php if ($booking['checked_in_at']): ?>
                    <span class="badge badge-danger">SUDAH CHECK-IN • <?= date('H:i', strtotime($booking['checked_in_at'])) ?></span>
                <?php else: ?>
                    <span class="badge badge-warning">SIAP PINDAI PINTU</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="ticket-body">
            <div class="ticket-film">
                <img src="<?= e(posterUrl($booking['poster'])) ?>" alt="<?= e($booking['movie_title']) ?>" class="ticket-poster">
                <div>
                    <div style="display:flex;gap:6px;align-items:center;margin-bottom:8px;flex-wrap:wrap;">
                        <span class="badge badge-info"><?= e($booking['age_rating']) ?></span>
                        <span class="mono text-muted" style="font-size:0.8rem;"><?= (int)$booking['duration_min'] ?> MENIT • <?= e(strtoupper($booking['language'])) ?></span>
                    </div>
                    <h2 class="ticket-movie-title"><?= e($booking['movie_title']) ?></h2>
                    <p class="text-muted" style="font-size:0.88rem;margin-top:4px;">
                        Tunjukkan QR Code di potongan tiket sebelah kanan kepada petugas pintu studio.
                    </p>
                </div>
            </div>

            <div class="ticket-telemetry">
                <div class="telemetry-cell">
                    <span class="telemetry-label">TANGGAL TAYANG</span>
                    <strong class="mono telemetry-value"><?= date('d M Y', strtotime($booking['start_time'])) ?></strong>
                </div>
                <div class="telemetry-cell">
                    <span class="telemetry-label">JAM MULAI</span>
                    <strong class="mono telemetry-value"><?= date('H:i', strtotime($booking['start_time'])) ?> WIB</strong>
                </div>
                <div class="telemetry-cell">
                    <span class="telemetry-label">STUDIO</span>
                    <strong class="mono telemetry-value"><?= e($booking['studio_name']) ?></strong>
                </div>
                <div class="telemetry-cell telemetry-highlight">
                    <span class="telemetry-label">NOMOR KURSI (<?= count($seatLabels) ?>)</span>
                    <strong class="mono telemetry-seat"><?= e(implode(', ', $seatLabels)) ?></strong>
                </div>
            </div>

            <div class="ticket-meta-bar">
                <div>
                    <span class="text-muted">Nama Penonton:</span>
                    <strong><?= e($booking['customer_name'] ?: 'Walk-in Loket') ?></strong>
                </div>
                <div>
                    <span class="text-muted">Metode Bayar:</span>
                    <strong><?= e($booking['payment_method'] ?: '-') ?></strong>
                </div>
                <div>
                    <span class="text-muted">Total Lunas:</span>
                    <strong class="mono"><?= rupiah($booking['total_price']) ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Garis Sobekan Perforasi -->
    <div class="ticket-perforation" aria-hidden="true"></div>

    <!-- Potongan Sobekan Pintu (Gate Stub) -->
    <div class="ticket-stub">
        <span class="mono ticket-stub-header">GATE SCAN STUB</span>
        <div class="qr-box">
            <div id="qrcode" data-code="<?= e($booking['booking_code']) ?>"></div>
        </div>
        <div class="ticket-code-pill mono">
            <?= e($booking['booking_code']) ?>
        </div>
        <div class="mono text-muted" style="font-size:0.78rem;text-align:center;">
            KURSI: <strong style="color:var(--text);"><?= e(implode(', ', $seatLabels)) ?></strong>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var qrEl = document.getElementById('qrcode');
    if (qrEl && typeof QRCode !== 'undefined') {
        new QRCode(qrEl, {
            text: qrEl.getAttribute('data-code'),
            width: 140,
            height: 140,
            colorDark: '#14213D',
            colorLight: '#FFFFFF'
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
