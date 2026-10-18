<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Detail Tagihan';
$page_css = ['dashboard.css', 'table.css'];

$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
cek_role($pdo, $id_grup, akses_menu()['kewajiban']);
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM kewajiban_pembayaran WHERE id = ? AND id_grup = ?");
$stmt->execute([$id, $id_grup]);
$k = $stmt->fetch();

if (!$k) {
    flash('error', 'Tagihan tidak ditemukan.');
    redirect('index.php');
}

$stmt = $pdo->prepare("SELECT ak.*, p.nama, p.email FROM anggota_kewajiban ak JOIN pengguna p ON p.id = ak.id_pengguna WHERE ak.id_kewajiban = ? ORDER BY p.nama");
$stmt->execute([$id]);
$anggota = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM anggota_kewajiban WHERE id_kewajiban = ? AND status = 'Lunas'");
$stmt->execute([$id]);
$jumlah_lunas = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT status FROM anggota_kewajiban WHERE id_kewajiban = ? AND id_pengguna = ?");
$stmt->execute([$id, $_SESSION['user']['id']]);
$status_saya = $stmt->fetchColumn();

$badge_saya = $status_saya === 'Lunas'
    ? 'success'
    : ($status_saya === 'Ditolak' ? 'danger' : ($status_saya === 'Menunggu' ? 'warning' : ''));

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <a class="back-link" href="index.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5M12 19l-7-7 7-7" />
        </svg>
        Kembali
    </a>
    <span class="eyebrow">Tagihan</span>
    <h1><?php echo e($k['judul']); ?></h1>
    <p><?php echo e($k['deskripsi'] ?: 'Detail tagihan dan status pembayaran anggota.'); ?></p>
</div>

<section class="stats">
    <article class="stat-card is-primary"><span>Jumlah Tagihan</span><strong><?php echo rupiah($k['jumlah']); ?></strong></article>
    <article class="stat-card"><span>Tenggat</span><strong><?php echo e($k['tenggat_waktu']); ?></strong></article>
    <article class="stat-card"><span>Sudah Lunas</span><strong><?php echo $jumlah_lunas; ?> / <?php echo count($anggota); ?></strong></article>
</section>

<?php if ($status_saya !== false): ?>
<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Status Pembayaran Saya</h2>
            <p>Status kewajiban Anda pada tagihan ini.</p>
        </div>
        <div class="card-actions">
            <span class="badge <?php echo $badge_saya; ?>"><?php echo e(str_replace('_', ' ', $status_saya)); ?></span>
            <?php if (!in_array($status_saya, ['Lunas', 'Menunggu'], true)): ?>
            <a class="btn primary small" href="bayar.php?id=<?php echo (int) $k['id']; ?>">Bayar</a>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Status Anggota</h2>
            <p>Pantau siapa saja yang sudah membayar tagihan ini.</p>
        </div>
    </div>
    <div class="table-fancy-wrap">
        <table class="table-fancy">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Terakhir Bayar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$anggota): ?><tr class="empty">
                    <td colspan="4">Belum ada anggota.</td>
                </tr><?php endif; ?>
                <?php foreach ($anggota as $a): ?>
                <tr>
                    <td><?php echo e($a['nama']); ?></td>
                    <td class="desc"><?php echo e($a['email']); ?></td>
                    <td>
                        <?php
                        $kelas = $a['status'] === 'Lunas' ? 'success' : ($a['status'] === 'Ditolak' ? 'danger' : '');
                        ?>
                        <span class="badge <?php echo $kelas; ?>"><?php echo e(str_replace('_', ' ', $a['status'])); ?></span>
                    </td>
                    <td class="nowrap">
                        <?php
                        $s = $pdo->prepare("SELECT tanggal_pembayaran, status FROM pembayaran WHERE id_anggota_kewajiban = ? ORDER BY id DESC LIMIT 1");
                        $s->execute([$a['id']]);
                        $bayar = $s->fetch();
                        echo $bayar ? e(substr((string) $bayar['tanggal_pembayaran'], 0, 16) . ' (' . $bayar['status'] . ')') : '-';
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
