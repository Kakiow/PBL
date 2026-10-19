<?php

// Skrip ini hanya untuk CLI/cron. Akses lewat browser ditolak.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Akses ditolak.');
}

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';

try {
    $jumlah = bersihkan_bukti_kedaluwarsa($pdo);
    echo '[' . date('Y-m-d H:i:s') . '] Bukti kedaluwarsa dibersihkan: ' . $jumlah . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Gagal membersihkan bukti: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
