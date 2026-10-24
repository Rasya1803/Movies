<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        setFlash('danger', 'Nama, email, dan password wajib diisi.');
    } else {
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $chk->execute([$email]);
        if ($chk->fetch()) {
            setFlash('danger', 'Email sudah terdaftar. Silakan gunakan email lain.');
        } else {
            // Simpan password secara PLAINTEXT sesuai aturan project
            $ins = $pdo->prepare("INSERT INTO users (name, email, password, role, phone) VALUES (?, ?, ?, 'user', ?)");
            $ins->execute([$name, $email, $password, $phone]);

            $_SESSION['user'] = [
                'id'     => (int)$pdo->lastInsertId(),
                'name'   => $name,
                'email'  => $email,
                'role'   => 'user',
                'avatar' => null,
            ];
            setFlash('success', 'Registrasi berhasil! Selamat datang di CineVerse.');
            header('Location: index.php');
            exit;
        }
    }
}

$pageTitle = 'Daftar Akun';
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="auth-wrap">
    <div class="card">
        <h2 style="margin-bottom:6px;">Buat Akun Baru</h2>
        <p class="text-muted" style="margin-bottom:20px;">Daftar gratis untuk memesan kursi dan menyimpan tiket.</p>

        <form method="POST">
            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <input type="text" id="name" name="name" required placeholder="Nama lengkap Anda">
            </div>
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input type="email" id="email" name="email" required placeholder="contoh@email.com">
            </div>
            <div class="form-group">
                <label for="phone">Nomor HP</label>
                <input type="text" id="phone" name="phone" placeholder="0812xxxxxxxx">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Buat password">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Daftar Akun</button>
        </form>

        <p class="text-muted" style="margin-top:16px;text-align:center;">
            Sudah punya akun? <a href="login.php" class="text-accent" style="font-weight:600;">Masuk di sini</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
