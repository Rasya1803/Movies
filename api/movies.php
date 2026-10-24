<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
$genreId = (int)($_GET['genre'] ?? 0);
$status = trim($_GET['status'] ?? '');

$sql = "
    SELECT m.*, GROUP_CONCAT(g.name SEPARATOR ', ') AS genres
    FROM movies m
    LEFT JOIN movie_genres mg ON mg.movie_id = m.id
    LEFT JOIN genres g ON g.id = mg.genre_id
    WHERE m.status != 'archived'
";
$params = [];

if ($q !== '') {
    $sql .= " AND (m.title LIKE ? OR m.director LIKE ? OR m.cast_list LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($status !== '' && in_array($status, ['now_showing', 'coming_soon'], true)) {
    $sql .= " AND m.status = ?";
    $params[] = $status;
}

if ($genreId > 0) {
    $sql .= " AND EXISTS (SELECT 1 FROM movie_genres mg2 WHERE mg2.movie_id = m.id AND mg2.genre_id = ?)";
    $params[] = $genreId;
}

$sql .= " GROUP BY m.id ORDER BY m.status ASC, m.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movies = $stmt->fetchAll();

foreach ($movies as &$m) {
    $m['poster_url'] = posterUrl($m['poster']);
}

echo json_encode($movies);
