<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Detail Tagihan';
$page_css = ['dashboard.css', 'table.css'];

$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
$role = cek_role($pdo, $id_grup, akses_menu()['kewajiban']);
$kelola = peran_pengelola($role);
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

// Sekalian hitung anggota grup yang belum dibebani tagihan ini (mis. gabung setelah tagihan dibuat).
$stmt = $pdo->prepare("
    SELECT k.*,
        (SELECT COUNT(*) FROM anggota_grup ag
            WHERE ag.id_grup = k.id_grup
              AND NOT EXISTS (SELECT 1 FROM anggota_kewajiban ak WHERE ak.id_kewajiban = k.id AND ak.id_pengguna = ag.id_pengguna)
        ) AS belum_tertagih
    FROM kewajiban_pembayaran k
    WHERE k.id = ? AND k.id_grup = ?
");
$stmt->execute([$id, $id_grup]);
$k = $stmt->fetch();

if (!$k) {
    flash('error', 'Tagihan tidak ditemukan.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $kelola && ($_POST['aksi'] ?? '') === 'tagihkan_anggota_baru') {
    $jumlah_baru = tagihkan_ke_anggota($pdo, $id, $id_grup);
    flash('success', $jumlah_baru > 0
        ? $jumlah_baru . ' anggota baru ditambahkan ke tagihan ini.'
        : 'Semua anggota grup sudah tercatat di tagihan ini.');
    redirect('detail.php?id=' . $id);
}

$belum_tertagih = (int) $k['belum_tertagih'];

// Pembayaran terakhir tiap anggota ikut diambil di sini (LATERAL), bukan satu query per baris tabel.
$stmt = $pdo->prepare("
    SELECT ak.*, p.nama, p.email, bayar.tanggal_pembayaran, bayar.status AS status_bayar
    FROM anggota_kewajiban ak
    JOIN pengguna p ON p.id = ak.id_pengguna
    LEFT JOIN LATERAL (
        SELECT tanggal_pembayaran, status FROM pembayaran
        WHERE id_anggota_kewajiban = ak.id ORDER BY id DESC LIMIT 1
    ) bayar ON TRUE
    WHERE ak.id_kewajiban = ?
    ORDER BY p.nama
");
$stmt->execute([$id]);
$anggota = $stmt->fetchAll();

$jumlah_lunas = 0;
$status_saya = false;
foreach ($anggota as $a) {
    if ($a['status'] === 'Lunas') {
        $jumlah_lunas++;
    }
    if ((int) $a['id_pengguna'] === (int) $_SESSION['user']['id']) {
        $status_saya = $a['status'];
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <?php tautan_kembali('index.php'); ?>
    <span class="eyebrow">Tagihan</span>
    <h1><?php echo e($k['judul']); ?></h1>
    <p><?php echo e($k['deskripsi'] ?: 'Detail tagihan dan status pembayaran anggota.'); ?></p>
</div>

<section class="stats">
    <article class="stat-card is-primary"><span>Jumlah Tagihan</span><strong><?php echo rupiah($k['jumlah']); ?></strong></article>
    <article class="stat-card"><span>Tenggat</span><strong><?php echo e($k['tenggat_waktu']); ?></strong></article>
    <article class="stat-card"><span>Sudah Lunas</span><strong><?php echo $jumlah_lunas; ?> / <?php echo count($anggota); ?></strong></article>
</section>

<?php if ($kelola): ?>
<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Kelola Tagihan</h2>
            <p><?php echo $belum_tertagih > 0
                    ? $belum_tertagih . ' anggota grup belum termasuk di tagihan ini (bergabung setelah tagihan dibuat).'
                    : 'Ubah rincian tagihan atau hapus bila tagihan dibuat keliru.'; ?></p>
        </div>
        <div class="card-actions">
            <?php if ($belum_tertagih > 0): ?>
            <form method="post" data-konfirmasi="Tagihkan <?php echo e($k['judul']); ?> ke <?php echo $belum_tertagih; ?> anggota baru?">
                <input type="hidden" name="aksi" value="tagihkan_anggota_baru">
                <input type="hidden" name="id" value="<?php echo (int) $k['id']; ?>">
                <button class="btn primary small" type="submit">Tagihkan ke <?php echo $belum_tertagih; ?> anggota baru</button>
            </form>
            <?php endif; ?>
            <a class="btn secondary small" href="edit.php?id=<?php echo (int) $k['id']; ?>">Edit</a>
            <form method="post" action="hapus.php"
                data-konfirmasi="Hapus tagihan <?php echo e($k['judul']); ?>? Pembayaran yang belum disetujui dan buktinya ikut terhapus.">
                <input type="hidden" name="id" value="<?php echo (int) $k['id']; ?>">
                <button class="btn ghost-danger small" type="submit">Hapus</button>
            </form>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($status_saya !== false): ?>
<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Status Pembayaran Saya</h2>
            <p>Status kewajiban Anda pada tagihan ini.</p>
        </div>
        <div class="card-actions">
            <span class="badge <?php echo kelas_status($status_saya); ?>"><?php echo e(str_replace('_', ' ', $status_saya)); ?></span>
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
                        <span class="badge <?php echo kelas_status($a['status']); ?>"><?php echo e(str_replace('_', ' ', $a['status'])); ?></span>
                    </td>
                    <td class="nowrap">
                        <?php echo $a['status_bayar'] !== null
                            ? e(substr((string) $a['tanggal_pembayaran'], 0, 16) . ' (' . $a['status_bayar'] . ')')
                            : '-'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
