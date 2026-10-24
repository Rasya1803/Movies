<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole(['admin']);

$genres = $pdo->query("SELECT * FROM genres ORDER BY name ASC")->fetchAll();
$editMovie = null;
$editGenreIds = [];

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$editId]);
    $editMovie = $stmt->fetch();

    if ($editMovie) {
        $gStmt = $pdo->prepare("SELECT genre_id FROM movie_genres WHERE movie_id = ?");
        $gStmt->execute([$editId]);
        $editGenreIds = $gStmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_movie') {
        $id = (int)($_POST['movie_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $synopsis = trim($_POST['synopsis'] ?? '');
        $duration = max(1, (int)($_POST['duration_min'] ?? 120));
        $ageRating = $_POST['age_rating'] ?? 'R13+';
        $language = trim($_POST['language'] ?? 'Indonesia');
        $director = trim($_POST['director'] ?? '');
        $castList = trim($_POST['cast_list'] ?? '');
        $status = $_POST['status'] ?? 'now_showing';
        $selectedGenres = array_map('intval', $_POST['genres'] ?? []);

        $uploadedPoster = uploadPoster($_FILES['poster'] ?? []);

        if ($id > 0) {
            if ($uploadedPoster) {
                $upd = $pdo->prepare("
                    UPDATE movies
                    SET title=?, synopsis=?, duration_min=?, age_rating=?, language=?, director=?, cast_list=?, poster=?, status=?
                    WHERE id=?
                ");
                $upd->execute([$title, $synopsis, $duration, $ageRating, $language, $director, $castList, $uploadedPoster, $status, $id]);
            } else {
                $upd = $pdo->prepare("
                    UPDATE movies
                    SET title=?, synopsis=?, duration_min=?, age_rating=?, language=?, director=?, cast_list=?, status=?
                    WHERE id=?
                ");
                $upd->execute([$title, $synopsis, $duration, $ageRating, $language, $director, $castList, $status, $id]);
            }
            $movieId = $id;
            setFlash('success', 'Data film berhasil diperbarui.');
        } else {
            $posterName = $uploadedPoster ?: 'poster-1.svg';
            $ins = $pdo->prepare("
                INSERT INTO movies (title, synopsis, duration_min, release_date, age_rating, language, director, cast_list, poster, status)
                VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?)
            ");
            $ins->execute([$title, $synopsis, $duration, $ageRating, $language, $director, $castList, $posterName, $status]);
            $movieId = (int)$pdo->lastInsertId();
            setFlash('success', 'Film baru berhasil ditambahkan dan langsung tampil di katalog.');
        }

        $pdo->prepare("DELETE FROM movie_genres WHERE movie_id = ?")->execute([$movieId]);
        $insG = $pdo->prepare("INSERT INTO movie_genres (movie_id, genre_id) VALUES (?, ?)");
        foreach ($selectedGenres as $gid) {
            $insG->execute([$movieId, $gid]);
        }

        header('Location: movies.php');
        exit;
    }

    if ($action === 'delete_movie') {
        $delId = (int)($_POST['movie_id'] ?? 0);
        $pdo->prepare("DELETE FROM movies WHERE id = ?")->execute([$delId]);
        setFlash('info', 'Film berhasil dihapus.');
        header('Location: movies.php');
        exit;
    }
}

$movies = $pdo->query("
    SELECT m.*, GROUP_CONCAT(g.name SEPARATOR ', ') AS genres
    FROM movies m
    LEFT JOIN movie_genres mg ON mg.movie_id = m.id
    LEFT JOIN genres g ON g.id = mg.genre_id
    GROUP BY m.id
    ORDER BY m.id DESC
")->fetchAll();

$pageTitle = 'Kelola Film';
require_once __DIR__ . '/../includes/layout/header.php';
?>

<div class="subnav">
    <a href="index.php">Dashboard</a>
    <a href="movies.php" class="active">Kelola Film</a>
    <a href="studios.php">Kelola Studio &amp; Kursi</a>
    <a href="promos.php">Kelola Promo</a>
    <a href="users.php">Kelola Akun</a>
    <a href="reports.php">Laporan &amp; CSV</a>
</div>

<div class="card" style="margin-bottom:28px;">
    <h2 style="margin-bottom:16px;"><?= $editMovie ? 'Edit Film: ' . e($editMovie['title']) : 'Tambah Film Baru' ?></h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save_movie">
        <input type="hidden" name="movie_id" value="<?= (int)($editMovie['id'] ?? 0) ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="title">Judul Film</label>
                <input type="text" id="title" name="title" value="<?= e($editMovie['title'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="director">Sutradara</label>
                <input type="text" id="director" name="director" value="<?= e($editMovie['director'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="duration_min">Durasi (Menit)</label>
                <input type="number" id="duration_min" name="duration_min" value="<?= (int)($editMovie['duration_min'] ?? 120) ?>" required>
            </div>
            <div class="form-group">
                <label for="age_rating">Batas Usia</label>
                <select name="age_rating" id="age_rating">
                    <?php foreach (['SU', 'R13+', 'D17+', 'D21+'] as $ar): ?>
                        <option value="<?= $ar ?>" <?= ($editMovie['age_rating'] ?? 'R13+') === $ar ? 'selected' : '' ?>><?= $ar ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="language">Bahasa</label>
                <input type="text" id="language" name="language" value="<?= e($editMovie['language'] ?? 'Indonesia') ?>">
            </div>
            <div class="form-group">
                <label for="cast_list">Pemeran Utama</label>
                <input type="text" id="cast_list" name="cast_list" value="<?= e($editMovie['cast_list'] ?? '') ?>" placeholder="Pisahkan dengan koma">
            </div>
            <div class="form-group">
                <label for="status">Status Tayang</label>
                <select name="status" id="status">
                    <option value="now_showing" <?= ($editMovie['status'] ?? '') === 'now_showing' ? 'selected' : '' ?>>Sedang Tayang</option>
                    <option value="coming_soon" <?= ($editMovie['status'] ?? '') === 'coming_soon' ? 'selected' : '' ?>>Segera Hadir</option>
                    <option value="archived" <?= ($editMovie['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Arsip</option>
                </select>
            </div>
            <div class="form-group">
                <label for="poster">Upload Poster (JPG/PNG/SVG)</label>
                <input type="file" id="poster" name="poster" accept=".jpg,.jpeg,.png,.webp,.svg" class="form-control">
            </div>
        </div>

        <div class="form-group">
            <label>Pilih Genre</label>
            <div style="display:flex;gap:14px;flex-wrap:wrap;">
                <?php foreach ($genres as $g): ?>
                    <label style="display:inline-flex;align-items:center;gap:6px;font-weight:400;">
                        <input type="checkbox" name="genres[]" value="<?= (int)$g['id'] ?>"
                            <?= in_array($g['id'], $editGenreIds) ? 'checked' : '' ?>>
                        <?= e($g['name']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="synopsis">Sinopsis</label>
            <textarea id="synopsis" name="synopsis" rows="3" required><?= e($editMovie['synopsis'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:10px;">
            <button type="submit" class="btn btn-primary"><?= $editMovie ? 'Simpan Perubahan' : '+ Tambah Film' ?></button>
            <?php if ($editMovie): ?>
                <a href="movies.php" class="btn btn-outline">Batal</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card table-wrap">
    <h3 style="margin-bottom:14px;">Daftar Semua Film (<?= count($movies) ?>)</h3>
    <table>
        <thead>
            <tr>
                <th>Poster</th>
                <th>Judul &amp; Genre</th>
                <th>Durasi</th>
                <th>Rating</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movies as $m): ?>
                <tr>
                    <td>
                        <img src="<?= e(posterUrl($m['poster'])) ?>" alt="<?= e($m['title']) ?>" style="width:44px;height:64px;object-fit:cover;border-radius:6px;">
                    </td>
                    <td>
                        <strong><?= e($m['title']) ?></strong><br>
                        <span class="text-muted"><?= e($m['genres'] ?: '-') ?></span>
                    </td>
                    <td class="mono"><?= (int)$m['duration_min'] ?>m</td>
                    <td class="mono"><?= e($m['age_rating']) ?></td>
                    <td><span class="badge badge-info"><?= e($m['status']) ?></span></td>
                    <td>
                        <div style="display:flex;gap:8px;">
                            <a href="movies.php?edit=<?= (int)$m['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                            <form method="POST" onsubmit="return confirm('Hapus film ini?');">
                                <input type="hidden" name="action" value="delete_movie">
                                <input type="hidden" name="movie_id" value="<?= (int)$m['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm text-danger">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
