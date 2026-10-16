<?php
session_start();
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/helpers.php';

if (!isset($_SESSION['user'])) {
    redirect('auth/login.php');
}

$page_title = 'Dashboard';
$page_css = ['dashboard.css'];
$grup = grup_aktif($pdo);

if (!$grup) {
    include __DIR__ . '/includes/header.php';
    ?>
<section class="hero">
    <h1>Halo, <?php echo e($_SESSION['user']['nama']); ?>!</h1>
    <p>Selamat datang di SIMKAS. Buat atau pilih grup terlebih dahulu untuk mulai mengelola kas.</p>
    <a class="btn primary" href="grup/index.php">Kelola Grup</a>
</section>
<?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

$id_grup = $grup['id'];

$stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN jenis = 'Pemasukan' THEN jumlah ELSE 0 END),0) AS pemasukan, COALESCE(SUM(CASE WHEN jenis = 'Pengeluaran' THEN jumlah ELSE 0 END),0) AS pengeluaran FROM transaksi WHERE id_grup = ?");
$stmt->execute([$id_grup]);
$saldo = $stmt->fetch();
$saldo_akhir = $saldo['pemasukan'] - $saldo['pengeluaran'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM anggota_grup WHERE id_grup = ?");
$stmt->execute([$id_grup]);
$total_anggota = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM kewajiban_pembayaran WHERE id_grup = ?");
$stmt->execute([$id_grup]);
$total_kewajiban = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT t.*, k.nama_kategori FROM transaksi t JOIN kategori k ON k.id = t.id_kategori WHERE t.id_grup = ? ORDER BY t.tanggal_transaksi DESC, t.id DESC LIMIT 5");
$stmt->execute([$id_grup]);
$transaksi = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div>
        <p class="eyebrow">GRUP AKTIF</p>
        <h1><?php echo e($grup['nama_grup']); ?></h1>
        <p><?php echo e($grup['deskripsi'] ?: 'Kelola pemasukan, pengeluaran, dan pembayaran kas.'); ?></p>
    </div>
    <a class="btn primary" href="grup/index.php">Kelola Grup</a>
</section>

<section class="stats">
    <article class="stat-card"><span>Saldo Kas</span><strong><?php echo rupiah($saldo_akhir); ?></strong></article>
    <article class="stat-card"><span>Total Pemasukan</span><strong><?php echo rupiah($saldo['pemasukan']); ?></strong>
    </article>
    <article class="stat-card"><span>Total
            Pengeluaran</span><strong><?php echo rupiah($saldo['pengeluaran']); ?></strong></article>
    <article class="stat-card"><span>Anggota</span><strong><?php echo $total_anggota; ?></strong></article>
</section>

<section>
    <div class="section-heading">
        <div>
            <h2>Transaksi Terbaru</h2>
            <p class="muted">Lima transaksi terakhir pada grup ini.</p>
        </div>
        <a class="btn secondary" href="transaksi/index.php">Lihat Semua</a>
    </div>
    <div class="table-responsive table-transaksi-wrap">
        <table class="table-transaksi">
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
                <?php if (!$transaksi): ?><tr class="empty">
                    <td colspan="5">Belum ada transaksi.</td>
                </tr><?php endif; ?>
                <?php foreach ($transaksi as $row): ?>
                <tr>
                    <td class="nowrap"><?php echo e($row['tanggal_transaksi']); ?></td>
                    <td><span class="chip"><?php echo e($row['nama_kategori']); ?></span></td>
                    <td><span
                            class="badge <?php echo $row['jenis'] === 'Pemasukan' ? 'success' : 'danger'; ?>"><?php echo e($row['jenis']); ?></span>
                    </td>
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

<section>
    <div class="section-heading">
        <div>
            <h2>Menu Cepat</h2>
            <p class="muted">Akses fitur pengelolaan kas.</p>
        </div>
    </div>
    <div class="quick-menu">
        <a href="kewajiban/index.php"><strong>Kewajiban</strong><span><?php echo $total_kewajiban; ?> data</span></a>
        <a href="pembayaran/index.php"><strong>Pembayaran</strong><span>Kelola pembayaran anggota</span></a>
        <a href="transaksi/tambah.php"><strong>Tambah Transaksi</strong><span>Catat pemasukan/pengeluaran</span></a>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>