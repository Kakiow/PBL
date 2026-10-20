<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Buat tagihan';
$page_css = ['kewajiban.css'];
$page_js = ['form.js', 'kewajiban.js'];
$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
$role = cek_role($pdo, $id_grup, akses_menu()['kewajiban']);

if (!peran_pengelola($role)) {
    flash('error', 'Hanya Admin dan Bendahara yang dapat membuat tagihan.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $jumlah = (float) ($_POST['jumlah'] ?? 0);
    $tenggat = (string) ($_POST['tenggat_waktu'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($judul === '' || $jumlah <= 0 || !tanggal_valid($tenggat)) {
        flash('error', 'Judul, jumlah, dan tenggat wajib diisi.');
    } elseif ($tenggat < date('Y-m-d')) {
        flash('error', 'Tenggat tidak boleh sebelum hari ini.');
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO kewajiban_pembayaran (id_grup,judul,jumlah,tenggat_waktu,deskripsi,dibuat_oleh) VALUES (?,?,?,?,?,?) RETURNING id");
            $stmt->execute([$id_grup, $judul, $jumlah, $tenggat, $deskripsi, $_SESSION['user']['id']]);
            tagihkan_ke_anggota($pdo, $stmt->fetchColumn(), $id_grup);
            $pdo->commit();
            flash('success', 'Tagihan berhasil dibuat.');
            redirect('index.php');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', 'Gagal membuat tagihan.');
        }
    }
}

$mode = 'tambah';
$row = [];
$tenggat_min = date('Y-m-d');
$judul = 'Buat tagihan';
$deskripsi_halaman = 'Tagihan ini otomatis dibebankan ke seluruh anggota grup.';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/_form.php';
include __DIR__ . '/../includes/footer.php';
