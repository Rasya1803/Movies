<?php
require_once __DIR__ . '/../includes/auth.php';
session_destroy();
session_start();
setFlash('info', 'Anda telah keluar dari akun.');
header('Location: login.php');
exit;
