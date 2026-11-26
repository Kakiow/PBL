<?php
require_once __DIR__ . '/../includes/auth.php';

// Hanya pengelola grup (Admin/Bendahara) atau pemilik pembayaran yang boleh mengakses berkas bukti.
$id_user = (int) ($_SESSION['user']['id'] ?? 0);
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(404);
    exit('Bukti pembayaran tidak ditemukan.');
}

$stmt = $pdo->prepare("
    SELECT p.berkas_bukti, ak.id_pengguna, k.id_grup
    FROM pembayaran p
    JOIN tagihan_anggota ak ON ak.id = p.id_tagihan_anggota
    JOIN tagihan k ON k.id = ak.id_tagihan
    WHERE p.id = ?
");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row || (string) $row['berkas_bukti'] === '') {
    http_response_code(404);
    exit('Bukti pembayaran tidak ditemukan.');
}

$role = role_group($pdo, (int) $row['id_grup'], $id_user);
$pemilik = (int) $row['id_pengguna'] === $id_user;

if (!$role || (!peran_pengelola($role) && !$pemilik)) {
    http_response_code(403);
    exit('Anda tidak berhak mengakses bukti pembayaran ini.');
}

$path = path_bukti($row['berkas_bukti']);
if ($path === null || !is_file($path)) {
    http_response_code(404);
    exit('Berkas bukti tidak ditemukan.');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($path);
$diizinkan = ['image/jpeg', 'image/png', 'application/pdf'];

if (!in_array($mime, $diizinkan, true)) {
    http_response_code(403);
    exit('Format berkas tidak valid.');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . basename($path) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');
readfile($path);
exit;
