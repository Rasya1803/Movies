<?php
$pageTitle = 'Beranda';
require_once __DIR__ . '/../includes/layout/header.php';

// Ambil 1 film unggulan untuk Hero
$heroStmt = $pdo->query("
    SELECT m.*, GROUP_CONCAT(g.name SEPARATOR ', ') AS genres
    FROM movies m
    LEFT JOIN movie_genres mg ON mg.movie_id = m.id
    LEFT JOIN genres g ON g.id = mg.genre_id
    WHERE m.status = 'now_showing'
    GROUP BY m.id
    ORDER BY m.id ASC
    LIMIT 1
");
$heroMovie = $heroStmt->fetch();

// Sedang Tayang
$nowStmt = $pdo->query("
    SELECT m.*, GROUP_CONCAT(g.name SEPARATOR ', ') AS genres
    FROM movies m
    LEFT JOIN movie_genres mg ON mg.movie_id = m.id
    LEFT JOIN genres g ON g.id = mg.genre_id
    WHERE m.status = 'now_showing'
    GROUP BY m.id
    ORDER BY m.id ASC
");
$nowShowing = $nowStmt->fetchAll();

// Segera Hadir
$comingStmt = $pdo->query("
    SELECT m.*, GROUP_CONCAT(g.name SEPARATOR ', ') AS genres
    FROM movies m
    LEFT JOIN movie_genres mg ON mg.movie_id = m.id
    LEFT JOIN genres g ON g.id = mg.genre_id
    WHERE m.status = 'coming_soon'
    GROUP BY m.id
    ORDER BY m.release_date ASC
");
$comingSoon = $comingStmt->fetchAll();
?>

<?php if ($heroMovie): ?>
<section class="hero">
    <div>
        <span class="badge badge-warning" style="margin-bottom:12px;">SEDANG TAYANG HARI INI</span>
        <h1><?= e($heroMovie['title']) ?></h1>
        <p><?= e($heroMovie['synopsis']) ?></p>
        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
            <a href="movie.php?id=<?= (int)$heroMovie['id'] ?>" class="btn btn-primary">Pesan Tiket Sekarang</a>
            <a href="movies.php" class="btn btn-outline-light">Semua Film</a>
            <span class="mono" style="color:rgba(255,255,255,0.75);font-size:0.88rem;">
                <?= e($heroMovie['genres']) ?> • <?= (int)$heroMovie['duration_min'] ?> Menit • <?= e($heroMovie['age_rating']) ?>
            </span>
        </div>
    </div>
    <div>
        <img src="<?= e(posterUrl($heroMovie['poster'])) ?>" alt="<?= e($heroMovie['title']) ?>" class="hero-poster">
    </div>
</section>
<?php endif; ?>

<div class="section-header">
    <h2>Sedang Tayang</h2>
    <a href="movies.php?status=now_showing" class="btn btn-outline btn-sm">Lihat Semua</a>
</div>

<div class="movie-grid">
    <?php foreach ($nowShowing as $m): ?>
        <article class="movie-card">
            <a href="movie.php?id=<?= (int)$m['id'] ?>" class="movie-poster-wrap">
                <img src="<?= e(posterUrl($m['poster'])) ?>" alt="<?= e($m['title']) ?>" class="movie-poster">
                <span class="age-badge"><?= e($m['age_rating']) ?></span>
            </a>
            <div class="movie-body">
                <h3 class="movie-title"><a href="movie.php?id=<?= (int)$m['id'] ?>"><?= e($m['title']) ?></a></h3>
                <div class="movie-meta">
                    <span><?= e($m['genres'] ?: '-') ?></span>
                    <span class="mono"><?= (int)$m['duration_min'] ?>m</span>
                </div>
                <a href="movie.php?id=<?= (int)$m['id'] ?>" class="btn btn-primary btn-sm" style="margin-top:auto;">Pilih Jadwal</a>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php if (!empty($comingSoon)): ?>
<div class="section-header">
    <h2>Segera Hadir</h2>
    <a href="movies.php?status=coming_soon" class="btn btn-outline btn-sm">Lihat Semua</a>
</div>

<div class="movie-grid">
    <?php foreach ($comingSoon as $m): ?>
        <article class="movie-card">
            <a href="movie.php?id=<?= (int)$m['id'] ?>" class="movie-poster-wrap">
                <img src="<?= e(posterUrl($m['poster'])) ?>" alt="<?= e($m['title']) ?>" class="movie-poster">
                <span class="age-badge"><?= e($m['age_rating']) ?></span>
            </a>
            <div class="movie-body">
                <h3 class="movie-title"><a href="movie.php?id=<?= (int)$m['id'] ?>"><?= e($m['title']) ?></a></h3>
                <div class="movie-meta">
                    <span><?= e($m['genres'] ?: '-') ?></span>
                    <span class="mono"><?= (int)$m['duration_min'] ?>m</span>
                </div>
                <a href="movie.php?id=<?= (int)$m['id'] ?>" class="btn btn-outline btn-sm" style="margin-top:auto;">Detail Film</a>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
