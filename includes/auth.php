<?php

// Bootstrap halaman modul yang wajib login: sesi + koneksi + helper + guard.
// Cukup `require_once __DIR__ . '/../includes/auth.php';` di awal file modul.

require_once __DIR__ . '/sesi.php';
mulai_sesi();

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/helpers.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}

if (!sesi_stempel_sah($pdo)) {
    sesi_gugurkan('../auth/login.php');
}
