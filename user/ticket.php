<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$user = currentUser();

$bookingId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT b.*, s.start_time, m.title AS movie_title, m.poster, m.age_rating, m.duration_min, m.language,
           st.name AS studio_name, st.type AS studio_type,
           p.method AS payment_method, p.paid_at,
           pr.code AS promo_code,
           COALESCE(NULLIF(b.customer_name, ''), u.name, 'Walk-in Loket') AS viewer_name
    FROM bookings b
    JOIN showtimes s ON s.id = b.showtime_id
    JOIN movies m ON m.id = s.movie_id
    JOIN studios st ON st.id = s.studio_id
    LEFT JOIN users u ON u.id = b.user_id
    LEFT JOIN payments p ON p.booking_id = b.id
    LEFT JOIN promos pr ON pr.id = b.promo_id
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
$seatListText = $seatLabels ? implode(', ', $seatLabels) : '-';

$statusMap = [
    'paid'      => ['label' => 'LUNAS', 'class' => 'badge-paid'],
    'pending'   => ['label' => 'MENUNGGU BAYAR', 'class' => 'badge-warning'],
    'cancelled' => ['label' => 'DIBATALKAN', 'class' => 'badge-danger'],
    'expired'   => ['label' => 'KEDALUWARSA', 'class' => 'badge-danger'],
];
$statusMeta = $statusMap[$booking['status']] ?? ['label' => strtoupper($booking['status']), 'class' => 'badge-info'];

$pageTitle = 'E-Ticket ' . $booking['booking_code'];
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="section-header no-print">
    <div>
        <h1>E-Ticket Digital</h1>
        <p class="text-muted">Kode Booking <span class="mono"><?= e($booking['booking_code']) ?></span> • <?= e($booking['movie_title']) ?></p>
    </div>
    <div class="ticket-header-actions">
        <?php if ($booking['status'] === 'pending' && $user['role'] === 'user'): ?>
            <a href="checkout.php?id=<?= (int)$booking['id'] ?>" class="btn btn-primary btn-sm">Lanjutkan Pembayaran</a>
        <?php endif; ?>
        <button type="button" onclick="window.print()" class="btn btn-primary btn-sm">Cetak Tiket (Print)</button>
        <?php if ($user['role'] === 'user'): ?>
            <a href="history.php" class="btn btn-outline btn-sm">Ke Riwayat Pesanan</a>
        <?php else: ?>
            <a href="<?= baseUrl('operator/pos.php') ?>" class="btn btn-outline btn-sm">Kembali ke Loket</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($booking['status'] !== 'paid'): ?>
<div class="alert alert-danger no-print">
    Tiket ini berstatus <strong><?= e($statusMeta['label']) ?></strong> dan belum dapat dipindai di pintu studio.
    <?php if ($booking['status'] === 'pending'): ?>
        Silakan selesaikan pembayaran sebelum batas waktu berakhir.
    <?php endif; ?>
</div>
<?php endif; ?>

