<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$user = currentUser();

$stmt = $pdo->prepare("
    SELECT b.*, s.start_time, m.title AS movie_title, st.name AS studio_name,
           (SELECT GROUP_CONCAT(CONCAT(se.row_label, se.seat_number) ORDER BY se.row_label, se.seat_number SEPARATOR ', ')
            FROM booking_seats bs JOIN seats se ON se.id = bs.seat_id
            WHERE bs.booking_id = b.id) AS seats_list
    FROM bookings b
    JOIN showtimes s ON s.id = b.showtime_id
    JOIN movies m ON m.id = s.movie_id
    JOIN studios st ON st.id = s.studio_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll();

$pageTitle = 'Riwayat Pesanan & Tiket Saya';
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="section-header">
    <h1>Riwayat Pesanan &amp; Tiket Saya</h1>
    <a href="<?= baseUrl('public/movies.php') ?>" class="btn btn-primary btn-sm">+ Pesan Tiket Baru</a>
</div>

<?php if (empty($bookings)): ?>
    <div class="card" style="text-align:center;">
        <p class="text-muted" style="margin-bottom:16px;">Anda belum memiliki riwayat pemesanan tiket.</p>
        <a href="<?= baseUrl('public/movies.php') ?>" class="btn btn-primary">Jelajahi Katalog Film</a>
    </div>
<?php else: ?>
    <div class="card table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Kode Booking</th>
                    <th>Film &amp; Studio</th>
                    <th>Jadwal Tayang</th>
                    <th>Kursi</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td class="mono"><strong><?= e($b['booking_code']) ?></strong></td>
                        <td>
                            <strong><?= e($b['movie_title']) ?></strong><br>
                            <span class="text-muted"><?= e($b['studio_name']) ?></span>
                        </td>
                        <td class="mono"><?= date('d M Y, H:i', strtotime($b['start_time'])) ?></td>
                        <td class="mono"><?= e($b['seats_list'] ?: '-') ?></td>
                        <td class="mono"><?= rupiah($b['total_price']) ?></td>
                        <td>
                            <span class="badge badge-<?= e($b['status']) ?>">
                                <?= strtoupper(e($b['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($b['status'] === 'paid'): ?>
                                <a href="ticket.php?id=<?= (int)$b['id'] ?>" class="btn btn-primary btn-sm">E-Ticket QR</a>
                            <?php elseif ($b['status'] === 'pending'): ?>
                                <a href="checkout.php?id=<?= (int)$b['id'] ?>" class="btn btn-outline btn-sm">Bayar Sekarang</a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
