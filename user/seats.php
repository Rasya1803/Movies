<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$user = currentUser();

$showtimeId = (int)($_GET['showtime_id'] ?? $_POST['showtime_id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT s.*, m.title AS movie_title, m.poster, m.age_rating, m.duration_min,
           st.name AS studio_name, st.type AS studio_type, st.total_rows, st.total_cols
    FROM showtimes s
    JOIN movies m ON m.id = s.movie_id
    JOIN studios st ON st.id = s.studio_id
    WHERE s.id = ? AND s.status = 'scheduled'
");
$stmt->execute([$showtimeId]);
$showtime = $stmt->fetch();

if (!$showtime) {
    setFlash('danger', 'Jadwal tayang tidak ditemukan atau sudah dibatalkan.');
    header('Location: ' . baseUrl('public/index.php'));
    exit;
}

// Proses submit kursi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawSeats = trim($_POST['seat_ids'] ?? '');
    $seatIds = array_values(array_unique(array_filter(array_map('intval', explode(',', $rawSeats)))));

    if (empty($seatIds)) {
        setFlash('danger', 'Silakan pilih minimal 1 kursi.');
    } elseif (count($seatIds) > 6) {
        setFlash('danger', 'Maksimal 6 kursi per pesanan.');
    } else {
        try {
            $pdo->beginTransaction();

            $bookingCode = generateBookingCode();
            $totalPrice = count($seatIds) * (int)$showtime['price'];

            $insBooking = $pdo->prepare("
                INSERT INTO bookings (booking_code, user_id, customer_name, showtime_id, total_price, status, expires_at)
                VALUES (?, ?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 10 MINUTE))
            ");
            $insBooking->execute([
                $bookingCode,
                $user['id'],
                $user['name'],
                $showtimeId,
                $totalPrice
            ]);
            $bookingId = (int)$pdo->lastInsertId();

            // Insert ke booking_seats (UNIQUE showtime_id + seat_id akan menolak jika kursi sudah dipesan user lain)
            $insSeat = $pdo->prepare("INSERT INTO booking_seats (booking_id, showtime_id, seat_id) VALUES (?, ?, ?)");
            foreach ($seatIds as $sid) {
                $insSeat->execute([$bookingId, $showtimeId, $sid]);
            }

            $pdo->commit();
            header('Location: checkout.php?id=' . $bookingId);
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            setFlash('danger', 'Maaf, salah satu kursi yang Anda pilih baru saja dipesan oleh pengguna lain. Silakan pilih kursi lain.');
        }
    }
}

// Ambil seluruh kursi studio ini beserta status apakah sudah dipesan di showtime ini
$seatStmt = $pdo->prepare("
    SELECT s.*,
           CASE WHEN bs.id IS NOT NULL THEN 1 ELSE 0 END AS is_occupied
    FROM seats s
    LEFT JOIN booking_seats bs ON bs.seat_id = s.id AND bs.showtime_id = ?
    WHERE s.studio_id = ?
    ORDER BY s.row_label ASC, s.seat_number ASC
");
$seatStmt->execute([$showtimeId, $showtime['studio_id']]);
$allSeats = $seatStmt->fetchAll();

$rows = [];
foreach ($allSeats as $seat) {
    $rows[$seat['row_label']][] = $seat;
}

$pageTitle = 'Pilih Kursi — ' . $showtime['movie_title'];
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="section-header">
    <div>
        <h1>Pilih Kursi</h1>
        <p class="text-muted">
            <?= e($showtime['movie_title']) ?> • <?= e($showtime['studio_name']) ?> (<?= strtoupper(e($showtime['studio_type'])) ?>) •
            <span class="mono"><?= date('d M Y, H:i', strtotime($showtime['start_time'])) ?> WIB</span>
        </p>
    </div>
    <a href="<?= baseUrl('public/movie.php?id=' . (int)$showtime['movie_id']) ?>" class="btn btn-outline btn-sm">&larr; Ganti Jadwal</a>
</div>

<div class="booking-layout" id="seatMapContainer" data-price="<?= (int)$showtime['price'] ?>">
    <div class="card">
        <div class="screen-indicator">LAYAR BIOSKOP</div>

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

        <div class="seat-legend">
            <span class="legend-item"><span class="legend-box" style="background:var(--surface-alt);"></span> Kosong</span>
            <span class="legend-item"><span class="legend-box" style="background:var(--accent);border-color:var(--accent);"></span> Dipilih</span>
            <span class="legend-item"><span class="legend-box" style="background:var(--danger);border-color:var(--danger);"></span> Terisi</span>
        </div>
    </div>

    <div class="card">
        <h3 style="margin-bottom:14px;">Ringkasan Pesanan</h3>
        <div style="display:flex;flex-direction:column;gap:10px;font-size:0.92rem;margin-bottom:20px;">
            <div>
                <span class="text-muted">Film:</span><br>
                <strong><?= e($showtime['movie_title']) ?></strong>
            </div>
            <div>
                <span class="text-muted">Studio &amp; Jadwal:</span><br>
                <span><?= e($showtime['studio_name']) ?> • <?= date('d M Y H:i', strtotime($showtime['start_time'])) ?></span>
            </div>
            <div>
                <span class="text-muted">Harga per Kursi:</span><br>
                <strong class="mono"><?= rupiah($showtime['price']) ?></strong>
            </div>
            <div>
                <span class="text-muted">Kursi Dipilih (Maks. 6):</span><br>
                <strong id="selectedSeatLabels" class="mono text-accent">Belum ada kursi dipilih</strong>
            </div>
            <div style="padding-top:10px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                <span>Total Sementara:</span>
                <strong id="totalPriceDisplay" class="mono" style="font-size:1.2rem;">Rp 0</strong>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="showtime_id" value="<?= (int)$showtimeId ?>">
            <input type="hidden" name="seat_ids" id="selectedSeatIds" value="">
            <button type="submit" id="submitSeatsBtn" class="btn btn-primary" style="width:100%;" disabled>
                Lanjut ke Checkout
            </button>
        </form>
    </div>
</div>

<script src="<?= baseUrl('assets/js/seat-map.js') ?>"></script>
<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
