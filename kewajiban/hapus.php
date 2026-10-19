<?php
require_once __DIR__ . '/../includes/auth.php';

// Hanya menerima POST dari tombol Hapus di detail.php (konfirmasi via data-konfirmasi).
$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
$role = cek_role($pdo, $id_grup, akses_menu()['kewajiban']);
$id = (int) ($_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

if (!peran_pengelola($role)) {
    flash('error', 'Hanya Admin dan Bendahara yang dapat menghapus tagihan.');
    redirect('index.php');
}

$judul = false;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT judul FROM kewajiban_pembayaran WHERE id = ? AND id_grup = ? FOR UPDATE");
    $stmt->execute([$id, $id_grup]);
    $judul = $stmt->fetchColumn();

    if ($judul === false) {
        throw new RuntimeException('Tagihan tidak ditemukan.');
    }

    // Pembayaran yang sudah disetujui sudah tercatat sebagai pemasukan kas — tagihannya tidak boleh hilang.
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) FILTER (WHERE p.status = 'Disetujui') AS disetujui,
            COALESCE(array_to_json(array_agg(p.berkas_bukti) FILTER (WHERE p.berkas_bukti <> '')), '[]') AS berkas
        FROM pembayaran p
        JOIN anggota_kewajiban ak ON ak.id = p.id_anggota_kewajiban
        WHERE ak.id_kewajiban = ?
    ");
    $stmt->execute([$id]);
    $info = $stmt->fetch();

    if ((int) $info['disetujui'] > 0) {
        throw new RuntimeException('Tagihan tidak dapat dihapus karena sudah ada pembayaran yang disetujui. Ubah tagihan jika ada data yang salah.');
    }

    // anggota_kewajiban & pembayaran ikut terhapus lewat ON DELETE CASCADE.
    $stmt = $pdo->prepare("DELETE FROM kewajiban_pembayaran WHERE id = ? AND id_grup = ?");
    $stmt->execute([$id, $id_grup]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    flash('error', $e instanceof RuntimeException ? $e->getMessage() : 'Tagihan gagal dihapus.');
    redirect($judul === false ? 'index.php' : 'detail.php?id=' . $id);
}

// File bukti dihapus setelah commit, supaya tidak hilang bila transaksi DB gagal.
foreach (json_decode($info['berkas'], true) ?: [] as $nama) {
    if (!hapus_bukti($nama)) {
        error_log('Gagal menghapus bukti pembayaran tagihan ' . $id . ': ' . $nama);
    }
}

flash('success', 'Tagihan ' . $judul . ' berhasil dihapus.');
redirect('index.php');
