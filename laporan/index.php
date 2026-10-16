<?php
session_start();
require_once __DIR__.'/../config/koneksi.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/auth.php';
$page_title = 'Laporan';
$id_grup = (int)($_SESSION['grup_id'] ?? 0);
cek_role($pdo, $id_grup, ['Admin','Bendahara','Anggota']);
$mulai = $_GET['mulai'] ?? date('Y-m-01');
$selesai = $_GET['selesai'] ?? date('Y-m-d');
$stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN jenis='Pemasukan' THEN jumlah ELSE 0 END),0) pemasukan, COALESCE(SUM(CASE WHEN jenis='Pengeluaran' THEN jumlah ELSE 0 END),0) pengeluaran FROM transaksi WHERE id_grup=? AND tanggal_transaksi BETWEEN ? AND ?");
$stmt->execute([$id_grup,$mulai,$selesai]);
$ringkasan = $stmt->fetch();
$saldo = $ringkasan['pemasukan'] - $ringkasan['pengeluaran'];
$stmt = $pdo->prepare("SELECT t.tanggal_transaksi,k.nama_kategori,t.jenis,t.jumlah,t.deskripsi FROM transaksi t JOIN kategori k ON k.id=t.id_kategori WHERE t.id_grup=? AND t.tanggal_transaksi BETWEEN ? AND ? ORDER BY t.tanggal_transaksi DESC,t.id DESC");
$stmt->execute([$id_grup,$mulai,$selesai]);
$data = $stmt->fetchAll();
include __DIR__.'/../includes/header.php';
?><section>
    <div class="section-heading">
        <div>
            <h1>Laporan Kas</h1>
            <p class="muted">Lihat ringkasan transaksi berdasarkan periode.</p>
        </div>
    </div>
    <form method="get" class="filter-form">
        <p><label>Mulai</label><input type="date" name="mulai" value="<?php echo e($mulai);?>"></p>
        <p><label>Selesai</label><input type="date" name="selesai" value="<?php echo e($selesai);?>"></p><button
            class="btn primary">Tampilkan</button>
    </form>
</section>
<section class="stats">
    <article class="stat-card"><span>Pemasukan</span><strong><?php echo rupiah($ringkasan['pemasukan']);?></strong>
    </article>
    <article class="stat-card"><span>Pengeluaran</span><strong><?php echo rupiah($ringkasan['pengeluaran']);?></strong>
    </article>
    <article class="stat-card"><span>Saldo</span><strong><?php echo rupiah($saldo);?></strong></article>
</section>
<section>
    <h2>Rincian Transaksi</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Jenis</th>
                    <th>Jumlah</th>
                    <th>Deskripsi</th>
                </tr>
            </thead>
            <tbody><?php if (!$data):?><tr>
                    <td colspan="5">Tidak ada transaksi pada periode ini.</td>
                </tr><?php endif;?><?php foreach ($data as $row):?><tr>
                    <td><?php echo e($row['tanggal_transaksi']);?></td>
                    <td><?php echo e($row['nama_kategori']);?></td>
                    <td><?php echo e($row['jenis']);?></td>
                    <td><?php echo rupiah($row['jumlah']);?></td>
                    <td><?php echo e($row['deskripsi']);?></td>
                </tr><?php endforeach;?></tbody>
        </table>
    </div>
</section><?php include __DIR__.'/../includes/footer.php';?>