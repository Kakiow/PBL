<?php
// Data & validasi bersama untuk transaksi/tambah.php dan transaksi/edit.php.

// Pilihan kategori (datalist) milik grup.
function opsi_form_transaksi($pdo, $id_grup)
{
    $stmt = $pdo->prepare("SELECT nama_kategori FROM kategori WHERE id_grup = ? ORDER BY nama_kategori");
    $stmt->execute([$id_grup]);

    return $stmt->fetchAll();
}

// Baca & validasi POST form transaksi. Mengembalikan null bila ada isian yang tidak valid.
function input_transaksi()
{
    $input = [
        'kategori' => trim($_POST['kategori'] ?? ''),
        'jenis' => $_POST['jenis'] ?? '',
        'jumlah' => (float) ($_POST['jumlah'] ?? 0),
        'tanggal' => $_POST['tanggal_transaksi'] ?? '',
        'deskripsi' => trim($_POST['deskripsi'] ?? ''),
    ];

    if ($input['kategori'] === '' || !in_array($input['jenis'], ['Pemasukan', 'Pengeluaran'], true)
        || $input['jumlah'] <= 0 || $input['tanggal'] === '') {
        return null;
    }

    return $input;
}
