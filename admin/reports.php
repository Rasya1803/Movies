<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['admin']);

$fromDate = $_GET['from'] ?? date('Y-m-01');
$toDate = $_GET['to'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT b.booking_code, b.customer_name, m.title AS movie_title, st.name AS studio_name,
           s.start_time, b.discount_amount, b.total_price, p.method AS payment_method, b.created_at
    FROM bookings b
    JOIN showtimes s ON s.id = b.showtime_id
    JOIN movies m ON m.id = s.movie_id
    JOIN studios st ON st.id = s.studio_id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.status = 'paid'
      AND DATE(b.created_at) BETWEEN ? AND ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$fromDate, $toDate]);
$rows = $stmt->fetchAll();

// Ekspor CSV jika diminta
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan-penjualan-' . $fromDate . '-sd-' . $toDate . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Kode Booking', 'Pembeli', 'Film', 'Studio', 'Jadwal Tayang', 'Diskon', 'Total Bayar', 'Metode', 'Tanggal Transaksi']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['booking_code'],
            $r['customer_name'] ?: 'Walk-in',
            $r['movie_title'],
            $r['studio_name'],
            $r['start_time'],
            $r['discount_amount'],
            $r['total_price'],
            $r['payment_method'] ?: '-',
            $r['created_at'],
        ]);
    }
    fclose($out);
    exit;
}

$grandTotal = array_sum(array_column($rows, 'total_price'));

$pageTitle = 'Laporan Penjualan';
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="subnav">
    <a href="index.php">Dashboard</a>
    <a href="movies.php">Kelola Film</a>
    <a href="studios.php">Kelola Studio &amp; Kursi</a>
    <a href="promos.php">Kelola Promo</a>
    <a href="users.php">Kelola Akun</a>
    <a href="reports.php" class="active">Laporan &amp; CSV</a>
</div>

<div class="card" style="margin-bottom:24px;">
    <form method="GET" style="display:flex;gap:14px;align-items:end;flex-wrap:wrap;">
        <div class="form-group" style="margin-bottom:0;">
            <label for="from">Dari Tanggal</label>
            <input type="date" id="from" name="from" value="<?= e($fromDate) ?>">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label for="to">Sampai Tanggal</label>
            <input type="date" id="to" name="to" value="<?= e($toDate) ?>">
        </div>
        <button type="submit" class="btn btn-primary">Filter Laporan</button>
        <a href="reports.php?from=<?= e($fromDate) ?>&amp;to=<?= e($toDate) ?>&amp;export=csv" class="btn btn-outline">
            Ekspor CSV
        </a>
    </form>
</div>

<div class="card table-wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px;">
        <h3>Transaksi Lunas (<?= count($rows) ?> Pesanan)</h3>
        <strong class="mono text-accent" style="font-size:1.2rem;">Total Pendapatan: <?= rupiah($grandTotal) ?></strong>
    </div>

    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Pembeli</th>
                <th>Film &amp; Studio</th>
                <th>Metode</th>
                <th>Diskon</th>
                <th>Total Bayar</th>
                <th>Waktu Bayar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="mono"><strong><?= e($r['booking_code']) ?></strong></td>
                    <td><?= e($r['customer_name'] ?: 'Walk-in') ?></td>
                    <td><?= e($r['movie_title']) ?> (<?= e($r['studio_name']) ?>)</td>
                    <td><?= e($r['payment_method'] ?: '-') ?></td>
                    <td class="mono"><?= rupiah($r['discount_amount']) ?></td>
                    <td class="mono"><strong><?= rupiah($r['total_price']) ?></strong></td>
                    <td class="mono"><?= date('d M Y H:i', strtotime($r['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
