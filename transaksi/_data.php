<?php
// Data & validasi bersama untuk transaksi/tambah.php dan transaksi/edit.php.

// Pilihan kategori (datalist) dan akun dana milik grup.
function opsi_form_transaksi($pdo, $id_grup)
{
    $stmt = $pdo->prepare("SELECT nama_kategori FROM kategori WHERE id_grup = ? ORDER BY nama_kategori");
    $stmt->execute([$id_grup]);
    $kategori = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT id, nama_akun FROM akun_dana WHERE id_grup = ? ORDER BY nama_akun");
    $stmt->execute([$id_grup]);
    $akun = $stmt->fetchAll();

    return [$kategori, $akun];
}

// Baca & validasi POST form transaksi. Mengembalikan null bila ada isian yang tidak valid.
// Akun dana harus salah satu akun grup ini, supaya id dari grup lain tidak bisa disisipkan.
function input_transaksi($akun)
{
    $input = [
        'kategori' => trim($_POST['kategori'] ?? ''),
        'akun' => (int) ($_POST['id_akun_dana'] ?? 0),
        'jenis' => $_POST['jenis'] ?? '',
        'jumlah' => (float) ($_POST['jumlah'] ?? 0),
        'tanggal' => $_POST['tanggal_transaksi'] ?? '',
        'deskripsi' => trim($_POST['deskripsi'] ?? ''),
    ];

    $akun_valid = in_array($input['akun'], array_map('intval', array_column($akun, 'id')), true);

    if ($input['kategori'] === '' || !$akun_valid || !in_array($input['jenis'], ['Pemasukan', 'Pengeluaran'], true)
        || $input['jumlah'] <= 0 || $input['tanggal'] === '') {
        return null;
    }

    return $input;
}
