<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/helpers.php';

// Pengunjung yang belum masuk melihat landing page; database baru dibutuhkan setelah login.
if (!isset($_SESSION['user'])) {
    include __DIR__ . '/landing.php';
    exit;
}

require_once __DIR__ . '/config/koneksi.php';

$page_title = 'Dashboard';
$page_css = ['dashboard.css', 'table.css'];

$grup = grup_aktif($pdo);
$peran = $grup ? role_group($pdo, $grup['id'], $_SESSION['user']['id']) : null;

if (!$grup) {
    include __DIR__ . '/includes/header.php';
    ?>
<div class="page-head">
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

$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(jumlah) FILTER (WHERE jenis = 'Pemasukan'), 0) AS pemasukan,
        COALESCE(SUM(jumlah) FILTER (WHERE jenis = 'Pengeluaran'), 0) AS pengeluaran,
        (SELECT COUNT(*) FROM anggota_grup WHERE id_grup = ?) AS anggota
    FROM transaksi
    WHERE id_grup = ?
");
$stmt->execute([$id_grup, $id_grup]);
$saldo = $stmt->fetch();
$saldo_akhir = $saldo['pemasukan'] - $saldo['pengeluaran'];
$total_anggota = $saldo['anggota'];

$stmt = $pdo->prepare("SELECT t.*, k.nama_kategori FROM transaksi t JOIN kategori k ON k.id = t.id_kategori WHERE t.id_grup = ? ORDER BY t.tanggal_transaksi DESC, t.id DESC LIMIT 5");
$stmt->execute([$id_grup]);
$transaksi = $stmt->fetchAll();

// Tagihan milik user ini + status & alasan pembayaran terakhirnya (bila ditolak).
$stmt = $pdo->prepare("
    SELECT k.id, k.judul, k.jumlah, k.tenggat_waktu, ak.status, bayar.catatan
    FROM anggota_kewajiban ak
    JOIN kewajiban_pembayaran k ON k.id = ak.id_kewajiban
    LEFT JOIN LATERAL (
        SELECT catatan FROM pembayaran WHERE id_anggota_kewajiban = ak.id ORDER BY id DESC LIMIT 1
    ) bayar ON TRUE
    WHERE ak.id_pengguna = ? AND k.id_grup = ?
    ORDER BY k.tenggat_waktu
");
$stmt->execute([$_SESSION['user']['id'], $id_grup]);

$tagihan_terbuka = [];
$total_tagihan = 0;
$belum_dibayar = 0;
$jumlah_menunggu = 0;
foreach ($stmt->fetchAll() as $row) {
    $total_tagihan++;
    if ($row['status'] === 'Lunas') {
        continue;
    }
    if ($row['status'] === 'Menunggu') {
        $jumlah_menunggu++;
    } else {
        $belum_dibayar += (float) $row['jumlah'];
    }
    $tagihan_terbuka[] = $row;
}
$jumlah_harus_bayar = count($tagihan_terbuka) - $jumlah_menunggu;
$tampil_tagihan = array_slice($tagihan_terbuka, 0, 4);

$kelola = peran_pengelola($peran);
$menunggu_verifikasi = 0;
if ($kelola) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pembayaran p JOIN anggota_kewajiban ak ON ak.id = p.id_anggota_kewajiban JOIN kewajiban_pembayaran k ON k.id = ak.id_kewajiban WHERE k.id_grup = ? AND p.status = 'Menunggu'");
    $stmt->execute([$id_grup]);
    $menunggu_verifikasi = (int) $stmt->fetchColumn();
}

include __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <h1><?php echo e($grup['nama_grup']); ?></h1>
    <p><?php echo e($grup['deskripsi'] ?: 'Kelola pemasukan, pengeluaran, dan pembayaran kas.'); ?></p>
</div>

