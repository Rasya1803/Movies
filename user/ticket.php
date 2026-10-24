<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$user = currentUser();

$bookingId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT b.*, s.start_time, m.title AS movie_title, m.age_rating, m.duration_min,
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

<div class="ticket-card">
    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <span class="badge badge-paid">STATUS: <?= strtoupper(e($booking['status'])) ?></span>
            <?php if ($booking['checked_in_at']): ?>
                <span class="badge badge-danger">SUDAH CHECK-IN (<?= date('H:i', strtotime($booking['checked_in_at'])) ?>)</span>
            <?php else: ?>
                <span class="badge badge-info">BELUM CHECK-IN</span>
            <?php endif; ?>
        </div>

        <h2 style="margin-bottom:4px;"><?= e($booking['movie_title']) ?></h2>
        <p class="text-muted" style="margin-bottom:16px;">
            <?= e($booking['studio_name']) ?> (<?= strtoupper(e($booking['studio_type'])) ?>) • <?= e($booking['age_rating']) ?>
        </p>

        <div class="form-row" style="gap:12px;margin-bottom:16px;">
            <div>
                <span class="text-muted">Tanggal &amp; Jam Tayang</span><br>
                <strong class="mono"><?= date('d M Y • H:i', strtotime($booking['start_time'])) ?> WIB</strong>
            </div>
            <div>
                <span class="text-muted">Nomor Kursi</span><br>
                <strong class="mono text-accent" style="font-size:1.15rem;"><?= e(implode(', ', $seatLabels)) ?></strong>
            </div>
        </div>

        <div class="form-row" style="gap:12px;padding-top:12px;border-top:1px solid var(--border);">
            <div>
                <span class="text-muted">Kode Booking</span><br>
                <strong class="mono booking-code" style="font-size:1.2rem;"><?= e($booking['booking_code']) ?></strong>
            </div>
            <div>
                <span class="text-muted">Pembeli / Metode</span><br>
                <strong><?= e($booking['customer_name'] ?: 'Walk-in Loket') ?> (<?= e($booking['payment_method'] ?: '-') ?>)</strong>
            </div>
        </div>
    </div>

    <div class="qr-box">
        <div id="qrcode" data-code="<?= e($booking['booking_code']) ?>"></div>
        <span class="mono" style="color:#14213D;font-size:0.8rem;margin-top:8px;font-weight:700;">
            <?= e($booking['booking_code']) ?>
        </span>
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
