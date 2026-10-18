<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Edit Transaksi';
$page_css = ['transaksi.css', 'transaksi-jenis.css', 'transaksi-fields.css', 'transaksi-preview.css'];
$page_js = ['transaksi.js'];
$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
$role = cek_role($pdo, $id_grup, akses_menu()['transaksi']);

if (!boleh_kelola_transaksi($role)) {
    flash('error', 'Hanya Admin dan Bendahara yang dapat mengubah transaksi.');
    redirect('index.php');
}

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT t.*, k.nama_kategori FROM transaksi t JOIN kategori k ON k.id = t.id_kategori WHERE t.id = ? AND t.id_grup = ?");
$stmt->execute([$id, $id_grup]);
$row = $stmt->fetch();

if (!$row) {
    flash('error', 'Transaksi tidak ditemukan.');
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
        $stmt = $pdo->prepare("UPDATE transaksi SET id_kategori = ?, id_akun_dana = ?, jenis = ?, jumlah = ?, tanggal_transaksi = ?, deskripsi = ? WHERE id = ? AND id_grup = ?");
        $stmt->execute([$id_kategori, $akun, $jenis, $jumlah, $tanggal, $deskripsi, $id, $id_grup]);
        flash('success', 'Transaksi berhasil diperbarui.');
        redirect('index.php');
    }
}

$stmt = $pdo->prepare("SELECT nama_kategori FROM kategori WHERE id_grup = ? ORDER BY nama_kategori");
$stmt->execute([$id_grup]);
$kategori = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM akun_dana WHERE id_grup = ? ORDER BY nama_akun");
$stmt->execute([$id_grup]);
$akun = $stmt->fetchAll();

$mode = 'edit';
$judul = 'Edit Transaksi';
$deskripsi = 'Perbarui rincian transaksi lalu simpan kembali.';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/_form.php';
include __DIR__ . '/../includes/footer.php';
