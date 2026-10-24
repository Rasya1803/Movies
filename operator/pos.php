<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['operator', 'admin']);

$allShowtimes = $pdo->query("
    SELECT s.*, m.title AS movie_title, st.name AS studio_name, st.type AS studio_type
    FROM showtimes s
    JOIN movies m ON m.id = s.movie_id
    JOIN studios st ON st.id = s.studio_id
    WHERE s.status = 'scheduled'
    ORDER BY s.start_time ASC
")->fetchAll();

$selectedShowtimeId = (int)($_GET['showtime_id'] ?? $_POST['showtime_id'] ?? ($allShowtimes[0]['id'] ?? 0));

$currentShowtime = null;
foreach ($allShowtimes as $sh) {
    if ((int)$sh['id'] === $selectedShowtimeId) {
        $currentShowtime = $sh;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentShowtime) {
    $customerName = trim($_POST['customer_name'] ?? 'Pembeli Loket');
    $rawSeats = trim($_POST['seat_ids'] ?? '');
    $seatIds = array_values(array_unique(array_filter(array_map('intval', explode(',', $rawSeats)))));

    if (empty($seatIds)) {
        setFlash('danger', 'Pilih minimal 1 kursi untuk pembeli loket.');
    } else {
        try {
            $pdo->beginTransaction();

            $bookingCode = generateBookingCode();
            $totalPrice = count($seatIds) * (int)$currentShowtime['price'];

            $insBooking = $pdo->prepare("
                INSERT INTO bookings (booking_code, user_id, customer_name, showtime_id, total_price, status, expires_at)
                VALUES (?, NULL, ?, ?, ?, 'paid', DATE_ADD(NOW(), INTERVAL 10 MINUTE))
            ");
            $insBooking->execute([$bookingCode, $customerName ?: 'Pembeli Loket', $selectedShowtimeId, $totalPrice]);
            $bookingId = (int)$pdo->lastInsertId();

            $insSeat = $pdo->prepare("INSERT INTO booking_seats (booking_id, showtime_id, seat_id) VALUES (?, ?, ?)");
            foreach ($seatIds as $sid) {
                $insSeat->execute([$bookingId, $selectedShowtimeId, $sid]);
            }

            $insPay = $pdo->prepare("INSERT INTO payments (booking_id, method, amount, paid_at) VALUES (?, 'Tunai Loket', ?, NOW())");
            $insPay->execute([$bookingId, $totalPrice]);

            $pdo->commit();
            setFlash('success', 'Transaksi loket berhasil! Silakan cetak tiket untuk pembeli.');
            header('Location: ' . baseUrl('user/ticket.php?id=' . $bookingId));
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            setFlash('danger', 'Kursi yang dipilih sudah terisi. Silakan pilih kursi lain.');
        }
    }
}

$rows = [];
if ($currentShowtime) {
    $seatStmt = $pdo->prepare("
        SELECT s.*,
               CASE WHEN bs.id IS NOT NULL THEN 1 ELSE 0 END AS is_occupied
        FROM seats s
        LEFT JOIN booking_seats bs ON bs.seat_id = s.id AND bs.showtime_id = ?
        WHERE s.studio_id = ?
        ORDER BY s.row_label ASC, s.seat_number ASC
    ");
    $seatStmt->execute([$selectedShowtimeId, $currentShowtime['studio_id']]);
    foreach ($seatStmt->fetchAll() as $seat) {
        $rows[$seat['row_label']][] = $seat;
    }
}

$pageTitle = 'Penjualan Tiket Loket (POS)';
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="subnav">
    <a href="index.php">Validasi &amp; Scan Tiket</a>
    <a href="showtimes.php">Kelola Jadwal Tayang</a>
    <a href="pos.php" class="active">Penjualan Loket (Walk-in)</a>
</div>

<div class="card" style="margin-bottom:24px;">
    <form method="GET" style="display:flex;gap:12px;align-items:end;flex-wrap:wrap;">
        <div class="form-group" style="flex:1;margin-bottom:0;">
            <label for="showtime_id">Pilih Sesi Jadwal Tayang</label>
            <select name="showtime_id" id="showtime_id" onchange="this.form.submit()">
                <?php foreach ($allShowtimes as $sh): ?>
                    <option value="<?= (int)$sh['id'] ?>" <?= (int)$sh['id'] === $selectedShowtimeId ? 'selected' : '' ?>>
                        <?= e($sh['movie_title']) ?> — <?= e($sh['studio_name']) ?> (<?= date('d M H:i', strtotime($sh['start_time'])) ?> — <?= rupiah($sh['price']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-outline">Tampilkan Kursi</button>
    </form>
</div>

<?php if ($currentShowtime): ?>
<div class="booking-layout" id="seatMapContainer" data-price="<?= (int)$currentShowtime['price'] ?>">
    <div class="card">
        <div class="screen-indicator">LAYAR BIOSKOP — <?= e($currentShowtime['studio_name']) ?></div>
        <div class="seat-grid-wrap">
            <?php foreach ($rows as $rowLabel => $seatsInRow): ?>
                <div class="seat-row">
                    <span class="row-label"><?= e($rowLabel) ?></span>
                    <?php foreach ($seatsInRow as $s): ?>
                        <?php $label = $s['row_label'] . $s['seat_number']; ?>
                        <button
                            type="button"
                            class="seat-btn <?= $s['is_occupied'] ? 'occupied' : '' ?>"
                            data-seat-id="<?= (int)$s['id'] ?>"
                            data-seat-label="<?= e($label) ?>"
                            <?= $s['is_occupied'] ? 'disabled' : '' ?>
                        >
                            <?= e($label) ?>
                        </button>
                    <?php endforeach; ?>
                    <span class="row-label"><?= e($rowLabel) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-bottom:14px;">Transaksi Tunai Loket</h3>
        <form method="POST">
            <input type="hidden" name="showtime_id" value="<?= (int)$selectedShowtimeId ?>">
            <input type="hidden" name="seat_ids" id="selectedSeatIds" value="">

            <div class="form-group">
                <label for="customer_name">Nama Pembeli (Walk-in)</label>
                <input type="text" id="customer_name" name="customer_name" value="Pembeli Loket" required>
            </div>

            <div style="font-size:0.9rem;display:flex;flex-direction:column;gap:8px;margin-bottom:16px;">
                <div>
                    <span class="text-muted">Kursi Dipilih:</span><br>
                    <strong id="selectedSeatLabels" class="mono text-accent">Belum ada kursi dipilih</strong>
                </div>
                <div style="display:flex;justify-content:space-between;padding-top:8px;border-top:1px solid var(--border);">
                    <span>Total Tunai:</span>
                    <strong id="totalPriceDisplay" class="mono" style="font-size:1.2rem;">Rp 0</strong>
                </div>
            </div>

            <button type="submit" id="submitSeatsBtn" class="btn btn-primary" style="width:100%;" disabled>
                Bayar Tunai &amp; Cetak Tiket
            </button>
        </form>
    </div>
</div>
<script src="<?= baseUrl('assets/js/seat-map.js') ?>"></script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
