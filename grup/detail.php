<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Detail grup';
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

$stmt = $pdo->prepare("SELECT ag.peran, p.nama, p.email FROM anggota_grup ag JOIN pengguna p ON p.id = ag.id_pengguna WHERE ag.id_grup = ? ORDER BY CASE ag.peran WHEN 'Admin' THEN 1 WHEN 'Bendahara' THEN 2 ELSE 3 END, p.nama");
$stmt->execute([$id]);
$anggota = $stmt->fetchAll();
$jumlah_anggota = count($anggota);

// Alasan Keluar Grup tidak tersedia, ditampilkan langsung di samping tombol (bukan baru muncul sebagai error).
$alasan_tak_bisa_keluar = '';
if ($jumlah_anggota <= 1) {
    $alasan_tak_bisa_keluar = 'Kamu anggota terakhir. Gunakan Hapus Grup untuk menutup grup ini.';
} elseif ($saya_admin && count(array_filter($anggota, fn ($a) => $a['peran'] === 'Admin')) <= 1) {
    $alasan_tak_bisa_keluar = 'Kamu Admin satu-satunya. Jadikan anggota lain Admin di halaman Anggota sebelum keluar.';
}

$stmt = $pdo->prepare("SELECT (SELECT COUNT(*) FROM tagihan WHERE id_grup = ?) AS tagihan, (SELECT COUNT(*) FROM transaksi WHERE id_grup = ?) AS transaksi");
$stmt->execute([$id, $id]);
$hitung = $stmt->fetch();
$jumlah_tagihan = (int) $hitung['tagihan'];
$jumlah_transaksi = (int) $hitung['transaksi'];

$grup_ini_aktif = (int) ($_SESSION['grup_id'] ?? 0) === $id;

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <?php tautan_kembali('index.php', 'Kembali ke Grup'); ?>
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
            <h2>Data grup</h2>
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
            <dt>Kode grup</dt>
            <dd><code><?php echo e($g['kode_grup']); ?></code>
                <button type="button" class="btn secondary small" data-copy="<?php echo e($g['kode_grup']); ?>">Salin kode</button></dd>
        </div>
        <div>
            <dt>Dibuat</dt>
            <dd><?php echo e(tanggal_id($g['dibuat_pada'])); ?></dd>
        </div>
        <div>
            <dt>Deskripsi</dt>
            <dd><?php echo e($g['deskripsi'] ?: '—'); ?></dd>
        </div>
        <div>
            <dt>Info pembayaran</dt>
            <dd class="pre-line"><?php echo e(trim((string) ($g['info_pembayaran'] ?? '')) ?: 'Belum diisi. Admin bisa menambahkannya lewat Edit grup.'); ?></dd>
        </div>
    </dl>
</section>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Aksi grup</h2>
            <p><?php echo $saya_admin
                    ? 'Sebagai Admin kamu dapat mengubah data grup atau menghapusnya.'
                    : 'Kamu dapat keluar dari grup kapan saja.'; ?></p>
        </div>
        <div class="card-actions">
            <?php if ($saya_admin): ?>
            <a class="btn secondary" href="edit.php?id=<?php echo $id; ?>">Edit grup</a>
            <?php endif; ?>
            <form method="post" data-konfirmasi="Keluar dari grup <?php echo e($g['nama_grup']); ?>?">
                <input type="hidden" name="aksi" value="keluar_grup">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <button class="btn ghost-danger" type="submit"<?php echo $alasan_tak_bisa_keluar !== '' ? ' disabled aria-describedby="alasan-keluar"' : ''; ?>>Keluar grup</button>
            </form>
            <?php if ($saya_admin): ?>
            <a class="btn ghost-danger" href="hapus.php?id=<?php echo $id; ?>">Hapus grup</a>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($alasan_tak_bisa_keluar !== ''): ?>
    <p class="form-hint" id="alasan-keluar"><?php echo e($alasan_tak_bisa_keluar); ?></p>
    <?php endif; ?>
    <?php if ($saya_admin): ?>
    <p class="form-hint">Menghapus grup akan menghapus seluruh anggota, tagihan, pembayaran, dan transaksinya.</p>
    <?php endif; ?>
</section>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Anggota grup</h2>
            <p>Daftar anggota beserta perannya pada grup ini.</p>
        </div>
        <a class="text-link" href="../anggota/index.php">Kelola anggota →</a>
    </div>
    <div class="table-fancy-wrap">
        <table class="table-fancy table-stack">
            <thead>
                <tr>
                    <th scope="col">Nama</th>
                    <th scope="col">Email</th>
                    <th scope="col">Peran</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($anggota as $a): ?>
                <tr>
                    <td class="cell-title"><?php echo e($a['nama']); ?></td>
                    <td class="desc" data-label="Email"><?php echo e($a['email']); ?></td>
                    <td data-label="Peran"><span class="badge role-<?php echo strtolower($a['peran']); ?>"><?php echo e($a['peran']); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
