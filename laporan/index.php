<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Laporan';
$page_css = ['dashboard.css', 'table.css'];

$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
cek_role($pdo, $id_grup, akses_menu()['laporan']);

// Tanggal dari query string divalidasi dulu; format salah akan membuat PostgreSQL melempar error.
$mulai = tanggal_valid($_GET['mulai'] ?? '') ? $_GET['mulai'] : date('Y-m-01');
$selesai = tanggal_valid($_GET['selesai'] ?? '') ? $_GET['selesai'] : date('Y-m-d');

$stmt = $pdo->prepare("SELECT t.tanggal_transaksi, k.nama_kategori, t.jenis, t.jumlah, t.deskripsi FROM transaksi t JOIN kategori k ON k.id = t.id_kategori WHERE t.id_grup = ? AND t.tanggal_transaksi BETWEEN ? AND ? ORDER BY t.tanggal_transaksi DESC, t.id DESC");
$stmt->execute([$id_grup, $mulai, $selesai]);
$data = $stmt->fetchAll();

// Ringkasan dihitung dari baris yang sama, tanpa query SUM terpisah.
$ringkasan = ['pemasukan' => 0, 'pengeluaran' => 0];
foreach ($data as $row) {
    $ringkasan[$row['jenis'] === 'Pemasukan' ? 'pemasukan' : 'pengeluaran'] += (float) $row['jumlah'];
}
$saldo = $ringkasan['pemasukan'] - $ringkasan['pengeluaran'];

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <h1>Laporan kas</h1>
    <p>Ringkasan transaksi berdasarkan periode yang dipilih.</p>
</div>

<div class="print-only print-head">
    <h1>Laporan kas</h1>
    <p class="print-meta"><?php echo e($grup['nama_grup'] ?? ''); ?> &middot; Periode <?php echo e(tanggal_id($mulai)); ?> s/d
        <?php echo e(tanggal_id($selesai)); ?></p>
</div>

<section class="content-card no-print">
    <div class="card-head">
        <div>
            <h2>Periode laporan</h2>
            <p>Pilih rentang tanggal — laporan langsung diperbarui.</p>
        </div>
    </div>
    <form method="get" class="filter-form" data-autosubmit>
        <p><label for="mulai">Mulai</label><input id="mulai" type="date" name="mulai" value="<?php echo e($mulai); ?>"></p>
        <p><label for="selesai">Selesai</label><input id="selesai" type="date" name="selesai" value="<?php echo e($selesai); ?>"></p>
        <button class="btn secondary" type="button" data-print>Unduh PDF</button>
        <noscript><button class="btn primary" type="submit">Tampilkan</button></noscript>
    </form>
</section>

<section class="stats">
    <article class="stat-card is-in"><span>Pemasukan</span><strong><?php echo rupiah($ringkasan['pemasukan']); ?></strong></article>
    <article class="stat-card is-out"><span>Pengeluaran</span><strong><?php echo rupiah($ringkasan['pengeluaran']); ?></strong></article>
    <article class="stat-card is-primary"><span>Selisih periode ini</span><strong><?php echo rupiah($saldo); ?></strong></article>
</section>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Rincian transaksi</h2>
            <p><?php echo count($data); ?> transaksi pada periode ini.</p>
        </div>
    </div>
    <div class="table-fancy-wrap">
        <table class="table-fancy table-stack">
            <thead>
                <tr>
                    <th scope="col">Deskripsi</th>
                    <th scope="col">Tanggal</th>
                    <th scope="col">Kategori</th>
                    <th scope="col">Jenis</th>
                    <th scope="col" class="num">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$data): ?><tr class="empty">
                    <td colspan="5">Tidak ada transaksi pada periode ini.</td>
                </tr><?php endif; ?>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td class="cell-title"><?php echo e($row['deskripsi'] ?: $row['nama_kategori']); ?></td>
                    <td class="nowrap" data-label="Tanggal"><?php echo e(tanggal_id($row['tanggal_transaksi'])); ?></td>
                    <td data-label="Kategori"><span class="chip"><?php echo e($row['nama_kategori']); ?></span></td>
                    <td data-label="Jenis"><span class="badge <?php echo $row['jenis'] === 'Pemasukan' ? 'success' : 'danger'; ?>"><?php echo e($row['jenis']); ?></span></td>
                    <td class="num <?php echo $row['jenis'] === 'Pemasukan' ? 'amount-in' : 'amount-out'; ?>" data-label="Jumlah">
                        <?php echo $row['jenis'] === 'Pemasukan' ? '+' : '&minus;'; ?> <?php echo rupiah($row['jumlah']); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
