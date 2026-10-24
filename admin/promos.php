<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_promo') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = ($_POST['type'] ?? 'percent') === 'fixed' ? 'fixed' : 'percent';
        $value = max(1, (int)($_POST['value'] ?? 10));
        $quota = max(1, (int)($_POST['quota'] ?? 50));
        $validFrom = $_POST['valid_from'] ?? date('Y-m-d');
        $validUntil = $_POST['valid_until'] ?? date('Y-m-d', strtotime('+30 days'));

        if ($code !== '') {
            $ins = $pdo->prepare("
                INSERT INTO promos (code, type, value, quota, valid_from, valid_until, is_active)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $ins->execute([$code, $type, $value, $quota, $validFrom, $validUntil]);
            setFlash('success', "Kode promo {$code} berhasil ditambahkan.");
        }
        header('Location: promos.php');
        exit;
    }

    if ($action === 'toggle_promo') {
        $id = (int)($_POST['promo_id'] ?? 0);
        $pdo->prepare("UPDATE promos SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
        setFlash('info', 'Status promo diperbarui.');
        header('Location: promos.php');
        exit;
    }
}

$promos = $pdo->query("SELECT * FROM promos ORDER BY id DESC")->fetchAll();

$pageTitle = 'Kelola Kode Promo';
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="subnav">
    <a href="index.php">Dashboard</a>
    <a href="movies.php">Kelola Film</a>
    <a href="studios.php">Kelola Studio &amp; Kursi</a>
    <a href="promos.php" class="active">Kelola Promo</a>
    <a href="users.php">Kelola Akun</a>
    <a href="reports.php">Laporan &amp; CSV</a>
</div>

<div class="card" style="margin-bottom:28px;">
    <h2 style="margin-bottom:16px;">Tambah Kode Promo Baru</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_promo">
        <div class="form-row">
            <div class="form-group">
                <label for="code">Kode Promo</label>
                <input type="text" id="code" name="code" class="mono" placeholder="MIS: GAJIAN25" required>
            </div>
            <div class="form-group">
                <label for="type">Tipe Diskon</label>
                <select name="type" id="type">
                    <option value="percent">Persen (%)</option>
                    <option value="fixed">Nominal Tetap (Rp)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="value">Nilai Diskon</label>
                <input type="number" id="value" name="value" value="15" required>
            </div>
            <div class="form-group">
                <label for="quota">Kuota Pemakaian</label>
                <input type="number" id="quota" name="quota" value="100" required>
            </div>
            <div class="form-group">
                <label for="valid_from">Berlaku Dari</label>
                <input type="date" id="valid_from" name="valid_from" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label for="valid_until">Sampai Tanggal</label>
                <input type="date" id="valid_until" name="valid_until" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">+ Simpan Promo</button>
    </form>
</div>

<div class="card table-wrap">
    <h3 style="margin-bottom:14px;">Daftar Kode Promo</h3>
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Potongan</th>
                <th>Sisa Kuota</th>
                <th>Masa Berlaku</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($promos as $p): ?>
                <tr>
                    <td class="mono"><strong><?= e($p['code']) ?></strong></td>
                    <td class="mono">
                        <?= $p['type'] === 'percent' ? (int)$p['value'] . '%' : rupiah($p['value']) ?>
                    </td>
                    <td class="mono"><?= (int)$p['quota'] ?></td>
                    <td class="mono"><?= e($p['valid_from']) ?> s/d <?= e($p['valid_until']) ?></td>
                    <td>
                        <span class="badge <?= $p['is_active'] ? 'badge-success' : 'badge-danger' ?>">
                            <?= $p['is_active'] ? 'AKTIF' : 'NONAKTIF' ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="action" value="toggle_promo">
                            <input type="hidden" name="promo_id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="btn btn-outline btn-sm">
                                <?= $p['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
