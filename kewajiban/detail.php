<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Detail tagihan';
$page_css = ['dashboard.css', 'table.css', 'kewajiban.css'];

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
    SELECT ak.*, p.nama, p.email, bayar.tanggal_pembayaran, bayar.status AS status_bayar, bayar.catatan
    FROM anggota_kewajiban ak
    JOIN pengguna p ON p.id = ak.id_pengguna
    LEFT JOIN LATERAL (
        SELECT tanggal_pembayaran, status, catatan FROM pembayaran
        WHERE id_anggota_kewajiban = ak.id ORDER BY id DESC LIMIT 1
    ) bayar ON TRUE
    WHERE ak.id_kewajiban = ?
    ORDER BY p.nama
");
$stmt->execute([$id]);
$anggota = $stmt->fetchAll();

$jumlah_lunas = 0;
$status_saya = false;
$saya = null;
foreach ($anggota as $a) {
    if ($a['status'] === 'Lunas') {
        $jumlah_lunas++;
    }
    if ((int) $a['id_pengguna'] === (int) $_SESSION['user']['id']) {
        $status_saya = $a['status'];
        $saya = $a;
    }
}
$tenggat = info_tenggat($k['tenggat_waktu']);

// Langkah timeline: Dikirim -> Diverifikasi -> Lunas (Ditolak menandai langkah verifikasi gagal).
$langkah_kelas = ['', '', ''];
if ($status_saya === 'Menunggu') {
    $langkah_kelas = ['is-done', 'is-current', ''];
} elseif ($status_saya === 'Lunas') {
    $langkah_kelas = ['is-done', 'is-done', 'is-done'];
} elseif ($status_saya === 'Ditolak') {
    $langkah_kelas = ['is-done', 'is-failed', ''];
}

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <?php tautan_kembali('index.php'); ?>
    <div class="page-head-row">
        <div>
            <h1><?php echo e($k['judul']); ?></h1>
            <p><?php echo e($k['deskripsi'] ?: 'Detail tagihan dan status pembayaran anggota.'); ?></p>
        </div>
        <?php if ($kelola): ?>
        <div class="page-actions">
            <a class="btn secondary small" href="edit.php?id=<?php echo (int) $k['id']; ?>">Edit tagihan</a>
            <form method="post" action="hapus.php"
                data-konfirmasi="Hapus tagihan <?php echo e($k['judul']); ?>? Pembayaran yang belum disetujui dan buktinya ikut terhapus.">
                <input type="hidden" name="id" value="<?php echo (int) $k['id']; ?>">
                <button class="btn ghost-danger small" type="submit">Hapus</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<section class="stats">
    <article class="stat-card is-primary"><span>Jumlah per anggota</span><strong><?php echo rupiah($k['jumlah']); ?></strong></article>
    <article class="stat-card"><span>Tenggat &middot; <?php echo e($tenggat['teks']); ?></span><strong><?php echo e(tanggal_id($k['tenggat_waktu'])); ?></strong></article>
    <article class="stat-card"><span>Sudah lunas</span><strong><?php echo $jumlah_lunas; ?> dari <?php echo count($anggota); ?></strong></article>
</section>

<?php if ($status_saya !== false): ?>
<section class="content-card" aria-labelledby="judul-status-saya">
    <div class="card-head">
        <div>
            <h2 id="judul-status-saya">Pembayaran kamu</h2>
            <p><?php echo e(label_status($status_saya)); ?><?php echo $saya['tanggal_pembayaran'] ? ' &middot; dikirim ' . e(tanggal_id($saya['tanggal_pembayaran'], true)) : ''; ?></p>
        </div>
        <?php if (in_array($status_saya, ['Belum_Bayar', 'Ditolak'], true)): ?>
        <a class="btn primary small" href="bayar.php?id=<?php echo (int) $k['id']; ?>"><?php echo $status_saya === 'Ditolak' ? 'Bayar ulang' : 'Bayar sekarang'; ?></a>
        <?php endif; ?>
    </div>
    <?php if ($status_saya === 'Ditolak'): ?>
    <div class="notice is-danger" role="status">
        <strong>Pembayaran ditolak</strong>
        <span class="notice-body"><?php echo trim((string) $saya['catatan']) !== '' ? e($saya['catatan']) : 'Bendahara tidak menuliskan alasan. Hubungi Bendahara untuk detailnya.'; ?></span>
    </div>
    <?php endif; ?>
    <ol class="status-steps" aria-label="Tahap pembayaran">
        <li class="<?php echo $langkah_kelas[0]; ?>"><strong>Dikirim</strong>Kamu mengirim pembayaran</li>
        <li class="<?php echo $langkah_kelas[1]; ?>"><strong><?php echo $status_saya === 'Ditolak' ? 'Ditolak' : 'Diverifikasi'; ?></strong>Bendahara memeriksa bukti</li>
        <li class="<?php echo $langkah_kelas[2]; ?>"><strong>Lunas</strong>Tercatat sebagai pemasukan kas</li>
    </ol>
</section>
<?php endif; ?>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Status anggota</h2>
            <p><?php echo $kelola && $belum_tertagih > 0
                    ? $belum_tertagih . ' anggota grup belum termasuk di tagihan ini karena bergabung setelah tagihan dibuat.'
                    : 'Pantau siapa saja yang sudah membayar tagihan ini.'; ?></p>
        </div>
        <?php if ($kelola && $belum_tertagih > 0): ?>
        <form method="post" data-konfirmasi="Tagihkan <?php echo e($k['judul']); ?> ke <?php echo $belum_tertagih; ?> anggota baru?">
            <input type="hidden" name="aksi" value="tagihkan_anggota_baru">
            <input type="hidden" name="id" value="<?php echo (int) $k['id']; ?>">
            <button class="btn primary small" type="submit">Tagihkan ke <?php echo $belum_tertagih; ?> anggota baru</button>
        </form>
        <?php endif; ?>
    </div>
    <div class="table-fancy-wrap">
        <table class="table-fancy table-stack">
            <thead>
                <tr>
                    <th scope="col">Nama</th>
                    <th scope="col">Email</th>
                    <th scope="col">Status</th>
                    <th scope="col">Terakhir bayar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$anggota): ?><tr class="empty">
                    <td colspan="4">Belum ada anggota yang ditagih.</td>
                </tr><?php endif; ?>
                <?php foreach ($anggota as $a): ?>
                <tr>
                    <td class="cell-title"><?php echo e($a['nama']); ?></td>
                    <td class="desc" data-label="Email"><?php echo e($a['email']); ?></td>
                    <td data-label="Status">
                        <span class="badge <?php echo kelas_status($a['status']); ?>"><?php echo e(label_status($a['status'])); ?></span>
                    </td>
                    <td class="nowrap" data-label="Terakhir bayar">
                        <?php echo $a['status_bayar'] !== null ? e(tanggal_id($a['tanggal_pembayaran'], true)) : '-'; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
