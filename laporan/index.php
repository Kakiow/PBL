<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Laporan';
$page_css = ['dashboard.css', 'table.css'];

$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
cek_role($pdo, $id_grup, akses_menu()['laporan']);

$mulai = $_GET['mulai'] ?? date('Y-m-01');
$selesai = $_GET['selesai'] ?? date('Y-m-d');

$stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN jenis='Pemasukan' THEN jumlah ELSE 0 END),0) AS pemasukan, COALESCE(SUM(CASE WHEN jenis='Pengeluaran' THEN jumlah ELSE 0 END),0) AS pengeluaran FROM transaksi WHERE id_grup = ? AND tanggal_transaksi BETWEEN ? AND ?");
$stmt->execute([$id_grup, $mulai, $selesai]);
$ringkasan = $stmt->fetch();
$saldo = $ringkasan['pemasukan'] - $ringkasan['pengeluaran'];

$stmt = $pdo->prepare("SELECT t.tanggal_transaksi, k.nama_kategori, t.jenis, t.jumlah, t.deskripsi FROM transaksi t JOIN kategori k ON k.id = t.id_kategori WHERE t.id_grup = ? AND t.tanggal_transaksi BETWEEN ? AND ? ORDER BY t.tanggal_transaksi DESC, t.id DESC");
$stmt->execute([$id_grup, $mulai, $selesai]);
$data = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <span class="eyebrow">Laporan</span>
    <h1>Laporan Kas</h1>
    <p>Ringkasan transaksi berdasarkan periode yang dipilih.</p>
</div>

<div class="print-only print-head">
    <h1>Laporan Kas</h1>
    <p class="print-meta"><?php echo e($grup['nama_grup'] ?? ''); ?> &middot; Periode <?php echo e($mulai); ?> s/d
        <?php echo e($selesai); ?></p>
</div>

<section class="content-card no-print">
    <div class="card-head">
        <div>
            <h2>Periode Laporan</h2>
            <p>Pilih rentang tanggal — laporan langsung diperbarui.</p>
        </div>
    </div>
    <form method="get" class="filter-form" data-autosubmit>
        <p><label>Mulai</label><input type="date" name="mulai" value="<?php echo e($mulai); ?>"></p>
        <p><label>Selesai</label><input type="date" name="selesai" value="<?php echo e($selesai); ?>"></p>
        <button class="btn secondary" type="button" data-print>Export to PDF</button>
        <noscript><button class="btn primary" type="submit">Tampilkan</button></noscript>
    </form>
</section>

<section class="stats">
    <article class="stat-card"><span>Pemasukan</span><strong><?php echo rupiah($ringkasan['pemasukan']); ?></strong></article>
    <article class="stat-card"><span>Pengeluaran</span><strong><?php echo rupiah($ringkasan['pengeluaran']); ?></strong></article>
    <article class="stat-card is-primary"><span>Saldo</span><strong><?php echo rupiah($saldo); ?></strong></article>
</section>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Rincian Transaksi</h2>
            <p><?php echo count($data); ?> transaksi pada periode ini.</p>
        </div>
    </div>
    <div class="table-fancy-wrap">
        <table class="table-fancy">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Jenis</th>
                    <th class="num">Jumlah</th>
                    <th>Deskripsi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$data): ?><tr class="empty">
                    <td colspan="5">Tidak ada transaksi pada periode ini.</td>
                </tr><?php endif; ?>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td class="nowrap"><?php echo e($row['tanggal_transaksi']); ?></td>
                    <td><span class="chip"><?php echo e($row['nama_kategori']); ?></span></td>
                    <td><span class="badge <?php echo $row['jenis'] === 'Pemasukan' ? 'success' : 'danger'; ?>"><?php echo e($row['jenis']); ?></span></td>
                    <td class="num <?php echo $row['jenis'] === 'Pemasukan' ? 'amount-in' : 'amount-out'; ?>">
                        <?php echo $row['jenis'] === 'Pemasukan' ? '+' : '&minus;'; ?> <?php echo rupiah($row['jumlah']); ?>
                    </td>
                    <td class="desc"><?php echo e($row['deskripsi']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
