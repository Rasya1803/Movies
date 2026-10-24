<?php
$user = currentUser();
$currentScript = basename($_SERVER['SCRIPT_NAME']);
?>
<header class="navbar no-print">
    <div class="container navbar-inner">
        <a href="<?= baseUrl('public/index.php') ?>" class="brand">
            <span class="brand-mark">CV</span>
            <span class="brand-text">Cine<span class="text-accent">Verse</span></span>
        </a>

        <nav class="nav-links">
            <a href="<?= baseUrl('public/index.php') ?>" class="<?= $currentScript === 'index.php' && str_contains($_SERVER['SCRIPT_NAME'], '/public/') ? 'active' : '' ?>">Beranda</a>
            <a href="<?= baseUrl('public/movies.php') ?>" class="<?= $currentScript === 'movies.php' && str_contains($_SERVER['SCRIPT_NAME'], '/public/') ? 'active' : '' ?>">Katalog Film</a>

            <?php if ($user && $user['role'] === 'user'): ?>
                <a href="<?= baseUrl('user/history.php') ?>" class="<?= $currentScript === 'history.php' ? 'active' : '' ?>">Tiket Saya</a>
                <a href="<?= baseUrl('user/watchlist.php') ?>" class="<?= $currentScript === 'watchlist.php' ? 'active' : '' ?>">Watchlist</a>
            <?php endif; ?>

            <?php if ($user && in_array($user['role'], ['operator', 'admin'], true)): ?>
                <a href="<?= baseUrl('operator/index.php') ?>" class="<?= str_contains($_SERVER['SCRIPT_NAME'], '/operator/') ? 'active' : '' ?>">Panel Operator</a>
            <?php endif; ?>

            <?php if ($user && $user['role'] === 'admin'): ?>
                <a href="<?= baseUrl('admin/index.php') ?>" class="<?= str_contains($_SERVER['SCRIPT_NAME'], '/admin/') ? 'active' : '' ?>">Dashboard Admin</a>
            <?php endif; ?>
        </nav>

        <div class="nav-actions">
            <button type="button" id="themeToggle" class="btn-icon" aria-label="Ganti mode terang atau gelap" title="Mode Terang / Gelap">
                <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="5"></circle>
                    <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path>
                </svg>
            </button>

            <?php if ($user): ?>
                <span class="user-pill">
                    <strong><?= e($user['name']) ?></strong>
                    <small class="role-tag"><?= e(strtoupper($user['role'])) ?></small>
                </span>
                <a href="<?= baseUrl('public/logout.php') ?>" class="btn btn-outline-light btn-sm">Keluar</a>
            <?php else: ?>
                <a href="<?= baseUrl('public/login.php') ?>" class="btn btn-outline-light btn-sm">Masuk</a>
                <a href="<?= baseUrl('public/register.php') ?>" class="btn btn-primary btn-sm">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</header>
