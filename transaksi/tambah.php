<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Tambah Transaksi';
$page_css = ['transaksi.css', 'transaksi-jenis.css', 'transaksi-fields.css', 'transaksi-preview.css'];
$page_js = ['transaksi.js'];
$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
$role = cek_role($pdo, $id_grup, akses_menu()['transaksi']);

if (!boleh_kelola_transaksi($role)) {
    flash('error', 'Hanya Admin dan Bendahara yang dapat mencatat transaksi.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kategori = trim($_POST['kategori'] ?? '');
    $akun = (int) ($_POST['id_akun_dana'] ?? 0);
    $jenis = $_POST['jenis'] ?? '';
    $jumlah = (float) ($_POST['jumlah'] ?? 0);
    $tanggal = $_POST['tanggal_transaksi'] ?? '';
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($nama_kategori === '' || !$akun || !in_array($jenis, ['Pemasukan', 'Pengeluaran']) || $jumlah <= 0 || $tanggal === '') {
        flash('error', 'Semua data transaksi wajib diisi.');
    } else {
        $id_kategori = kategori_id_untuk($pdo, $id_grup, $nama_kategori, $jenis);
        $stmt = $pdo->prepare("INSERT INTO transaksi (id_grup,id_pengguna,id_kategori,id_akun_dana,jenis,jumlah,tanggal_transaksi,deskripsi) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$id_grup, $_SESSION['user']['id'], $id_kategori, $akun, $jenis, $jumlah, $tanggal, $deskripsi]);
        flash('success', 'Transaksi berhasil ditambahkan.');
        redirect('index.php');
    }
}

$stmt = $pdo->prepare("SELECT nama_kategori FROM kategori WHERE id_grup = ? ORDER BY nama_kategori");
$stmt->execute([$id_grup]);
$kategori = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM akun_dana WHERE id_grup = ? ORDER BY nama_akun");
$stmt->execute([$id_grup]);
$akun = $stmt->fetchAll();

$mode = 'tambah';
$row = [];
$judul = 'Tambah Transaksi';
$deskripsi = 'Catat pemasukan atau pengeluaran baru dan pantau ringkasannya secara langsung.';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/_form.php';
include __DIR__ . '/../includes/footer.php';
