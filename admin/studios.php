<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_studio') {
        $name = trim($_POST['name'] ?? '');
        $type = ($_POST['type'] ?? 'regular') === 'premier' ? 'premier' : 'regular';
        $rows = max(1, min(15, (int)($_POST['total_rows'] ?? 6)));
        $cols = max(1, min(20, (int)($_POST['total_cols'] ?? 8)));
        $basePrice = max(10000, (int)($_POST['base_price'] ?? 40000));

        if ($name !== '') {
            $ins = $pdo->prepare("INSERT INTO studios (name, type, total_rows, total_cols, base_price, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $ins->execute([$name, $type, $rows, $cols, $basePrice]);
            $studioId = (int)$pdo->lastInsertId();
            generateStudioSeats($pdo, $studioId, $rows, $cols, $type);
            setFlash('success', "Studio {$name} berhasil dibuat beserta " . ($rows * $cols) . " kursi otomatis!");
        }
        header('Location: studios.php');
        exit;
    }

    if ($action === 'update_price') {
        $id = (int)($_POST['studio_id'] ?? 0);
        $price = max(10000, (int)($_POST['base_price'] ?? 40000));
        $pdo->prepare("UPDATE studios SET base_price = ? WHERE id = ?")->execute([$price, $id]);
        setFlash('success', 'Harga dasar studio berhasil diperbarui.');
        header('Location: studios.php');
        exit;
    }
}

$studios = $pdo->query("
    SELECT st.*, (SELECT COUNT(*) FROM seats s WHERE s.studio_id = st.id) AS seat_count
    FROM studios st
    ORDER BY st.id ASC
")->fetchAll();

$pageTitle = 'Kelola Studio & Kursi';
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="subnav">
    <a href="index.php">Dashboard</a>
    <a href="movies.php">Kelola Film</a>
    <a href="studios.php" class="active">Kelola Studio &amp; Kursi</a>
    <a href="promos.php">Kelola Promo</a>
    <a href="users.php">Kelola Akun</a>
    <a href="reports.php">Laporan &amp; CSV</a>
</div>

<div class="card" style="margin-bottom:28px;">
    <h2 style="margin-bottom:16px;">Tambah Studio Baru (Generate Kursi Otomatis)</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_studio">
        <div class="form-row">
            <div class="form-group">
                <label for="name">Nama Studio</label>
                <input type="text" id="name" name="name" placeholder="Contoh: Studio 4 IMAX" required>
            </div>
            <div class="form-group">
                <label for="type">Tipe Studio</label>
                <select name="type" id="type">
                    <option value="regular">Regular</option>
                    <option value="premier">Premier</option>
                </select>
            </div>
            <div class="form-group">
                <label for="total_rows">Jumlah Baris (A-Z)</label>
                <input type="number" id="total_rows" name="total_rows" value="6" min="2" max="15" required>
            </div>
            <div class="form-group">
                <label for="total_cols">Kursi per Baris</label>
                <input type="number" id="total_cols" name="total_cols" value="8" min="2" max="20" required>
            </div>
            <div class="form-group">
                <label for="base_price">Harga Tiket Dasar (Rp)</label>
                <input type="number" id="base_price" name="base_price" value="45000" step="5000" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">+ Buat Studio &amp; Kursi</button>
    </form>
</div>

<div class="card table-wrap">
    <h3 style="margin-bottom:14px;">Daftar Studio &amp; Harga Tiket</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Studio</th>
                <th>Tipe</th>
                <th>Ukuran Grid</th>
                <th>Total Kursi</th>
                <th>Harga Dasar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($studios as $st): ?>
                <tr>
                    <td class="mono">#<?= (int)$st['id'] ?></td>
                    <td><strong><?= e($st['name']) ?></strong></td>
                    <td><span class="badge badge-info"><?= strtoupper(e($st['type'])) ?></span></td>
                    <td class="mono"><?= (int)$st['total_rows'] ?> baris &times; <?= (int)$st['total_cols'] ?> kolom</td>
                    <td class="mono"><?= (int)$st['seat_count'] ?> kursi</td>
                    <td>
                        <form method="POST" style="display:flex;gap:8px;align-items:center;">
                            <input type="hidden" name="action" value="update_price">
                            <input type="hidden" name="studio_id" value="<?= (int)$st['id'] ?>">
                            <input type="number" name="base_price" value="<?= (int)$st['base_price'] ?>" step="5000" style="width:130px;">
                            <button type="submit" class="btn btn-outline btn-sm">Simpan</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
