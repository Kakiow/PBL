<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Buat tagihan';
$page_css = ['tagihan.css', 'datepicker.css'];
$page_js = ['form.js', 'datepicker.js', 'tagihan.js'];
$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
$role = cek_role($pdo, $id_grup, akses_menu()['tagihan']);

if (!peran_pengelola($role)) {
    flash('error', 'Hanya Admin dan Bendahara yang dapat membuat tagihan.');
    redirect('index.php');
}

$stmt = $pdo->prepare("SELECT p.id, p.nama, p.email, ag.peran FROM anggota_grup ag JOIN pengguna p ON p.id = ag.id_pengguna WHERE ag.id_grup = ? ORDER BY p.nama");
$stmt->execute([$id_grup]);
$anggota_grup = $stmt->fetchAll();
$id_anggota = array_map(fn ($a) => (int) $a['id'], $anggota_grup);

$row = [];
$mode_sasaran = 'semua';
$sasaran_terpilih = $id_anggota;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $jumlah = (float) ($_POST['jumlah'] ?? 0);
    $tenggat = (string) ($_POST['tenggat_waktu'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $mode_sasaran = ($_POST['mode_sasaran'] ?? '') === 'pilih' ? 'pilih' : 'semua';
    $dipilih = array_values(array_unique(array_map('intval', (array) ($_POST['sasaran'] ?? []))));

    // Isian dikembalikan ke form agar tidak hilang saat validasi gagal.
    $row = [
        'judul' => $judul,
        'jumlah' => $jumlah > 0 ? $jumlah : '',
        'tenggat_waktu' => $tenggat,
        'deskripsi' => $deskripsi,
    ];

    if ($mode_sasaran === 'pilih') {
        $sasaran_terpilih = $dipilih;
    }

    if ($judul === '' || $jumlah <= 0 || !tanggal_valid($tenggat)) {
        flash('error', 'Judul, jumlah, dan tenggat wajib diisi.');
    } elseif ($tenggat < date('Y-m-d')) {
        flash('error', 'Tenggat tidak boleh sebelum hari ini.');
    } elseif ($mode_sasaran === 'pilih' && !$dipilih) {
        flash('error', 'Pilih minimal satu anggota.');
    } elseif ($mode_sasaran === 'pilih' && array_diff($dipilih, $id_anggota)) {
        flash('error', 'Pilihan anggota tidak valid.');
    } else {
        $sasaran = $mode_sasaran === 'semua' ? $id_anggota : $dipilih;
        $sasaran_semua = $mode_sasaran === 'semua';

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO tagihan (id_grup,judul,jumlah,tenggat_waktu,deskripsi,sasaran_semua) VALUES (?,?,?,?,?,?) RETURNING id");
            $stmt->execute([$id_grup, $judul, $jumlah, $tenggat, $deskripsi, $sasaran_semua ? 'true' : 'false']);
            $id_tagihan = $stmt->fetchColumn();
            tagihkan_ke_pengguna($pdo, $id_tagihan, $id_grup, $sasaran);
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
$tenggat_min = date('Y-m-d');
$judul = 'Buat tagihan';
$deskripsi_halaman = 'Pilih sasaran anggota, lalu isi nominal dan tenggat pembayaran.';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/_form.php';
include __DIR__ . '/../includes/footer.php';
