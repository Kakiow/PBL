<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/xlsx.php';

$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
$role = cek_role($pdo, $id_grup, akses_menu()['laporan']);
$grup = grup_aktif($pdo);

if (!$grup) {
    http_response_code(400);
    exit('Tidak ada grup aktif.');
}

$mulai = tanggal_valid($_GET['mulai'] ?? '') ? $_GET['mulai'] : date('Y-m-01');
$selesai = tanggal_valid($_GET['selesai'] ?? '') ? $_GET['selesai'] : date('Y-m-d');

$stmt = $pdo->prepare("SELECT t.tanggal_transaksi, k.nama_kategori, t.jenis, t.jumlah, t.deskripsi FROM transaksi t JOIN kategori k ON k.id = t.id_kategori WHERE t.id_grup = ? AND t.tanggal_transaksi BETWEEN ? AND ? ORDER BY t.tanggal_transaksi DESC, t.id DESC");
$stmt->execute([$id_grup, $mulai, $selesai]);
$data = $stmt->fetchAll();

$ringkasan = ['pemasukan' => 0, 'pengeluaran' => 0];
foreach ($data as $row) {
    $ringkasan[$row['jenis'] === 'Pemasukan' ? 'pemasukan' : 'pengeluaran'] += (float) $row['jumlah'];
}
$ringkasan['saldo'] = $ringkasan['pemasukan'] - $ringkasan['pengeluaran'];

$nama_user = trim((string) ($_SESSION['user']['nama'] ?? '')) ?: 'Pengguna';
$meta = [
    'judul' => 'Laporan Kas',
    'grup' => $grup['nama_grup'],
    'periode' => tanggal_id($mulai) . ' – ' . tanggal_id($selesai),
    'dicetak' => tanggal_id(date('Y-m-d H:i:s'), true),
    'oleh' => $nama_user . ' (' . label_peran($role) . ')',
];

$baris = [];
foreach ($data as $row) {
    $baris[] = [
        'tanggal' => tanggal_id($row['tanggal_transaksi']),
        'deskripsi' => $row['deskripsi'] ?: $row['nama_kategori'],
        'kategori' => $row['nama_kategori'],
        'jenis' => $row['jenis'],
        'jumlah' => (float) $row['jumlah'],
    ];
}

try {
    $biner = xlsx_laporan($meta, $ringkasan, $baris);
} catch (RuntimeException $e) {
    http_response_code(500);
    exit('Gagal membuat berkas XLSX.');
}

$nama_berkas = xlsx_nama_berkas($grup['nama_grup'], $mulai, $selesai);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nama_berkas . '"');
header('Content-Length: ' . strlen($biner));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
echo $biner;
