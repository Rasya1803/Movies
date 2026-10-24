<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$user = currentUser();

$bookingId = (int)($_GET['id'] ?? $_POST['booking_id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT b.*, s.start_time, s.price AS seat_price, m.title AS movie_title,
           st.name AS studio_name, p.code AS promo_code
    FROM bookings b
    JOIN showtimes s ON s.id = b.showtime_id
    JOIN movies m ON m.id = s.movie_id
    JOIN studios st ON st.id = s.studio_id
    LEFT JOIN promos p ON p.id = b.promo_id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$bookingId, $user['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    setFlash('danger', 'Pesanan tidak ditemukan.');
    header('Location: history.php');
    exit;
}

if ($booking['status'] === 'paid') {
    header('Location: ticket.php?id=' . $bookingId);
    exit;
}

if ($booking['status'] !== 'pending') {
    setFlash('danger', 'Pesanan ini sudah ' . $booking['status'] . ' (melewati batas waktu 10 menit atau dibatalkan).');
    header('Location: history.php');
    exit;
}

// Ambil daftar kursi pesanan ini
$seatStmt = $pdo->prepare("
    SELECT CONCAT(s.row_label, s.seat_number) AS seat_label
    FROM booking_seats bs
    JOIN seats s ON s.id = bs.seat_id
    WHERE bs.booking_id = ?
    ORDER BY s.row_label ASC, s.seat_number ASC
");
$seatStmt->execute([$bookingId]);
$seatLabels = $seatStmt->fetchAll(PDO::FETCH_COLUMN);
$baseSubtotal = count($seatLabels) * (int)$booking['seat_price'];

// Handle aksi Promo, Bayar, atau Batal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'apply_promo') {
        $code = strtoupper(trim($_POST['promo_code'] ?? ''));
        $pStmt = $pdo->prepare("
            SELECT * FROM promos
            WHERE code = ? AND is_active = 1 AND quota > 0
              AND CURDATE() BETWEEN valid_from AND valid_until
            LIMIT 1
        ");
        $pStmt->execute([$code]);
        $promo = $pStmt->fetch();

        if (!$promo) {
            setFlash('danger', 'Kode promo tidak valid atau sudah habis.');
        } else {
            $discount = $promo['type'] === 'percent'
                ? (int)round($baseSubtotal * ((int)$promo['value'] / 100))
                : (int)$promo['value'];
            $discount = min($discount, $baseSubtotal);
            $newTotal = $baseSubtotal - $discount;

            $upd = $pdo->prepare("UPDATE bookings SET promo_id = ?, discount_amount = ?, total_price = ? WHERE id = ?");
            $upd->execute([$promo['id'], $discount, $newTotal, $bookingId]);
            setFlash('success', 'Kode promo ' . $code . ' berhasil dipasang! Hemat ' . rupiah($discount));
        }
        header('Location: checkout.php?id=' . $bookingId);
        exit;
    }

    if ($action === 'pay') {
        $method = $_POST['method'] ?? 'QRIS';
        $allowedMethods = ['QRIS', 'Transfer Bank', 'E-Wallet'];
        if (!in_array($method, $allowedMethods, true)) {
            $method = 'QRIS';
        }

        $pdo->beginTransaction();
        $upd = $pdo->prepare("UPDATE bookings SET status = 'paid' WHERE id = ? AND status = 'pending'");
        $upd->execute([$bookingId]);

        $insPay = $pdo->prepare("INSERT INTO payments (booking_id, method, amount, paid_at) VALUES (?, ?, ?, NOW())");
        $insPay->execute([$bookingId, $method, $booking['total_price']]);

        if ($booking['promo_id']) {
            $decPromo = $pdo->prepare("UPDATE promos SET quota = GREATEST(0, quota - 1) WHERE id = ?");
            $decPromo->execute([$booking['promo_id']]);
        }

        // Tambah poin loyalitas (1 poin tiap Rp10.000)
        $earnedPoints = (int)floor((int)$booking['total_price'] / 10000);
        if ($earnedPoints > 0) {
            $ptStmt = $pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?");
            $ptStmt->execute([$earnedPoints, $user['id']]);
        }

        $pdo->commit();
        setFlash('success', 'Pembayaran berhasil! Ini adalah E-Ticket Anda.');
        header('Location: ticket.php?id=' . $bookingId);
        exit;
    }

    if ($action === 'cancel') {
        $pdo->prepare("DELETE FROM booking_seats WHERE booking_id = ?")->execute([$bookingId]);
        $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$bookingId]);
        setFlash('info', 'Pesanan telah dibatalkan dan kursi kembali tersedia.');
        header('Location: history.php');
        exit;
    }
}

