<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Edit tagihan';
$page_css = ['kewajiban.css'];
$page_js = ['form.js', 'kewajiban.js'];
$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
$role = cek_role($pdo, $id_grup, akses_menu()['kewajiban']);

if (!peran_pengelola($role)) {
    flash('error', 'Hanya Admin dan Bendahara yang dapat mengubah tagihan.');
    redirect('index.php');
}

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

// Nominal dikunci begitu ada pembayaran yang dikirim/disetujui, agar tidak beda dengan uang yang sudah masuk.
$stmt = $pdo->prepare("
    SELECT k.*, EXISTS (
        SELECT 1 FROM pembayaran p
        JOIN anggota_kewajiban ak ON ak.id = p.id_anggota_kewajiban
        WHERE ak.id_kewajiban = k.id AND p.status IN ('Menunggu', 'Disetujui')
    ) AS nominal_terkunci
    FROM kewajiban_pembayaran k
    WHERE k.id = ? AND k.id_grup = ?
");
$stmt->execute([$id, $id_grup]);
$row = $stmt->fetch();

if (!$row) {
    flash('error', 'Tagihan tidak ditemukan.');
    redirect('index.php');
}

$nominal_terkunci = (bool) $row['nominal_terkunci'];
// Tenggat lama yang sudah lewat boleh dipertahankan; tenggat baru tidak boleh sebelum hari ini.
$tenggat_min = min(date('Y-m-d'), (string) $row['tenggat_waktu']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $jumlah = $nominal_terkunci ? (float) $row['jumlah'] : (float) ($_POST['jumlah'] ?? 0);
    $tenggat = (string) ($_POST['tenggat_waktu'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($judul === '' || $jumlah <= 0 || !tanggal_valid($tenggat)) {
        flash('error', 'Judul, jumlah, dan tenggat wajib diisi.');
    } elseif ($tenggat < $tenggat_min) {
        flash('error', 'Tenggat tidak boleh sebelum hari ini.');
    } else {
        $stmt = $pdo->prepare("UPDATE kewajiban_pembayaran SET judul = ?, jumlah = ?, tenggat_waktu = ?, deskripsi = ? WHERE id = ? AND id_grup = ?");
        $stmt->execute([$judul, $jumlah, $tenggat, $deskripsi, $id, $id_grup]);
        flash('success', 'Tagihan berhasil diperbarui.');
        redirect('detail.php?id=' . $id);
    }

    redirect('edit.php?id=' . $id);
}

$mode = 'edit';
$judul = 'Edit tagihan';
$deskripsi_halaman = 'Perbarui rincian tagihan. Status pembayaran anggota tidak berubah.';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/_form.php';
include __DIR__ . '/../includes/footer.php';
