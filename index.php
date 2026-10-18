<?php
session_start();
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/includes/helpers.php';

if (!isset($_SESSION['user'])) {
    redirect('auth/login.php');
}

$page_title = 'Dashboard';
$page_css = ['dashboard.css', 'table.css'];

$grup = grup_aktif($pdo);
$peran = $grup ? role_group($pdo, $grup['id'], $_SESSION['user']['id']) : null;

if (!$grup) {
    include __DIR__ . '/includes/header.php';
    ?>
<div class="page-head">
    <span class="eyebrow">Belum ada grup</span>
    <h1>Halo, <?php echo e($_SESSION['user']['nama']); ?></h1>
    <p>Selamat datang di SIMKAS. Buat atau pilih grup terlebih dahulu untuk mulai mengelola kas.</p>
</div>
<div class="welcome-card">
    <h2>Belum ada grup aktif</h2>
    <p>Buat grup baru atau gabung memakai kode grup untuk mulai mencatat kas kelasmu.</p>
    <a class="btn primary" href="grup/index.php">Kelola Grup</a>
</div>
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

$kelola = boleh_kelola_transaksi($peran);

$aksi = [
    ['href' => 'grup/index.php', 'judul' => 'Kelola Grup', 'ket' => 'Buat, gabung, dan pilih grup', 'tampil' => true,
        'icon' => '<path d="M4 6h16M4 12h16M4 18h10"/>'],
    ['href' => 'anggota/index.php', 'judul' => 'Kelola Anggota', 'ket' => 'Anggota dan role grup', 'tampil' => true,
        'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/>'],
    ['href' => 'kewajiban/index.php', 'judul' => 'Tagihan', 'ket' => 'Buat dan pantau tagihan kas', 'tampil' => true,
        'icon' => '<path d="M4 4h16v16l-3-2-2 2-3-2-3 2-2-2-3 2z"/><path d="M8 9h8M8 13h5"/>'],
    ['href' => 'pembayaran/index.php', 'judul' => 'Verifikasi', 'ket' => 'Verifikasi pembayaran anggota', 'tampil' => true,
        'icon' => '<path d="M20 6L9 17l-5-5"/>'],
    ['href' => 'transaksi/tambah.php', 'judul' => 'Catat Transaksi', 'ket' => 'Catat pemasukan / pengeluaran', 'tampil' => $kelola,
        'icon' => '<path d="M12 5v14M5 12h14"/>'],
    ['href' => 'laporan/index.php', 'judul' => 'Lihat Laporan', 'ket' => 'Laporan keuangan grup', 'tampil' => in_array($peran, ['Admin', 'Bendahara'], true),
        'icon' => '<path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/>'],
];

include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <span class="eyebrow"><?php echo e(label_peran($peran)); ?></span>
    <h1><?php echo e($grup['nama_grup']); ?></h1>
    <p><?php echo e($grup['deskripsi'] ?: 'Kelola pemasukan, pengeluaran, dan pembayaran kas.'); ?></p>
</div>

<section class="stats">
    <article class="stat-card is-primary"><span>Saldo Kas</span><strong><?php echo rupiah($saldo_akhir); ?></strong></article>
    <article class="stat-card"><span>Total Pemasukan</span><strong><?php echo rupiah($saldo['pemasukan']); ?></strong></article>
    <article class="stat-card"><span>Total Pengeluaran</span><strong><?php echo rupiah($saldo['pengeluaran']); ?></strong></article>
    <article class="stat-card"><span>Anggota</span><strong><?php echo (int) $total_anggota; ?></strong></article>
</section>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Aksi Cepat</h2>
            <p>Pilih menu untuk melanjutkan pengelolaan kas.</p>
        </div>
    </div>
    <div class="quick-grid">
        <?php foreach ($aksi as $item): if (!$item['tampil']) { continue; } ?>
        <a class="quick-card" href="<?php echo $item['href']; ?>">
            <span class="quick-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round"><?php echo $item['icon']; ?></svg>
            </span>
            <strong><?php echo e($item['judul']); ?></strong>
            <span><?php echo e($item['ket']); ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Transaksi Terbaru</h2>
            <p>Lima transaksi terakhir pada grup ini.</p>
        </div>
        <a class="text-link" href="transaksi/index.php">Lihat semua →</a>
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
                <?php if (!$transaksi): ?><tr class="empty">
                    <td colspan="5">Belum ada transaksi.</td>
                </tr><?php endif; ?>
                <?php foreach ($transaksi as $row): ?>
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
<?php include __DIR__ . '/includes/footer.php'; ?>
