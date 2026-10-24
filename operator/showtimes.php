<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['operator', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_showtime') {
        $movieId = (int)($_POST['movie_id'] ?? 0);
        $studioId = (int)($_POST['studio_id'] ?? 0);
        $startTime = $_POST['start_time'] ?? '';
        $price = (int)($_POST['price'] ?? 40000);

        if ($movieId > 0 && $studioId > 0 && $startTime !== '') {
            $ins = $pdo->prepare("INSERT INTO showtimes (movie_id, studio_id, start_time, price, status) VALUES (?, ?, ?, ?, 'scheduled')");
            $ins->execute([$movieId, $studioId, str_replace('T', ' ', $startTime), $price]);
            setFlash('success', 'Jadwal tayang baru berhasil ditambahkan dan langsung tampil di halaman film.');
        }
        header('Location: showtimes.php');
        exit;
    }

    if ($action === 'toggle_status') {
        $id = (int)($_POST['showtime_id'] ?? 0);
        $nextStatus = ($_POST['next_status'] ?? 'cancelled') === 'scheduled' ? 'scheduled' : 'cancelled';
        $upd = $pdo->prepare("UPDATE showtimes SET status = ? WHERE id = ?");
        $upd->execute([$nextStatus, $id]);
        setFlash('info', 'Status jadwal tayang berhasil diperbarui.');
        header('Location: showtimes.php');
        exit;
    }
}

$movies = $pdo->query("SELECT id, title FROM movies WHERE status != 'archived' ORDER BY title ASC")->fetchAll();
$studios = $pdo->query("SELECT id, name, type, base_price FROM studios WHERE is_active = 1 ORDER BY id ASC")->fetchAll();

$showtimes = $pdo->query("
    SELECT s.*, m.title AS movie_title, st.name AS studio_name
    FROM showtimes s
    JOIN movies m ON m.id = s.movie_id
    JOIN studios st ON st.id = s.studio_id
    ORDER BY s.start_time DESC
")->fetchAll();

$pageTitle = 'Kelola Jadwal Tayang';
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="subnav">
    <a href="index.php">Validasi &amp; Scan Tiket</a>
    <a href="showtimes.php" class="active">Kelola Jadwal Tayang</a>
    <a href="pos.php">Penjualan Loket (Walk-in)</a>
</div>

<div class="card" style="margin-bottom:28px;">
    <h2 style="margin-bottom:16px;">Tambah Jadwal Tayang Baru</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_showtime">
        <div class="form-row">
            <div class="form-group">
                <label for="movie_id">Pilih Film</label>
                <select name="movie_id" id="movie_id" required>
                    <?php foreach ($movies as $m): ?>
                        <option value="<?= (int)$m['id'] ?>"><?= e($m['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="studio_id">Pilih Studio</label>
                <select name="studio_id" id="studio_id" required>
                    <?php foreach ($studios as $st): ?>
                        <option value="<?= (int)$st['id'] ?>">
                            <?= e($st['name']) ?> (<?= strtoupper(e($st['type'])) ?> — <?= rupiah($st['base_price']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="start_time">Tanggal &amp; Jam Mulai</label>
                <input type="datetime-local" id="start_time" name="start_time" value="<?= date('Y-m-d\TH:00', strtotime('+2 hours')) ?>" required>
            </div>
            <div class="form-group">
                <label for="price">Harga Tiket (Rp)</label>
                <input type="number" id="price" name="price" value="40000" step="5000" min="10000" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">+ Tambah Jadwal Tayang</button>
    </form>
</div>

<div class="card table-wrap">
    <h3 style="margin-bottom:14px;">Semua Jadwal Tayang</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Film</th>
                <th>Studio</th>
                <th>Waktu Mulai</th>
                <th>Harga</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($showtimes as $sh): ?>
                <tr>
                    <td class="mono">#<?= (int)$sh['id'] ?></td>
                    <td><strong><?= e($sh['movie_title']) ?></strong></td>
                    <td><?= e($sh['studio_name']) ?></td>
                    <td class="mono"><?= date('d M Y, H:i', strtotime($sh['start_time'])) ?></td>
                    <td class="mono"><?= rupiah($sh['price']) ?></td>
                    <td>
                        <span class="badge <?= $sh['status'] === 'scheduled' ? 'badge-success' : 'badge-danger' ?>">
                            <?= strtoupper(e($sh['status'])) ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="showtime_id" value="<?= (int)$sh['id'] ?>">
                            <?php if ($sh['status'] === 'scheduled'): ?>
                                <input type="hidden" name="next_status" value="cancelled">
                                <button type="submit" class="btn btn-outline btn-sm text-danger">Batalkan</button>
                            <?php else: ?>
                                <input type="hidden" name="next_status" value="scheduled">
                                <button type="submit" class="btn btn-outline btn-sm">Aktifkan</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
