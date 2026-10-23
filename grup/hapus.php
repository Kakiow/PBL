<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Hapus grup';
$page_css = ['dashboard.css'];
$id_user = (int) $_SESSION['user']['id'];
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM grup WHERE id = ?");
$stmt->execute([$id]);
$g = $stmt->fetch();

$peran = $g ? role_group($pdo, $id, $id_user) : null;

if (!$g || $peran !== 'Admin') {
    flash('error', 'Hanya Admin grup yang dapat menghapus grup.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $konfirmasi = trim($_POST['konfirmasi'] ?? '');

    if (!hash_equals((string) $g['nama_grup'], $konfirmasi)) {
        flash('error', 'Nama grup tidak cocok. Ketik persis: ' . $g['nama_grup']);
        redirect('hapus.php?id=' . $id);
    }

    // Kumpulkan bukti pembayaran grup ini dulu supaya tidak tertinggal di disk.
    $stmt = $pdo->prepare("SELECT p.berkas_bukti FROM pembayaran p JOIN tagihan_anggota ak ON ak.id = p.id_tagihan_anggota JOIN tagihan k ON k.id = ak.id_tagihan WHERE k.id_grup = ? AND p.berkas_bukti <> ''");
    $stmt->execute([$id]);
    $berkas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM grup WHERE id = ?");
        $stmt->execute([$id]);
        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', 'Grup gagal dihapus.');
        redirect('hapus.php?id=' . $id);
    }

    foreach ($berkas as $nama) {
        if (!hapus_bukti($nama)) {
            error_log('Gagal menghapus bukti pembayaran grup ' . $id . ': ' . $nama);
        }
    }

    grup_aktif_berikutnya($pdo, $id_user, $id);
    flash('success', 'Grup ' . $g['nama_grup'] . ' beserta seluruh datanya berhasil dihapus.');
    redirect('index.php');
}

// Ringkasan data yang ikut terhapus — cukup satu query, hanya saat halaman ditampilkan.
$stmt = $pdo->prepare("
    SELECT
        (SELECT COUNT(*) FROM anggota_grup WHERE id_grup = ?) AS anggota,
        (SELECT COUNT(*) FROM tagihan WHERE id_grup = ?) AS tagihan,
        (SELECT COUNT(*) FROM pembayaran p
            JOIN tagihan_anggota ak ON ak.id = p.id_tagihan_anggota
            JOIN tagihan k ON k.id = ak.id_tagihan
            WHERE k.id_grup = ?) AS pembayaran,
        (SELECT COUNT(*) FROM transaksi WHERE id_grup = ?) AS transaksi
");
$stmt->execute([$id, $id, $id, $id]);
$hitung = $stmt->fetch();
$jumlah_anggota = (int) $hitung['anggota'];
$jumlah_tagihan = (int) $hitung['tagihan'];
$jumlah_pembayaran = (int) $hitung['pembayaran'];
$jumlah_transaksi = (int) $hitung['transaksi'];

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <?php tautan_kembali('detail.php?id=' . $id, 'Kembali ke Detail'); ?>
    <h1>Hapus grup</h1>
    <p>Tindakan ini permanen dan tidak dapat dibatalkan.</p>
</div>

<section class="stats">
    <article class="stat-card"><span>Anggota</span><strong><?php echo $jumlah_anggota; ?></strong></article>
    <article class="stat-card"><span>Tagihan</span><strong><?php echo $jumlah_tagihan; ?></strong></article>
    <article class="stat-card"><span>Pembayaran</span><strong><?php echo $jumlah_pembayaran; ?></strong></article>
    <article class="stat-card"><span>Transaksi</span><strong><?php echo $jumlah_transaksi; ?></strong></article>
</section>

<section class="form-card">
    <h2>
        <span class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <path d="M3 6h18" />
                <path d="M8 6V4h8v2" />
                <path d="M19 6l-1 14H6L5 6" />
                <path d="M10 11v6M14 11v6" />
            </svg>
        </span>
        Konfirmasi penghapusan
    </h2>
    <p class="form-hint">Seluruh data di atas akan hilang permanen, termasuk riwayat transaksi grup
        <strong><?php echo e($g['nama_grup']); ?></strong>. Ketik <strong><?php echo e($g['nama_grup']); ?></strong>
        untuk mengaktifkan tombol hapus.</p>
    <form method="post">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <p>
            <label for="konfirmasi">Ketik nama grup</label>
            <input id="konfirmasi" name="konfirmasi" type="text" required autocomplete="off" spellcheck="false"
                placeholder="Ketik nama grup di sini" data-cocok="<?php echo e($g['nama_grup']); ?>" data-tombol="tombol-hapus-grup">
            <span class="field-hint">Harus sama persis, termasuk huruf besar/kecil dan spasi.</span>
        </p>
        <div class="form-actions">
            <button class="btn danger" type="submit" id="tombol-hapus-grup">Hapus permanen</button>
            <a class="btn secondary" href="detail.php?id=<?php echo $id; ?>">Batal</a>
        </div>
    </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
