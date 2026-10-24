<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['operator', 'admin']);
$operator = currentUser();

$scanResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_code'])) {
    $code = strtoupper(trim($_POST['booking_code']));
    $stmt = $pdo->prepare("
        SELECT b.*, s.start_time, m.title AS movie_title, st.name AS studio_name,
               (SELECT GROUP_CONCAT(CONCAT(se.row_label, se.seat_number) ORDER BY se.row_label, se.seat_number SEPARATOR ', ')
                FROM booking_seats bs JOIN seats se ON se.id = bs.seat_id
                WHERE bs.booking_id = b.id) AS seats_list
        FROM bookings b
        JOIN showtimes s ON s.id = b.showtime_id
        JOIN movies m ON m.id = s.movie_id
        JOIN studios st ON st.id = s.studio_id
        WHERE b.booking_code = ?
        LIMIT 1
    ");
    $stmt->execute([$code]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        $scanResult = ['status' => 'danger', 'title' => 'TIKET TIDAK DITEMUKAN', 'msg' => "Kode booking {$code} tidak terdaftar di sistem."];
    } elseif ($ticket['status'] !== 'paid') {
        $scanResult = ['status' => 'danger', 'title' => 'TIKET BELUM LUNAS / BATAL', 'msg' => "Status pesanan saat ini adalah " . strtoupper($ticket['status']) . ".", 'ticket' => $ticket];
    } elseif (!empty($ticket['checked_in_at'])) {
        $scanResult = [
            'status' => 'danger',
            'title'  => 'DITOLAK — TIKET SUDAH DIPAKAI!',
            'msg'    => 'Tiket ini sudah pernah di-scan pada ' . date('d M Y, H:i:s', strtotime($ticket['checked_in_at'])) . ' WIB.',
            'ticket' => $ticket
        ];
    } else {
        $upd = $pdo->prepare('UPDATE bookings SET checked_in_at = NOW(), checked_in_by = ? WHERE id = ?');
        $upd->execute([$operator['id'], $ticket['id']]);
        $ticket['checked_in_at'] = date('Y-m-d H:i:s');
        $scanResult = [
            'status' => 'success',
            'title'  => 'TIKET VALID — SILAKAN MASUK',
            'msg'    => 'Check-in berhasil dicatat.',
            'ticket' => $ticket
        ];
    }
}

// Daftar jadwal tayang & okupansi kursi
$sessions = $pdo->query("
    SELECT s.*, m.title AS movie_title, st.name AS studio_name,
           (st.total_rows * st.total_cols) AS capacity,
           (SELECT COUNT(*) FROM booking_seats bs
            JOIN bookings b ON b.id = bs.booking_id
            WHERE bs.showtime_id = s.id AND b.status IN ('paid', 'pending')) AS occupied_seats
    FROM showtimes s
    JOIN movies m ON m.id = s.movie_id
    JOIN studios st ON st.id = s.studio_id
    WHERE s.status = 'scheduled'
    ORDER BY s.start_time ASC
")->fetchAll();

$pageTitle = 'Panel Operator — Validasi Tiket';
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="subnav">
    <a href="index.php" class="active">Validasi &amp; Scan Tiket</a>
    <a href="showtimes.php">Kelola Jadwal Tayang</a>
    <a href="pos.php">Penjualan Loket (Walk-in)</a>
</div>

<div class="booking-layout">
    <div class="card">
        <h2 style="margin-bottom:8px;">Validasi Tiket Pintu Studio</h2>
        <p class="text-muted" style="margin-bottom:16px;">
            Masukkan kode booking (contoh: <code>CV-DEMO01</code> untuk tiket aktif atau <code>CV-DEMO02</code> yang sudah dipakai) atau scan QR Code via kamera.
        </p>

        <form method="POST" id="validateTicketForm" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
            <input type="text" id="bookingCodeInput" name="booking_code" class="mono" style="flex:1;min-width:200px;" required placeholder="Ketik Kode Booking (mis: CV-DEMO01)">
            <button type="submit" class="btn btn-primary">Validasi Tiket</button>
            <button type="button" id="startScanBtn" class="btn btn-outline">Scan QR via Kamera</button>
        </form>

        <div id="qrReader" style="display:none;max-width:340px;margin:16px auto;border-radius:12px;overflow:hidden;"></div>

        <?php if ($scanResult): ?>
            <div class="alert alert-<?= e($scanResult['status']) ?>" style="margin-top:16px;">
                <h3 style="margin-bottom:4px;color:inherit;"><?= e($scanResult['title']) ?></h3>
                <p><?= e($scanResult['msg']) ?></p>
                <?php if (!empty($scanResult['ticket'])): $t = $scanResult['ticket']; ?>
                    <div style="margin-top:12px;padding-top:12px;border-top:1px solid currentColor;font-size:0.9rem;">
                        <strong>Film:</strong> <?= e($t['movie_title']) ?> |
                        <strong>Studio:</strong> <?= e($t['studio_name']) ?> |
                        <strong>Jam:</strong> <span class="mono"><?= date('d M Y H:i', strtotime($t['start_time'])) ?></span> |
                        <strong>Kursi:</strong> <span class="mono"><?= e($t['seats_list'] ?: '-') ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3 style="margin-bottom:12px;">Panduan Cepat Operator</h3>
        <ul style="padding-left:18px;font-size:0.9rem;color:var(--text-muted);display:flex;flex-direction:column;gap:8px;">
            <li>Tiket hanya berlaku untuk <strong>1 kali scan</strong>. Percobaan scan kedua otomatis ditolak.</li>
            <li>Coba kode <code>CV-DEMO01</code> untuk simulasi tiket valid.</li>
            <li>Coba kode <code>CV-DEMO02</code> untuk simulasi tiket yang ditolak karena sudah pernah check-in.</li>
            <li>Gunakan menu <strong>Penjualan Loket</strong> untuk pembeli tunai tanpa akun.</li>
        </ul>
    </div>
</div>

<div class="section-header">
    <h2>Daftar Sesi &amp; Okupansi Kursi</h2>
</div>

<div class="card table-wrap">
    <table>
        <thead>
            <tr>
                <th>Film</th>
                <th>Studio</th>
                <th>Waktu Tayang</th>
                <th>Harga</th>
                <th>Kursi Terisi</th>
                <th>Aksi Loket</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sessions as $s): ?>
                <tr>
                    <td><strong><?= e($s['movie_title']) ?></strong></td>
                    <td><?= e($s['studio_name']) ?></td>
                    <td class="mono"><?= date('d M Y, H:i', strtotime($s['start_time'])) ?> WIB</td>
                    <td class="mono"><?= rupiah($s['price']) ?></td>
                    <td class="mono">
                        <strong><?= (int)$s['occupied_seats'] ?></strong> / <?= (int)$s['capacity'] ?> kursi
                    </td>
                    <td>
                        <a href="pos.php?showtime_id=<?= (int)$s['id'] ?>" class="btn btn-primary btn-sm">Jual di Loket</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="<?= baseUrl('assets/js/scanner.js') ?>"></script>
<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
