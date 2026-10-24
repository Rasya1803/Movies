<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$movieId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT m.*, GROUP_CONCAT(g.name SEPARATOR ', ') AS genres
    FROM movies m
    LEFT JOIN movie_genres mg ON mg.movie_id = m.id
    LEFT JOIN genres g ON g.id = mg.genre_id
    WHERE m.id = ?
    GROUP BY m.id
");
$stmt->execute([$movieId]);
$movie = $stmt->fetch();

if (!$movie) {
    setFlash('danger', 'Film tidak ditemukan.');
    header('Location: movies.php');
    exit;
}

$user = currentUser();

// Handle Toggle Watchlist & Submit Review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_watchlist') {
        $chk = $pdo->prepare('SELECT 1 FROM watchlists WHERE user_id = ? AND movie_id = ?');
        $chk->execute([$user['id'], $movieId]);
        if ($chk->fetchColumn()) {
            $del = $pdo->prepare('DELETE FROM watchlists WHERE user_id = ? AND movie_id = ?');
            $del->execute([$user['id'], $movieId]);
            setFlash('info', 'Film dihapus dari Watchlist.');
        } else {
            $ins = $pdo->prepare('INSERT INTO watchlists (user_id, movie_id) VALUES (?, ?)');
            $ins->execute([$user['id'], $movieId]);
            setFlash('success', 'Film disimpan ke Watchlist Anda!');
        }
        header('Location: movie.php?id=' . $movieId);
        exit;
    }

    if ($action === 'submit_review') {
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $comment = trim($_POST['comment'] ?? '');
        if ($comment !== '') {
            $rev = $pdo->prepare("
                INSERT INTO reviews (user_id, movie_id, rating, comment)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), created_at = NOW()
            ");
            $rev->execute([$user['id'], $movieId, $rating, $comment]);
            setFlash('success', 'Ulasan Anda berhasil disimpan.');
        }
        header('Location: movie.php?id=' . $movieId);
        exit;
    }
}

$inWatchlist = false;
if ($user) {
    $wStmt = $pdo->prepare('SELECT 1 FROM watchlists WHERE user_id = ? AND movie_id = ?');
    $wStmt->execute([$user['id'], $movieId]);
    $inWatchlist = (bool)$wStmt->fetchColumn();
}

