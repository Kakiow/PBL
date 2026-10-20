<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/_data.php';

$page_title = 'Edit transaksi';
$page_css = ['transaksi.css', 'transaksi-jenis.css', 'transaksi-fields.css', 'transaksi-preview.css'];
$page_js = ['form.js', 'transaksi.js'];
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

[$kategori, $akun] = opsi_form_transaksi($pdo, $id_grup);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = input_transaksi($akun);

    if ($in === null) {
        flash('error', 'Semua data transaksi wajib diisi.');
    } else {
        $id_kategori = kategori_id_untuk($pdo, $id_grup, $in['kategori'], $in['jenis']);
        $stmt = $pdo->prepare("UPDATE transaksi SET id_kategori = ?, id_akun_dana = ?, jenis = ?, jumlah = ?, tanggal_transaksi = ?, deskripsi = ? WHERE id = ? AND id_grup = ?");
        $stmt->execute([$id_kategori, $in['akun'], $in['jenis'], $in['jumlah'], $in['tanggal'], $in['deskripsi'], $id, $id_grup]);
        flash('success', 'Transaksi berhasil diperbarui.');
        redirect('index.php');
    }
}

$mode = 'edit';
$judul = 'Edit transaksi';
$deskripsi = 'Perbarui rincian transaksi lalu simpan kembali.';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/_form.php';
include __DIR__ . '/../includes/footer.php';
