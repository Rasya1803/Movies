<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'operator';
        if (!in_array($role, ['user', 'operator', 'admin'], true)) {
            $role = 'user';
        }

        if ($name !== '' && $email !== '' && $password !== '') {
            // Password disimpan PLAINTEXT sesuai spesifikasi
            $ins = $pdo->prepare("INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)");
            $ins->execute([$name, $email, $password, $role]);
            setFlash('success', "Akun {$name} ({$role}) berhasil ditambahkan.");
        }
        header('Location: users.php');
        exit;
    }

    if ($action === 'update_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $role = $_POST['role'] ?? 'user';
        $active = (int)($_POST['is_active'] ?? 1);
        $upd = $pdo->prepare("UPDATE users SET role = ?, is_active = ? WHERE id = ?");
        $upd->execute([$role, $active, $uid]);
        setFlash('success', 'Peran dan status akun berhasil diperbarui.');
        header('Location: users.php');
        exit;
    }
}

$users = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();

$pageTitle = 'Kelola Akun Pengguna & Operator';
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="subnav">
    <a href="index.php">Dashboard</a>
    <a href="movies.php">Kelola Film</a>
    <a href="studios.php">Kelola Studio &amp; Kursi</a>
    <a href="promos.php">Kelola Promo</a>
    <a href="users.php" class="active">Kelola Akun</a>
    <a href="reports.php">Laporan &amp; CSV</a>
</div>

<div class="card" style="margin-bottom:28px;">
    <h2 style="margin-bottom:16px;">Tambah Akun User / Operator</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_user">
        <div class="form-row">
            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password (Plaintext)</label>
                <input type="text" id="password" name="password" class="mono" required placeholder="Contoh: operator123">
            </div>
            <div class="form-group">
                <label for="role">Peran (Role)</label>
                <select name="role" id="role">
                    <option value="operator">Operator</option>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">+ Tambah Akun</button>
    </form>
</div>

<div class="card table-wrap">
    <h3 style="margin-bottom:14px;">Daftar Akun Terdaftar</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Password (Plaintext)</th>
                <th>Poin</th>
                <th>Peran &amp; Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td class="mono">#<?= (int)$u['id'] ?></td>
                    <td><strong><?= e($u['name']) ?></strong></td>
                    <td><?= e($u['email']) ?></td>
                    <td class="mono"><code><?= e($u['password']) ?></code></td>
                    <td class="mono"><?= (int)$u['points'] ?></td>
                    <td>
                        <form method="POST" style="display:flex;gap:8px;align-items:center;">
                            <input type="hidden" name="action" value="update_user">
                            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                            <select name="role" style="width:120px;">
                                <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                <option value="operator" <?= $u['role'] === 'operator' ? 'selected' : '' ?>>Operator</option>
                                <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <select name="is_active" style="width:120px;">
                                <option value="1" <?= (int)$u['is_active'] === 1 ? 'selected' : '' ?>>Aktif</option>
                                <option value="0" <?= (int)$u['is_active'] === 0 ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                            <button type="submit" class="btn btn-outline btn-sm">Simpan</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