<article class="ticket-pass<?= $booking['status'] !== 'paid' ? ' ticket-pass-inactive' : '' ?>" aria-label="Tiket Masuk Bioskop <?= e($booking['movie_title']) ?>">
    <!-- Bagian Utama Tiket -->
    <div class="ticket-main">
        <div class="ticket-banner">
            <div class="ticket-brand">
                <span class="brand-mark">CV</span>
                <div>
                    <strong class="ticket-brand-title">CINEVERSE ADMISSION PASS</strong>
                    <span class="mono ticket-brand-sub">
                        <?= e(strtoupper($booking['studio_name'])) ?> • <?= e(strtoupper($booking['studio_type'])) ?> CLASS
                    </span>
                </div>
            </div>
            <div class="ticket-badges">
                <span class="badge <?= e($statusMeta['class']) ?>"><?= e($statusMeta['label']) ?></span>
                <?php if ($booking['checked_in_at']): ?>
                    <span class="badge badge-danger">SUDAH CHECK-IN • <?= date('H:i', strtotime($booking['checked_in_at'])) ?></span>
                <?php elseif ($booking['status'] === 'paid'): ?>
                    <span class="badge badge-warning">SIAP PINDAI PINTU</span>
                <?php else: ?>
                    <span class="badge badge-danger">BELUM AKTIF</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="ticket-body">
            <div class="ticket-film">
                <img src="<?= e(posterUrl($booking['poster'])) ?>" alt="Poster <?= e($booking['movie_title']) ?>" class="ticket-poster">
                <div>
                    <div class="ticket-film-tags">
                        <span class="badge badge-info"><?= e($booking['age_rating']) ?></span>
                        <span class="mono text-muted"><?= (int)$booking['duration_min'] ?> MENIT • <?= e(strtoupper($booking['language'])) ?></span>
                    </div>
                    <h2 class="ticket-movie-title"><?= e($booking['movie_title']) ?></h2>
                    <p class="text-muted ticket-film-note">
                        Tunjukkan QR Code pada potongan tiket kepada petugas pintu studio sebelum masuk.
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
                    <strong class="mono telemetry-seat"><?= e($seatListText) ?></strong>
                </div>
            </div>

            <div class="ticket-meta-bar">
                <div>
                    <span class="text-muted">Nama Penonton:</span>
                    <strong><?= e($booking['viewer_name']) ?></strong>
                </div>
                <div>
                    <span class="text-muted">Metode Bayar:</span>
                    <strong><?= e($booking['payment_method'] ?: 'Belum Dibayar') ?></strong>
                </div>
                <?php if ((int)$booking['discount_amount'] > 0 || (int)$booking['points_used'] > 0): ?>
                <div>
                    <span class="text-muted">Potongan:</span>
                    <strong class="mono text-success">-<?= rupiah((int)$booking['discount_amount'] + (int)$booking['points_used']) ?></strong>
                </div>
                <?php endif; ?>
                <div>
                    <span class="text-muted"><?= $booking['status'] === 'paid' ? 'Total Lunas:' : 'Total Tagihan:' ?></span>
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
            <div id="qrcode" data-code="<?= e($booking['booking_code']) ?>" role="img" aria-label="QR Code Tiket <?= e($booking['booking_code']) ?>">
                <noscript><span class="mono"><?= e($booking['booking_code']) ?></span></noscript>
            </div>
        </div>
        <div class="ticket-code-row">
            <span class="ticket-code-pill mono" id="bookingCodeText"><?= e($booking['booking_code']) ?></span>
            <button type="button" id="copyCodeBtn" class="btn btn-outline btn-sm no-print" data-copy="<?= e($booking['booking_code']) ?>" aria-label="Salin kode booking <?= e($booking['booking_code']) ?>">Salin</button>
        </div>
        <div class="mono text-muted ticket-stub-seat">
            KURSI: <strong class="ticket-stub-seat-val"><?= e($seatListText) ?></strong>
        </div>
    </div>
</article>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var qrEl = document.getElementById('qrcode');
    if (qrEl) {
        var code = qrEl.getAttribute('data-code');
        if (typeof QRCode !== 'undefined') {
            new QRCode(qrEl, {
                text: code,
                width: 140,
                height: 140,
                colorDark: '#14213D',
                colorLight: '#FFFFFF'
            });
        } else {
            qrEl.textContent = code;
            qrEl.className = 'mono qr-fallback';
        }
    }

    var copyBtn = document.getElementById('copyCodeBtn');
    if (copyBtn && navigator.clipboard) {
        copyBtn.addEventListener('click', function () {
            var val = copyBtn.getAttribute('data-copy');
            navigator.clipboard.writeText(val).then(function () {
                var prev = copyBtn.textContent;
                copyBtn.textContent = 'Tersalin';
                setTimeout(function () {
                    copyBtn.textContent = prev;
                }, 1600);
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
