<?php
$pageTitle = 'Katalog Film';
require_once __DIR__ . '/../includes/layout/header.php';

$genres = $pdo->query("SELECT * FROM genres ORDER BY name ASC")->fetchAll();
$selectedStatus = $_GET['status'] ?? '';
$selectedGenre = (int)($_GET['genre'] ?? 0);
$searchQuery = trim($_GET['q'] ?? '');

$sql = "
    SELECT m.*, GROUP_CONCAT(g.name SEPARATOR ', ') AS genres
    FROM movies m
    LEFT JOIN movie_genres mg ON mg.movie_id = m.id
    LEFT JOIN genres g ON g.id = mg.genre_id
    WHERE m.status != 'archived'
";
$params = [];

if ($searchQuery !== '') {
    $sql .= " AND (m.title LIKE ? OR m.director LIKE ?)";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
}
if (in_array($selectedStatus, ['now_showing', 'coming_soon'], true)) {
    $sql .= " AND m.status = ?";
    $params[] = $selectedStatus;
}
if ($selectedGenre > 0) {
    $sql .= " AND EXISTS (SELECT 1 FROM movie_genres mg2 WHERE mg2.movie_id = m.id AND mg2.genre_id = ?)";
    $params[] = $selectedGenre;
}
$sql .= " GROUP BY m.id ORDER BY m.status ASC, m.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movies = $stmt->fetchAll();
?>

<div class="section-header">
    <h1>Katalog Film</h1>
</div>

<div class="card" style="margin-bottom:24px;">
    <form method="GET" class="form-row" onsubmit="return false;">
        <div class="form-group" style="margin-bottom:0;">
            <label for="liveSearch">Cari Judul / Sutradara</label>
            <input type="text" id="liveSearch" name="q" value="<?= e($searchQuery) ?>" placeholder="Ketik judul film...">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label for="genreFilter">Filter Genre</label>
            <select id="genreFilter" name="genre">
                <option value="">Semua Genre</option>
                <?php foreach ($genres as $g): ?>
                    <option value="<?= (int)$g['id'] ?>" <?= $selectedGenre === (int)$g['id'] ? 'selected' : '' ?>>
                        <?= e($g['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label for="statusFilter">Status Tayang</label>
            <select id="statusFilter" name="status">
                <option value="">Semua Status</option>
                <option value="now_showing" <?= $selectedStatus === 'now_showing' ? 'selected' : '' ?>>Sedang Tayang</option>
                <option value="coming_soon" <?= $selectedStatus === 'coming_soon' ? 'selected' : '' ?>>Segera Hadir</option>
            </select>
        </div>
    </form>
</div>

<div id="catalogGrid" class="movie-grid" data-api-url="<?= e(baseUrl('api/movies.php')) ?>">
    <?php foreach ($movies as $m): ?>
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
                <a href="movie.php?id=<?= (int)$m['id'] ?>" class="btn btn-primary btn-sm" style="margin-top:auto;">Lihat Jadwal</a>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
