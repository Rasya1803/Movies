<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['admin']);

$totalRevenue = (int)$pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE status = 'paid'")->fetchColumn();
$totalTickets = (int)$pdo->query("
    SELECT COUNT(bs.id)
    FROM booking_seats bs
    JOIN bookings b ON b.id = bs.booking_id
    WHERE b.status = 'paid'
")->fetchColumn();
$activeMovies = (int)$pdo->query("SELECT COUNT(*) FROM movies WHERE status = 'now_showing'")->fetchColumn();
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Data grafik pendapatan per tanggal
$revRows = $pdo->query("
    SELECT DATE(created_at) AS tgl, SUM(total_price) AS total
    FROM bookings
    WHERE status = 'paid'
    GROUP BY DATE(created_at)
    ORDER BY tgl ASC
    LIMIT 7
")->fetchAll();
$revLabels = array_map(fn($r) => date('d M', strtotime($r['tgl'])), $revRows);
$revValues = array_map(fn($r) => (int)$r['total'], $revRows);

// Data grafik film terlaris
$topMovieRows = $pdo->query("
    SELECT m.title, COUNT(bs.id) AS terjual
    FROM movies m
    JOIN showtimes s ON s.movie_id = m.id
    JOIN bookings b ON b.showtime_id = s.id AND b.status = 'paid'
    JOIN booking_seats bs ON bs.booking_id = b.id
    GROUP BY m.id
    ORDER BY terjual DESC
    LIMIT 5
")->fetchAll();
$movieLabels = array_column($topMovieRows, 'title');
$movieValues = array_map('intval', array_column($topMovieRows, 'terjual'));

// Pesanan terbaru
$recentBookings = $pdo->query("
    SELECT b.*, m.title AS movie_title, st.name AS studio_name
    FROM bookings b
    JOIN showtimes s ON s.id = b.showtime_id
    JOIN movies m ON m.id = s.movie_id
    JOIN studios st ON st.id = s.studio_id
    ORDER BY b.created_at DESC
    LIMIT 8
")->fetchAll();

$pageTitle = 'Dashboard Admin';
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="subnav">
    <a href="index.php" class="active">Dashboard</a>
    <a href="movies.php">Kelola Film</a>
    <a href="studios.php">Kelola Studio &amp; Kursi</a>
    <a href="promos.php">Kelola Promo</a>
    <a href="users.php">Kelola Akun</a>
    <a href="reports.php">Laporan &amp; CSV</a>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <span class="text-muted">Total Pendapatan (Paid)</span>
        <div class="stat-value text-accent"><?= rupiah($totalRevenue) ?></div>
    </div>
    <div class="stat-card">
        <span class="text-muted">Total Kursi Terjual</span>
        <div class="stat-value"><?= $totalTickets ?> Kursi</div>
    </div>
    <div class="stat-card">
        <span class="text-muted">Film Sedang Tayang</span>
        <div class="stat-value"><?= $activeMovies ?> Film</div>
    </div>
    <div class="stat-card">
        <span class="text-muted">Total Akun Terdaftar</span>
        <div class="stat-value"><?= $totalUsers ?> Akun</div>
    </div>
</div>

<div class="form-row" style="margin-bottom:24px;">
    <div class="card">
        <h3 style="margin-bottom:14px;">Grafik Pendapatan Harian</h3>
        <canvas id="revenueChart"
            data-labels="<?= e(json_encode($revLabels)) ?>"
            data-values="<?= e(json_encode($revValues)) ?>"
            height="160"></canvas>
    </div>
    <div class="card">
        <h3 style="margin-bottom:14px;">Film Terlaris (Kursi Terjual)</h3>
        <canvas id="topMoviesChart"
            data-labels="<?= e(json_encode($movieLabels)) ?>"
            data-values="<?= e(json_encode($movieValues)) ?>"
            height="160"></canvas>
    </div>
</div>

<div class="card table-wrap">
    <h3 style="margin-bottom:14px;">Transaksi Terbaru</h3>
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Pembeli</th>
                <th>Film &amp; Studio</th>
                <th>Total</th>
                <th>Status</th>
                <th>Waktu</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentBookings as $b): ?>
                <tr>
                    <td class="mono"><strong><?= e($b['booking_code']) ?></strong></td>
                    <td><?= e($b['customer_name'] ?: 'Walk-in') ?></td>
                    <td><?= e($b['movie_title']) ?> (<?= e($b['studio_name']) ?>)</td>
                    <td class="mono"><?= rupiah($b['total_price']) ?></td>
                    <td><span class="badge badge-<?= e($b['status']) ?>"><?= strtoupper(e($b['status'])) ?></span></td>
                    <td class="mono"><?= date('d M H:i', strtotime($b['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= baseUrl('assets/js/charts.js') ?>"></script>
<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
