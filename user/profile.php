<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$sessionUser = currentUser();
$userId = (int)$sessionUser['id'];

// Pastikan kolom avatar sudah ada di tabel users (aman jika database lama belum di-alter)
$colCheck = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'")->fetch();
if (!$colCheck) {
    $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER phone");
}

// Ambil data lengkap user dari database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if (!$profile) {
    session_destroy();
    header('Location: ' . baseUrl('public/login.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'remove_avatar') {
        $pdo->prepare("UPDATE users SET avatar = NULL WHERE id = ?")->execute([$userId]);
        $_SESSION['user']['avatar'] = null;
        setFlash('info', 'Foto profil telah dihapus.');
        header('Location: profile.php');
        exit;
    }

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            setFlash('danger', 'Nama lengkap, email, dan password tidak boleh kosong.');
        } else {
            // Cek apakah email dipakai oleh akun lain
            $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
            $chk->execute([$email, $userId]);
            if ($chk->fetch()) {
                setFlash('danger', 'Alamat email tersebut sudah digunakan oleh akun lain.');
            } else {
                $newAvatar = uploadAvatar($_FILES['avatar'] ?? []);
                $avatarToSave = $newAvatar ?: ($profile['avatar'] ?? null);

                // Simpan perubahan profil (password plaintext sesuai aturan project)
                $upd = $pdo->prepare("
                    UPDATE users
                    SET name = ?, email = ?, phone = ?, password = ?, avatar = ?
                    WHERE id = ?
                ");
                $upd->execute([$name, $email, $phone, $password, $avatarToSave, $userId]);

                // Sinkronkan session user
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['avatar'] = $avatarToSave;

                setFlash('success', 'Profil Anda berhasil diperbarui!');
                header('Location: profile.php');
                exit;
            }
        }
    }
}

// Ambil statistik singkat user
$paidBookingsCount = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE user_id = {$userId} AND status = 'paid'")->fetchColumn();
$watchlistCount = (int)$pdo->query("SELECT COUNT(*) FROM watchlists WHERE user_id = {$userId}")->fetchColumn();
$reviewCount = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE user_id = {$userId}")->fetchColumn();

$pageTitle = 'Profil Saya';
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="section-header">
    <div>
        <h1>Profil Saya</h1>
        <p class="text-muted">Kelola foto profil, informasi kontak, dan password akun Anda.</p>
    </div>
    <?php if ($profile['role'] === 'user'): ?>
        <a href="history.php" class="btn btn-outline btn-sm">Lihat Riwayat Tiket &rarr;</a>
    <?php endif; ?>
</div>

<div class="profile-layout">
    <!-- Kartu Ringkasan Profil -->
    <div class="card" style="text-align:center;">
        <?php if (!empty($profile['avatar'])): ?>
            <img src="<?= e(avatarUrl($profile['avatar'])) ?>" alt="<?= e($profile['name']) ?>" id="avatarPreviewImg" class="profile-avatar-lg">
        <?php else: ?>
            <img src="" alt="Preview" id="avatarPreviewImg" class="profile-avatar-lg" style="display:none;">
            <div id="avatarFallbackBox" class="profile-avatar-fallback-lg">
                <?= e(userInitials($profile['name'])) ?>
            </div>
        <?php endif; ?>

        <h2 style="font-size:1.3rem;margin-bottom:4px;"><?= e($profile['name']) ?></h2>
        <p class="text-muted" style="margin-bottom:10px;"><?= e($profile['email']) ?></p>
        <span class="badge badge-warning"><?= strtoupper(e($profile['role'])) ?></span>

        <?php if (!empty($profile['avatar'])): ?>
            <form method="POST" style="margin-top:12px;">
                <input type="hidden" name="action" value="remove_avatar">
                <button type="submit" class="btn btn-outline btn-sm text-danger">Hapus Foto Profil</button>
            </form>
        <?php endif; ?>

        <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border);text-align:left;font-size:0.9rem;display:flex;flex-direction:column;gap:10px;">
            <div style="display:flex;justify-content:space-between;">
                <span class="text-muted">Nomor HP:</span>
                <strong class="mono"><?= e($profile['phone'] ?: '-') ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span class="text-muted">Poin Loyalitas:</span>
                <strong class="mono text-accent"><?= (int)$profile['points'] ?> Poin</strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span class="text-muted">Tiket Lunas:</span>
                <strong class="mono"><?= $paidBookingsCount ?> Pesanan</strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span class="text-muted">Watchlist &amp; Ulasan:</span>
                <strong class="mono"><?= $watchlistCount ?> Film • <?= $reviewCount ?> Ulasan</strong>
            </div>
            <div style="display:flex;justify-content:space-between;">
                <span class="text-muted">Bergabung Sejak:</span>
                <span class="mono"><?= date('d M Y', strtotime($profile['created_at'])) ?></span>
            </div>
        </div>
    </div>

    <!-- Form Edit Profil -->
    <div class="card">
        <h3 style="margin-bottom:16px;">Edit Data Diri &amp; Foto Profil</h3>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_profile">

            <div class="form-group">
                <label for="avatarInput">Upload Foto Profil Baru (JPG, PNG, WEBP, SVG)</label>
                <input type="file" id="avatarInput" name="avatar" accept=".jpg,.jpeg,.png,.webp,.svg" class="form-control">
                <small class="text-muted">Kosongkan jika tidak ingin mengganti foto profil.</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" value="<?= e($profile['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Alamat Email</label>
                    <input type="email" id="email" name="email" value="<?= e($profile['email']) ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Nomor HP / WhatsApp</label>
                    <input type="text" id="phone" name="phone" value="<?= e($profile['phone'] ?? '') ?>" placeholder="Contoh: 081234567890">
                </div>
                <div class="form-group">
                    <label for="password">Password (Plaintext)</label>
                    <input type="text" id="password" name="password" class="mono" value="<?= e($profile['password']) ?>" required>
                </div>
            </div>

            <div style="margin-top:8px;display:flex;gap:12px;align-items:center;">
                <button type="submit" class="btn btn-primary">Simpan Perubahan Profil</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var avatarInput = document.getElementById('avatarInput');
    var previewImg = document.getElementById('avatarPreviewImg');
    var fallbackBox = document.getElementById('avatarFallbackBox');

    if (avatarInput && previewImg) {
        avatarInput.addEventListener('change', function () {
            var file = avatarInput.files && avatarInput.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
                previewImg.style.display = 'block';
                if (fallbackBox) fallbackBox.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
