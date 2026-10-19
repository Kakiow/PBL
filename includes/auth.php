<?php

// Bootstrap halaman modul yang wajib login: sesi + koneksi + helper + guard.
// Cukup `require_once __DIR__ . '/../includes/auth.php';` di awal file modul.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/helpers.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}
