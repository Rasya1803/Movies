<?php
date_default_timezone_set('Asia/Jakarta');

$dbHost = '127.0.0.1';
$dbName = 'cineverse';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Koneksi database gagal: Pastikan MySQL di XAMPP aktif dan database <b>cineverse</b> sudah diimpor dari <code>database.sql</code>. Detail: ' . htmlspecialchars($e->getMessage()));
}

/**
 * Otomatis batalkan (expire) pesanan berstatus pending yang lewat 10 menit (expires_at < NOW())
 * dan bebaskan kursinya dari tabel booking_seats agar bisa dipesan lagi.
 */
function expirePendingBookings(PDO $pdo): void
{
    $stmt = $pdo->query("SELECT id FROM bookings WHERE status = 'pending' AND expires_at < NOW()");
    $expiredIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($expiredIds)) {
        $placeholders = implode(',', array_fill(0, count($expiredIds), '?'));
        $del = $pdo->prepare("DELETE FROM booking_seats WHERE booking_id IN ({$placeholders})");
        $del->execute($expiredIds);

        $upd = $pdo->prepare("UPDATE bookings SET status = 'expired' WHERE id IN ({$placeholders})");
        $upd->execute($expiredIds);
    }
}

expirePendingBookings($pdo);