$remainingSeconds = max(0, strtotime($booking['expires_at']) - time());

$pageTitle = 'Checkout Pembayaran';
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="section-header">
    <h1>Checkout Pembayaran</h1>
    <div class="badge badge-warning" style="font-size:0.95rem;padding:8px 14px;">
        Sisa Waktu Bayar: &nbsp;<span id="countdownTimer" class="mono" data-seconds="<?= (int)$remainingSeconds ?>">10:00</span>
    </div>
</div>

<div class="booking-layout">
    <div class="card">
        <h3 style="margin-bottom:16px;">Simulasi Pembayaran</h3>

        <form method="POST" style="margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border);">
            <input type="hidden" name="action" value="apply_promo">
            <label for="promo_code" style="display:block;font-size:14px;font-weight:500;margin-bottom:6px;">Punya Kode Promo? (Contoh: <code>HEMAT20</code> atau <code>NONTON10K</code>)</label>
            <div style="display:flex;gap:10px;">
                <input type="text" id="promo_code" name="promo_code" value="<?= e($booking['promo_code'] ?? '') ?>" placeholder="Masukkan kode promo">
                <button type="submit" class="btn btn-outline">Pakai</button>
            </div>
        </form>

        <form method="POST">
            <input type="hidden" name="action" value="pay">
            <div class="form-group">
                <label for="method">Pilih Metode Pembayaran (Simulasi)</label>
                <select name="method" id="method" required>
                    <option value="QRIS">QRIS (Langsung Lunas)</option>
                    <option value="Transfer Bank">Virtual Account / Transfer Bank</option>
                    <option value="E-Wallet">E-Wallet (GoPay / OVO / Dana)</option>
                </select>
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:20px;">
                <button type="submit" class="btn btn-primary">Bayar Sekarang (<?= rupiah($booking['total_price']) ?>)</button>
            </div>
        </form>

        <form method="POST" style="margin-top:12px;" onsubmit="return confirm('Yakin ingin membatalkan pesanan ini?');">
            <input type="hidden" name="action" value="cancel">
            <button type="submit" class="btn btn-outline btn-sm text-danger">Batalkan Pesanan</button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-bottom:14px;">Detail Tagihan</h3>
        <div style="display:flex;flex-direction:column;gap:10px;font-size:0.92rem;">
            <div>
                <span class="text-muted">Kode Booking:</span><br>
                <strong class="mono"><?= e($booking['booking_code']) ?></strong>
            </div>
            <div>
                <span class="text-muted">Film:</span><br>
                <strong><?= e($booking['movie_title']) ?></strong>
            </div>
            <div>
                <span class="text-muted">Studio &amp; Jadwal:</span><br>
                <span><?= e($booking['studio_name']) ?> • <?= date('d M Y, H:i', strtotime($booking['start_time'])) ?> WIB</span>
            </div>
            <div>
                <span class="text-muted">Kursi (<?= count($seatLabels) ?>):</span><br>
                <strong class="mono text-accent"><?= e(implode(', ', $seatLabels)) ?></strong>
            </div>
            <div style="padding-top:10px;border-top:1px solid var(--border);display:flex;justify-content:space-between;">
                <span>Subtotal:</span>
                <span class="mono"><?= rupiah($baseSubtotal) ?></span>
            </div>
            <?php if ((int)$booking['discount_amount'] > 0): ?>
                <div style="display:flex;justify-content:space-between;" class="text-success">
                    <span>Diskon Promo (<?= e($booking['promo_code']) ?>):</span>
                    <span class="mono">- <?= rupiah($booking['discount_amount']) ?></span>
                </div>
            <?php endif; ?>
            <div style="padding-top:10px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                <strong>Total Bayar:</strong>
                <strong class="mono text-accent" style="font-size:1.25rem;"><?= rupiah($booking['total_price']) ?></strong>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var timerEl = document.getElementById('countdownTimer');
    if (!timerEl) return;
    var remaining = parseInt(timerEl.getAttribute('data-seconds') || '0', 10);

    function renderTimer() {
        var mins = Math.floor(remaining / 60);
        var secs = remaining % 60;
        timerEl.textContent = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        if (remaining <= 0) {
            window.location.reload();
        } else {
            remaining--;
        }
    }

    renderTimer();
    setInterval(renderTimer, 1000);
});
</script>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
