<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';
$pageTitle = isset($pageTitle) ? $pageTitle . ' — CineVerse' : 'CineVerse — Pesan Tiket Bioskop';
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@500&family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css') ?>">
    <script>
        (function () {
            var savedTheme = localStorage.getItem('cineverse_theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>
<body>
<?php include __DIR__ . '/navbar.php'; ?>
<main class="container main-content">
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
            <span><?= e($flash['message']) ?></span>
        </div>
    <?php endif; ?>
