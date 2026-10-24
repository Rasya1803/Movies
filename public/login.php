<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Pencocokan password PLAINTEXT sesuai spesifikasi belajar
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND password = ? LIMIT 1');
    $stmt->execute([$email, $password]);
    $user = $stmt->fetch();

    if (!$user) {
        setFlash('danger', 'Email atau password salah.');
    } elseif (!(int)$user['is_active']) {
        setFlash('danger', 'Akun Anda sedang dinonaktifkan oleh Admin.');
    } else {
        $_SESSION['user'] = [
            'id'     => (int)$user['id'],
            'name'   => $user['name'],
            'email'  => $user['email'],
            'role'   => $user['role'],
            'avatar' => $user['avatar'] ?? null,
        ];
        setFlash('success', 'Selamat datang kembali, ' . $user['name'] . '!');
        if ($user['role'] === 'admin') {
            header('Location: ' . baseUrl('admin/index.php'));
        } elseif ($user['role'] === 'operator') {
            header('Location: ' . baseUrl('operator/index.php'));
        } else {
            header('Location: ' . baseUrl('public/index.php'));
        }
        exit;
    }
}

$pageTitle = 'Masuk Akun';
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="auth-wrap">
    <div class="card">
        <h2 style="margin-bottom:6px;">Masuk ke CineVerse</h2>
        <p class="text-muted" style="margin-bottom:20px;">Gunakan akun Anda untuk memesan tiket bioskop.</p>

        <form method="POST">
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input type="email" id="email" name="email" required placeholder="contoh@email.com">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Masukkan password">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Masuk</button>
        </form>

        <p class="text-muted" style="margin-top:16px;text-align:center;">
            Belum punya akun? <a href="register.php" class="text-accent" style="font-weight:600;">Daftar Sekarang</a>
        </p>

        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);font-size:0.85rem;">
            <strong style="display:block;margin-bottom:8px;">Akun Demo (Klik untuk isi otomatis):</strong>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('email').value='budi@gmail.com';document.getElementById('password').value='user123';">User (budi@gmail.com)</button>
                <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('email').value='operator@cineverse.id';document.getElementById('password').value='operator123';">Operator</button>
                <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('email').value='admin@cineverse.id';document.getElementById('password').value='admin123';">Admin</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
