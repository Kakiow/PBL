<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Detail Grup';
$page_css = ['dashboard.css', 'table.css'];
$id_user = (int) $_SESSION['user']['id'];
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM grup WHERE id = ?");
$stmt->execute([$id]);
$g = $stmt->fetch();

$peran = $g ? role_group($pdo, $id, $id_user) : null;

if (!$g || !$peran) {
    flash('error', 'Grup tidak ditemukan atau Anda bukan anggotanya.');
    redirect('index.php');
}

$saya_admin = $peran === 'Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'keluar_grup') {
    $jumlah_anggota = jumlah_anggota_grup($pdo, $id);

    if ($jumlah_anggota <= 1) {
        flash('error', 'Anda anggota terakhir di grup ini. Gunakan Hapus Grup untuk menutup grup.');
    } elseif ($saya_admin && jumlah_admin_grup($pdo, $id) <= 1) {
        flash('error', 'Anda Admin satu-satunya. Jadikan anggota lain sebagai Admin di tab Anggota terlebih dahulu.');
    } else {
        $stmt = $pdo->prepare("DELETE FROM anggota_grup WHERE id_grup = ? AND id_pengguna = ?");
        $stmt->execute([$id, $id_user]);
        grup_aktif_berikutnya($pdo, $id_user, $id);
        flash('success', 'Anda berhasil keluar dari grup ' . $g['nama_grup'] . '.');
        redirect('index.php');
    }

    redirect('detail.php?id=' . $id);
}

$jumlah_anggota = jumlah_anggota_grup($pdo, $id);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM kewajiban_pembayaran WHERE id_grup = ?");
$stmt->execute([$id]);
$jumlah_tagihan = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM transaksi WHERE id_grup = ?");
$stmt->execute([$id]);
$jumlah_transaksi = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT ag.peran, p.nama, p.email FROM anggota_grup ag JOIN pengguna p ON p.id = ag.id_pengguna WHERE ag.id_grup = ? ORDER BY CASE ag.peran WHEN 'Admin' THEN 1 WHEN 'Bendahara' THEN 2 ELSE 3 END, p.nama");
$stmt->execute([$id]);
$anggota = $stmt->fetchAll();

$grup_ini_aktif = (int) ($_SESSION['grup_id'] ?? 0) === $id;

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <a class="back-link" href="index.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5M12 19l-7-7 7-7" />
        </svg>
        Kembali ke Grup
    </a>
    <span class="eyebrow">Grup</span>
    <h1><?php echo e($g['nama_grup']); ?></h1>
    <p><?php echo e($g['deskripsi'] ?: 'Grup ini belum memiliki deskripsi.'); ?></p>
</div>

<section class="stats">
    <article class="stat-card is-primary"><span>Anggota</span><strong><?php echo $jumlah_anggota; ?></strong></article>
    <article class="stat-card"><span>Tagihan</span><strong><?php echo $jumlah_tagihan; ?></strong></article>
    <article class="stat-card"><span>Transaksi</span><strong><?php echo $jumlah_transaksi; ?></strong></article>
</section>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Data Grup</h2>
            <p>Bagikan kode grup ke calon anggota agar mereka bisa ikut bergabung.</p>
        </div>
        <div class="card-actions">
            <?php if ($grup_ini_aktif): ?>
            <span class="badge success">Grup aktif</span>
            <?php else: ?>
            <span class="badge">Belum aktif</span>
            <?php endif; ?>
            <span class="badge role-<?php echo strtolower($peran); ?>"><?php echo e($peran); ?></span>
        </div>
    </div>
    <dl class="detail-list">
        <div>
            <dt>Kode Grup</dt>
            <dd><code><?php echo e($g['kode_grup']); ?></code></dd>
        </div>
        <div>
            <dt>Dibuat</dt>
            <dd><?php echo e(substr((string) $g['dibuat_pada'], 0, 10)); ?></dd>
        </div>
        <div>
            <dt>Deskripsi</dt>
            <dd><?php echo e($g['deskripsi'] ?: '—'); ?></dd>
        </div>
    </dl>
</section>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Aksi Grup</h2>
            <p><?php echo $saya_admin
                    ? 'Sebagai Admin Anda dapat mengubah data grup atau menghapusnya.'
                    : 'Anda dapat keluar dari grup kapan saja.'; ?></p>
        </div>
        <div class="card-actions">
            <?php if ($saya_admin): ?>
            <a class="btn secondary" href="edit.php?id=<?php echo $id; ?>">Edit Grup</a>
            <?php endif; ?>
            <form method="post" data-konfirmasi="Keluar dari grup <?php echo e($g['nama_grup']); ?>?">
                <input type="hidden" name="aksi" value="keluar_grup">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <button class="btn ghost-danger" type="submit">Keluar Grup</button>
            </form>
            <?php if ($saya_admin): ?>
            <a class="btn danger" href="hapus.php?id=<?php echo $id; ?>">Hapus Grup</a>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($saya_admin): ?>
    <p class="form-hint">Admin tidak bisa keluar selama menjadi Admin satu-satunya &mdash; jadikan anggota lain
        sebagai Admin di tab Anggota terlebih dahulu. Menghapus grup akan menghapus seluruh anggota, tagihan,
        pembayaran, dan transaksinya.</p>
    <?php endif; ?>
</section>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Anggota Grup</h2>
            <p>Daftar anggota beserta perannya pada grup ini.</p>
        </div>
        <a class="text-link" href="../anggota/index.php">Kelola anggota →</a>
    </div>
    <div class="table-fancy-wrap">
        <table class="table-fancy">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Peran</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($anggota as $a): ?>
                <tr>
                    <td><?php echo e($a['nama']); ?></td>
                    <td class="desc"><?php echo e($a['email']); ?></td>
                    <td><span class="badge role-<?php echo strtolower($a['peran']); ?>"><?php echo e($a['peran']); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
