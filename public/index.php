<?php
$pageTitle = 'Beranda';
require_once __DIR__ . '/../includes/layout/header.php';

// Ambil 1 film unggulan untuk Hero beserta telemetri rating & jumlah jadwal aktif
$heroStmt = $pdo->query("
    SELECT m.*,
           GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS genres,
           (SELECT COUNT(*) FROM showtimes s WHERE s.movie_id = m.id AND s.status = 'scheduled') AS showtime_count,
           (SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.movie_id = m.id) AS avg_rating
    FROM movies m
    LEFT JOIN movie_genres mg ON mg.movie_id = m.id
    LEFT JOIN genres g ON g.id = mg.genre_id
    WHERE m.status = 'now_showing'
    GROUP BY m.id
    ORDER BY m.id ASC
    LIMIT 1
");
$heroMovie = $heroStmt->fetch();

// Sedang Tayang (dibatasi 8 film untuk menjaga kepadatan beranda)
$nowStmt = $pdo->query("
    SELECT m.*,
           GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS genres,
           (SELECT COUNT(*) FROM showtimes s WHERE s.movie_id = m.id AND s.status = 'scheduled') AS showtime_count,
           (SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.movie_id = m.id) AS avg_rating
    FROM movies m
    LEFT JOIN movie_genres mg ON mg.movie_id = m.id
    LEFT JOIN genres g ON g.id = mg.genre_id
    WHERE m.status = 'now_showing'
    GROUP BY m.id
    ORDER BY m.id ASC
    LIMIT 8
");
$nowShowing = $nowStmt->fetchAll();

// Segera Hadir (dibatasi 8 film terdekat)
$comingStmt = $pdo->query("
    SELECT m.*,
           GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') AS genres
    FROM movies m
    LEFT JOIN movie_genres mg ON mg.movie_id = m.id
    LEFT JOIN genres g ON g.id = mg.genre_id
    WHERE m.status = 'coming_soon'
    GROUP BY m.id
    ORDER BY m.release_date ASC
    LIMIT 8
");
$comingSoon = $comingStmt->fetchAll();
?>

<?php if ($heroMovie): ?>
<section class="hero">
    <div>
        <span class="badge badge-warning hero-badge">SEDANG TAYANG HARI INI</span>
        <h1><?= e($heroMovie['title']) ?></h1>
        <p><?= e($heroMovie['synopsis']) ?></p>
        <div class="hero-actions">
            <a href="movie.php?id=<?= (int)$heroMovie['id'] ?>" class="btn btn-primary">Pesan Tiket Sekarang</a>
            <a href="movies.php" class="btn btn-outline-light">Semua Film</a>
            <span class="mono hero-meta">
                <?= e($heroMovie['genres']) ?> • <?= (int)$heroMovie['duration_min'] ?> Menit • <?= e($heroMovie['age_rating']) ?>
                <?php if (!empty($heroMovie['avg_rating'])): ?>
                    • ★ <?= number_format((float)$heroMovie['avg_rating'], 1) ?>
                <?php endif; ?>
                • <?= (int)$heroMovie['showtime_count'] ?> Jadwal Aktif
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

<?php if (empty($nowShowing)): ?>
<div class="card empty-state-card">
    <h3>Belum Ada Penayangan Aktif Saat Ini</h3>
    <p class="text-muted">Jadwal penayangan hari ini sedang diperbarui oleh tim operasional kami. Silakan jelajahi katalog lengkap atau daftar film yang segera tayang.</p>
    <a href="movies.php" class="btn btn-primary btn-sm">Jelajahi Katalog Film</a>
</div>
<?php else: ?>
<div class="movie-grid">
    <?php foreach ($nowShowing as $m): ?>
        <article class="movie-card">
            <a href="movie.php?id=<?= (int)$m['id'] ?>" class="movie-poster-wrap" tabindex="-1" aria-hidden="true">
                <img src="<?= e(posterUrl($m['poster'])) ?>" alt="<?= e($m['title']) ?>" class="movie-poster" loading="lazy">
                <?php if (!empty($m['avg_rating'])): ?>
                    <span class="rating-badge mono">★ <?= number_format((float)$m['avg_rating'], 1) ?></span>
                <?php endif; ?>
                <span class="age-badge"><?= e($m['age_rating']) ?></span>
            </a>
            <div class="movie-body">
                <h3 class="movie-title"><a href="movie.php?id=<?= (int)$m['id'] ?>"><?= e($m['title']) ?></a></h3>
                <div class="movie-meta">
                    <span><?= e($m['genres'] ?: '-') ?></span>
                    <span class="mono"><?= (int)$m['duration_min'] ?>m</span>
                </div>
                <div class="movie-telemetry mono">
                    <span><?= (int)$m['showtime_count'] > 0 ? (int)$m['showtime_count'] . ' Jadwal Tersedia' : 'Cek Jadwal' ?></span>
                </div>
                <a href="movie.php?id=<?= (int)$m['id'] ?>" class="btn btn-primary btn-sm">Pilih Jadwal</a>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($comingSoon)): ?>
<div class="section-header">
    <h2>Segera Hadir</h2>
    <a href="movies.php?status=coming_soon" class="btn btn-outline btn-sm">Lihat Semua</a>
</div>

<div class="movie-grid">
    <?php foreach ($comingSoon as $m): ?>
        <article class="movie-card">
            <a href="movie.php?id=<?= (int)$m['id'] ?>" class="movie-poster-wrap" tabindex="-1" aria-hidden="true">
                <img src="<?= e(posterUrl($m['poster'])) ?>" alt="<?= e($m['title']) ?>" class="movie-poster" loading="lazy">
                <span class="age-badge"><?= e($m['age_rating']) ?></span>
            </a>
            <div class="movie-body">
                <h3 class="movie-title"><a href="movie.php?id=<?= (int)$m['id'] ?>"><?= e($m['title']) ?></a></h3>
                <div class="movie-meta">
                    <span><?= e($m['genres'] ?: '-') ?></span>
                    <span class="mono"><?= (int)$m['duration_min'] ?>m</span>
                </div>
                <div class="movie-telemetry mono">
                    <span>Tayang: <?= !empty($m['release_date']) ? e(date('d M Y', strtotime($m['release_date']))) : 'Segera' ?></span>
                </div>
                <a href="movie.php?id=<?= (int)$m['id'] ?>" class="btn btn-outline btn-sm">Detail Film</a>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
