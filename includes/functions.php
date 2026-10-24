<?php
require_once __DIR__ . '/auth.php';

function e(?string $str): string
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function rupiah(int|float $amount): string
{
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

function posterUrl(?string $poster): string
{
    if (!$poster) {
        return baseUrl('uploads/poster-1.svg');
    }
    if (str_starts_with($poster, 'http://') || str_starts_with($poster, 'https://')) {
        return $poster;
    }
    return baseUrl('uploads/' . $poster);
}

function generateBookingCode(): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $random = '';
    for ($i = 0; $i < 6; $i++) {
        $random .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return 'CV-' . $random;
}

/**
 * Upload poster film ke folder /uploads
 */
function uploadPoster(array $file): ?string
{
    if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts, true)) {
        return null;
    }

    $uploadDir = dirname(__DIR__) . '/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $filename = 'poster-' . time() . '-' . random_int(100, 999) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return $filename;
    }

    return null;
}

/**
 * Generate kursi otomatis saat studio dibuat atau diubah ukurannya
 */
function generateStudioSeats(PDO $pdo, int $studioId, int $rows, int $cols, string $type): void
{
    $del = $pdo->prepare('DELETE FROM seats WHERE studio_id = ?');
    $del->execute([$studioId]);

    $ins = $pdo->prepare('INSERT INTO seats (studio_id, row_label, seat_number, seat_type) VALUES (?, ?, ?, ?)');
    for ($r = 0; $r < $rows; $r++) {
        $rowLabel = chr(65 + $r); // A, B, C, ...
        for ($c = 1; $c <= $cols; $c++) {
            $ins->execute([$studioId, $rowLabel, $c, $type]);
        }
    }
}
