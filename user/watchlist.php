<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_movie_id'])) {
    $rmId = (int)$_POST['remove_movie_id'];
    $del = $pdo->prepare('DELETE FROM watchlists WHERE user_id = ? AND movie_id = ?');
    $del->execute([$user['id'], $rmId]);
    setFlash('info', 'Film dihapus dari Watchlist.');
    header('Location: watchlist.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT m.*, GROUP_CONCAT(g.name SEPARATOR ', ') AS genres
    FROM watchlists w
    JOIN movies m ON m.id = w.movie_id
    LEFT JOIN movie_genres mg ON mg.movie_id = m.id
    LEFT JOIN genres g ON g.id = mg.genre_id
    WHERE w.user_id = ?
    GROUP BY m.id
    ORDER BY w.created_at DESC
");
$stmt->execute([$user['id']]);
$movies = $stmt->fetchAll();

$pageTitle = 'Watchlist Saya';
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="section-header">
    <h1>Watchlist Saya</h1>
    <a href="<?= baseUrl('public/movies.php') ?>" class="btn btn-outline btn-sm">Tambah Film Lain</a>
</div>

<?php if (empty($movies)): ?>
    <div class="card" style="text-align:center;">
        <p class="text-muted" style="margin-bottom:16px;">Belum ada film yang disimpan di Watchlist Anda.</p>
        <a href="<?= baseUrl('public/movies.php') ?>" class="btn btn-primary">Lihat Katalog Film</a>
    </div>
<?php else: ?>
    <div class="movie-grid">
        <?php foreach ($movies as $m): ?>
            <article class="movie-card">
                <a href="<?= baseUrl('public/movie.php?id=' . (int)$m['id']) ?>" class="movie-poster-wrap">
                    <img src="<?= e(posterUrl($m['poster'])) ?>" alt="<?= e($m['title']) ?>" class="movie-poster">
                    <span class="age-badge"><?= e($m['age_rating']) ?></span>
                </a>
                <div class="movie-body">
                    <h3 class="movie-title">
                        <a href="<?= baseUrl('public/movie.php?id=' . (int)$m['id']) ?>"><?= e($m['title']) ?></a>
                    </h3>
                    <div class="movie-meta">
                        <span><?= e($m['genres'] ?: '-') ?></span>
                        <span class="mono"><?= (int)$m['duration_min'] ?>m</span>
                    </div>
                    <div style="display:flex;gap:8px;margin-top:auto;">
                        <a href="<?= baseUrl('public/movie.php?id=' . (int)$m['id']) ?>" class="btn btn-primary btn-sm" style="flex:1;">Detail</a>
                        <form method="POST">
                            <input type="hidden" name="remove_movie_id" value="<?= (int)$m['id'] ?>">
                            <button type="submit" class="btn btn-outline btn-sm" aria-label="Hapus dari watchlist">Hapus</button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