// Ambil jadwal tayang aktif
$stStmt = $pdo->prepare("
    SELECT s.*, st.name AS studio_name, st.type AS studio_type
    FROM showtimes s
    JOIN studios st ON st.id = s.studio_id
    WHERE s.movie_id = ? AND s.status = 'scheduled'
    ORDER BY s.start_time ASC
");
$stStmt->execute([$movieId]);
$showtimes = $stStmt->fetchAll();

// Kelompokkan jadwal berdasarkan tanggal
$groupedShowtimes = [];
foreach ($showtimes as $sh) {
    $dateKey = date('Y-m-d', strtotime($sh['start_time']));
    $groupedShowtimes[$dateKey][] = $sh;
}

// Ambil ulasan
$revStmt = $pdo->prepare("
    SELECT r.*, u.name AS user_name
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    WHERE r.movie_id = ?
    ORDER BY r.created_at DESC
");
$revStmt->execute([$movieId]);
$reviews = $revStmt->fetchAll();

$avgRating = 0;
if ($reviews) {
    $avgRating = round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1);
}

$pageTitle = $movie['title'];
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="detail-grid">
    <div>
        <img src="<?= e(posterUrl($movie['poster'])) ?>" alt="<?= e($movie['title']) ?>" class="detail-poster">
        <?php if ($user): ?>
            <form method="POST" style="margin-top:12px;">
                <input type="hidden" name="action" value="toggle_watchlist">
                <button type="submit" class="btn <?= $inWatchlist ? 'btn-secondary' : 'btn-outline' ?>" style="width:100%;">
                    <?= $inWatchlist ? '✓ Tersimpan di Watchlist' : '+ Simpan ke Watchlist' ?>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div>
        <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;flex-wrap:wrap;">
            <span class="badge badge-info"><?= e($movie['age_rating']) ?></span>
            <span class="badge badge-warning"><?= e($movie['language']) ?></span>
            <span class="mono text-muted"><?= (int)$movie['duration_min'] ?> Menit</span>
            <?php if ($avgRating > 0): ?>
                <span class="badge badge-success">★ <?= $avgRating ?> / 5 (<?= count($reviews) ?> ulasan)</span>
            <?php endif; ?>
        </div>

        <h1 style="margin-bottom:12px;"><?= e($movie['title']) ?></h1>
        <p class="text-muted" style="margin-bottom:16px;">
            <strong>Genre:</strong> <?= e($movie['genres'] ?: '-') ?> &nbsp;|&nbsp;
            <strong>Sutradara:</strong> <?= e($movie['director'] ?: '-') ?> &nbsp;|&nbsp;
            <strong>Pemeran:</strong> <?= e($movie['cast_list'] ?: '-') ?>
        </p>

        <div class="card" style="margin-bottom:24px;">
            <h3 style="margin-bottom:8px;">Sinopsis</h3>
            <p><?= nl2br(e($movie['synopsis'])) ?></p>
        </div>

        <div class="card" style="margin-bottom:24px;">
            <h3 style="margin-bottom:12px;">Pilih Jadwal Tayang</h3>
            <?php if (empty($groupedShowtimes)): ?>
                <p class="text-muted">Belum ada jadwal tayang tersedia untuk film ini.</p>
            <?php else: ?>
                <?php foreach ($groupedShowtimes as $date => $items): ?>
                    <div style="margin-bottom:16px;">
                        <strong class="mono"><?= date('d M Y', strtotime($date)) ?></strong>
                        <div class="showtime-grid">
                            <?php foreach ($items as $sh): ?>
                                <a href="<?= baseUrl('user/seats.php?showtime_id=' . (int)$sh['id']) ?>" class="showtime-slot">
                                    <span class="mono" style="font-size:1.05rem;font-weight:700;">
                                        <?= date('H:i', strtotime($sh['start_time'])) ?> WIB
                                    </span>
                                    <span class="text-muted" style="font-size:0.8rem;">
                                        <?= e($sh['studio_name']) ?> (<?= strtoupper(e($sh['studio_type'])) ?>)
                                    </span>
                                    <span class="mono text-accent" style="font-size:0.85rem;font-weight:600;">
                                        <?= rupiah($sh['price']) ?>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 style="margin-bottom:16px;">Ulasan Penonton (<?= count($reviews) ?>)</h3>

            <?php if ($user): ?>
                <form method="POST" style="margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border);">
                    <input type="hidden" name="action" value="submit_review">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="rating">Rating Bintang</label>
                            <select name="rating" id="rating">
                                <option value="5">★★★★★ (5 - Luar Biasa)</option>
                                <option value="4">★★★★☆ (4 - Bagus)</option>
                                <option value="3">★★★☆☆ (3 - Cukup)</option>
                                <option value="2">★★☆☆☆ (2 - Kurang)</option>
                                <option value="1">★☆☆☆☆ (1 - Buruk)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="comment">Komentar Ulasan</label>
                        <textarea name="comment" id="comment" rows="2" required placeholder="Tulis pendapat Anda tentang film ini..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Kirim Ulasan</button>
                </form>
            <?php endif; ?>

            <?php if (empty($reviews)): ?>
                <p class="text-muted">Belum ada ulasan. Jadilah yang pertama memberi ulasan!</p>
            <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <?php foreach ($reviews as $rv): ?>
                        <div style="padding-bottom:12px;border-bottom:1px solid var(--border);">
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <strong><?= e($rv['user_name']) ?></strong>
                                <span class="text-accent mono"><?= str_repeat('★', (int)$rv['rating']) ?></span>
                            </div>
                            <p style="margin-top:4px;"><?= e($rv['comment']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