<div class="dash-grid<?php echo $kelola ? ' has-side' : ''; ?>">
    <section class="content-card" aria-labelledby="judul-tagihan-kamu">
        <div class="card-head">
            <div>
                <h2 id="judul-tagihan-kamu">Tagihan kamu</h2>
                <p class="dash-summary">
                    <?php if ($jumlah_harus_bayar > 0): ?>
                    <strong><?php echo rupiah($belum_dibayar); ?></strong> belum dibayar dari
                    <?php echo $jumlah_harus_bayar; ?> tagihan<?php echo $jumlah_menunggu > 0 ? ' &middot; ' . $jumlah_menunggu . ' menunggu verifikasi' : ''; ?>.
                    <?php elseif ($jumlah_menunggu > 0): ?>
                    Semua sudah kamu bayar. <?php echo $jumlah_menunggu; ?> pembayaran sedang menunggu verifikasi.
                    <?php elseif ($total_tagihan > 0): ?>
                    Semua tagihan kamu sudah lunas.
                    <?php else: ?>
                    Belum ada tagihan untukmu di grup ini.
                    <?php endif; ?>
                </p>
            </div>
            <a class="text-link" href="kewajiban/index.php">Semua tagihan →</a>
        </div>

        <?php if ($tampil_tagihan): ?>
        <ul class="bill-list">
            <?php foreach ($tampil_tagihan as $t): $tenggat = info_tenggat($t['tenggat_waktu']); ?>
            <li class="bill-item">
                <div>
                    <a class="bill-title" href="kewajiban/detail.php?id=<?php echo (int) $t['id']; ?>"><?php echo e($t['judul']); ?></a>
                    <span class="bill-meta">
                        <span>Tenggat <?php echo e(tanggal_id($t['tenggat_waktu'])); ?></span>
                        <span class="<?php echo $tenggat['lewat'] ? 'is-late' : ''; ?>"><?php echo e($tenggat['teks']); ?></span>
                    </span>
                </div>
                <span class="bill-amount"><?php echo rupiah($t['jumlah']); ?></span>
                <span class="bill-action">
                    <?php if ($t['status'] === 'Menunggu'): ?>
                    <span class="badge warning"><?php echo e(label_status('Menunggu')); ?></span>
                    <?php else: ?>
                    <a class="btn primary small" href="kewajiban/bayar.php?id=<?php echo (int) $t['id']; ?>"><?php echo $t['status'] === 'Ditolak' ? 'Bayar ulang' : 'Bayar'; ?></a>
                    <?php endif; ?>
                </span>
                <?php if ($t['status'] === 'Ditolak'): ?>
                <p class="bill-reason">Ditolak<?php echo trim((string) $t['catatan']) !== '' ? ': ' . e($t['catatan']) : '. Hubungi Bendahara untuk detailnya.'; ?></p>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if (count($tagihan_terbuka) > count($tampil_tagihan)): ?>
        <p class="bill-more"><a href="kewajiban/index.php">dan <?php echo count($tagihan_terbuka) - count($tampil_tagihan); ?> tagihan lainnya</a></p>
        <?php endif; ?>
        <?php endif; ?>
    </section>

    <?php if ($kelola): ?>
    <section class="content-card" aria-labelledby="judul-perlu-tindakan">
        <div class="card-head">
            <div>
                <h2 id="judul-perlu-tindakan">Perlu tindakan</h2>
                <p>Pekerjaan bendahara untuk grup ini.</p>
            </div>
        </div>
        <ul class="todo-list">
            <li><a href="pembayaran/index.php">Verifikasi pembayaran
                <?php if ($menunggu_verifikasi > 0): ?><span class="todo-count"><?php echo $menunggu_verifikasi; ?></span>
                <?php else: ?><span class="todo-go">Tidak ada antrean</span><?php endif; ?></a></li>
            <li><a href="kewajiban/tambah.php">Buat tagihan <span class="todo-go">→</span></a></li>
            <li><a href="transaksi/tambah.php">Catat transaksi <span class="todo-go">→</span></a></li>
            <li><a href="laporan/index.php">Lihat laporan <span class="todo-go">→</span></a></li>
        </ul>
    </section>
    <?php endif; ?>
</div>

<section class="stats" aria-label="Ringkasan kas grup">
    <article class="stat-card is-primary"><span>Saldo kas</span><strong><?php echo rupiah($saldo_akhir); ?></strong></article>
    <article class="stat-card is-in"><span>Total pemasukan</span><strong><?php echo rupiah($saldo['pemasukan']); ?></strong></article>
    <article class="stat-card is-out"><span>Total pengeluaran</span><strong><?php echo rupiah($saldo['pengeluaran']); ?></strong></article>
    <article class="stat-card"><span>Anggota</span><strong><?php echo (int) $total_anggota; ?></strong></article>
</section>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Transaksi terbaru</h2>
            <p>Lima transaksi terakhir pada grup ini.</p>
        </div>
        <a class="text-link" href="transaksi/index.php">Lihat semua →</a>
    </div>
    <div class="table-fancy-wrap">
        <table class="table-fancy table-stack">
            <thead>
                <tr>
                    <th scope="col">Deskripsi</th>
                    <th scope="col">Tanggal</th>
                    <th scope="col">Kategori</th>
                    <th scope="col" class="num">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$transaksi): ?><tr class="empty">
                    <td colspan="4">Belum ada transaksi.</td>
                </tr><?php endif; ?>
                <?php foreach ($transaksi as $row): $masuk = $row['jenis'] === 'Pemasukan'; ?>
                <tr>
                    <td class="cell-title"><?php echo e($row['deskripsi'] ?: $row['nama_kategori']); ?></td>
                    <td class="nowrap" data-label="Tanggal"><?php echo e(tanggal_id($row['tanggal_transaksi'])); ?></td>
                    <td data-label="Kategori"><span class="chip"><?php echo e($row['nama_kategori']); ?></span></td>
                    <td class="num <?php echo $masuk ? 'amount-in' : 'amount-out'; ?>" data-label="<?php echo e($row['jenis']); ?>">
                        <?php echo $masuk ? '+' : '&minus;'; ?> <?php echo rupiah($row['jumlah']); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
