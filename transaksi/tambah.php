<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/_data.php';

$page_title = 'Tambah transaksi';
$page_css = ['transaksi.css', 'transaksi-jenis.css', 'transaksi-fields.css', 'transaksi-preview.css'];
$page_js = ['form.js', 'transaksi.js'];
$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
$role = cek_role($pdo, $id_grup, akses_menu()['transaksi']);

if (!boleh_kelola_transaksi($role)) {
    flash('error', 'Hanya Admin dan Bendahara yang dapat mencatat transaksi.');
    redirect('index.php');
}

[$kategori, $akun] = opsi_form_transaksi($pdo, $id_grup);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = input_transaksi($akun);

    if ($in === null) {
        flash('error', 'Semua data transaksi wajib diisi.');
    } else {
        $id_kategori = kategori_id_untuk($pdo, $id_grup, $in['kategori'], $in['jenis']);
        $stmt = $pdo->prepare("INSERT INTO transaksi (id_grup,id_pengguna,id_kategori,id_akun_dana,jenis,jumlah,tanggal_transaksi,deskripsi) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$id_grup, $_SESSION['user']['id'], $id_kategori, $in['akun'], $in['jenis'], $in['jumlah'], $in['tanggal'], $in['deskripsi']]);
        flash('success', 'Transaksi berhasil ditambahkan.');
        redirect('index.php');
    }
}

$mode = 'tambah';
$row = [];
$judul = 'Tambah transaksi';
$deskripsi = 'Catat pemasukan atau pengeluaran baru dan pantau ringkasannya secara langsung.';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/_form.php';
include __DIR__ . '/../includes/footer.php';
