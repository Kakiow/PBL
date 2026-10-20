<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Bayar tagihan';
$page_css = ['kewajiban.css'];
$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
$id_user = (int) $_SESSION['user']['id'];
cek_role($pdo, $id_grup, akses_menu()['kewajiban']);

// Kalau ukuran kiriman melebihi post_max_size, PHP mengosongkan $_POST dan $_FILES.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    flash('error', 'Ukuran bukti pembayaran terlalu besar. Maksimal 5 MB.');
    redirect('index.php');
}

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM kewajiban_pembayaran WHERE id = ? AND id_grup = ?");
$stmt->execute([$id, $id_grup]);
$k = $stmt->fetch();

if (!$k) {
    flash('error', 'Tagihan tidak ditemukan.');
    redirect('index.php');
}

// Ikut ambil alasan penolakan terakhir, supaya anggota tahu apa yang harus diperbaiki.
$stmt = $pdo->prepare("
    SELECT ak.id, ak.status,
        (SELECT catatan FROM pembayaran WHERE id_anggota_kewajiban = ak.id ORDER BY id DESC LIMIT 1) AS catatan_terakhir
    FROM anggota_kewajiban ak
    WHERE ak.id_kewajiban = ? AND ak.id_pengguna = ?
");
$stmt->execute([$id, $id_user]);
$ak = $stmt->fetch();

if (!$ak) {
    flash('error', 'Anda bukan anggota pada tagihan ini.');
    redirect('index.php');
}

if (in_array($ak['status'], ['Lunas', 'Menunggu'], true)) {
    flash('error', $ak['status'] === 'Lunas'
        ? 'Tagihan ini sudah lunas.'
        : 'Pembayaran Anda untuk tagihan ini sedang menunggu verifikasi.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metode = $_POST['metode_pembayaran'] ?? '';
    $jumlah = (float) $k['jumlah'];
    $tunai = $metode === 'Tunai';
    $nama_file = '';
    $gagal = null;

    if (!in_array($metode, ['Tunai', 'Transfer', 'E-Wallet', 'QRIS'], true)) {
        $gagal = 'Metode pembayaran tidak valid.';
    } elseif ($tunai) {
        $nama_file = '';
    } elseif (!isset($_FILES['berkas_bukti']) || (int) $_FILES['berkas_bukti']['error'] === UPLOAD_ERR_NO_FILE) {
        $gagal = 'Bukti pembayaran belum dipilih.';
    } elseif (tagihan_bukti_kedaluwarsa($k['tenggat_waktu'])) {
        $gagal = 'Tenggat tagihan sudah lewat lebih dari ' . BUKTI_HARI_SIMPAN
            . ' hari. Bukti pembayaran tidak dapat diunggah; silakan bayar tunai atau hubungi Bendahara/Admin.';
    } else {
        try {
            $nama_file = simpan_bukti($_FILES['berkas_bukti']);
        } catch (Throwable $e) {
            $gagal = $e->getMessage();
        }
    }

    if ($gagal !== null) {
        flash('error', $gagal);
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT status FROM anggota_kewajiban WHERE id = ? FOR UPDATE");
            $stmt->execute([$ak['id']]);
            $status_kini = $stmt->fetchColumn();

            if (!$status_kini || in_array($status_kini, ['Lunas', 'Menunggu'], true)) {
                throw new Exception('Pembayaran tidak dapat dikirim lagi untuk tagihan ini.');
            }

            $stmt = $pdo->prepare("INSERT INTO pembayaran (id_anggota_kewajiban, jumlah, metode_pembayaran, berkas_bukti) VALUES (?, ?, ?, ?)");
            $stmt->execute([$ak['id'], $jumlah, $metode, $nama_file]);

            $stmt = $pdo->prepare("UPDATE anggota_kewajiban SET status = 'Menunggu' WHERE id = ?");
            $stmt->execute([$ak['id']]);

            $pdo->commit();

            flash('success', 'Pembayaran terkirim. Bendahara akan memeriksanya; pantau statusnya di sini.');
            redirect('detail.php?id=' . $id);
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($nama_file !== '') {
                hapus_bukti($nama_file);
            }
            flash('error', $e->getMessage());
        }
    }
}

$info_pembayaran = trim((string) (grup_aktif($pdo)['info_pembayaran'] ?? ''));
$tenggat = info_tenggat($k['tenggat_waktu']);

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <?php tautan_kembali('detail.php?id=' . (int) $k['id']); ?>
    <h1>Bayar tagihan</h1>
    <p><?php echo e($k['judul']); ?> &middot; tenggat <?php echo e(tanggal_id($k['tenggat_waktu'])); ?> (<?php echo e(strtolower($tenggat['teks'])); ?>)</p>
</div>

<section class="form-card">
    <h2>
        <span class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <rect x="2.5" y="5" width="19" height="14" rx="2.5" />
                <path d="M2.5 10h19M7 15h3" />
            </svg>
        </span>
        Detail pembayaran
    </h2>

    <?php if ($ak['status'] === 'Ditolak'): ?>
    <div class="notice is-danger" role="status">
        <strong>Pembayaran sebelumnya ditolak</strong>
        <span class="notice-body"><?php echo trim((string) $ak['catatan_terakhir']) !== '' ? e($ak['catatan_terakhir']) : 'Bendahara tidak menuliskan alasan. Hubungi Bendahara bila perlu.'; ?></span>
    </div>
    <?php endif; ?>

    <div class="pay-amount">
        <span>Jumlah yang dibayar</span>
        <strong><?php echo rupiah($k['jumlah']); ?></strong>
        <small>Nominal sesuai tagihan dan tidak bisa diubah.</small>
    </div>

    <form method="post" enctype="multipart/form-data" id="form-bayar">
        <input type="hidden" name="id" value="<?php echo (int) $k['id']; ?>">
        <p class="field">
            <label for="metode_pembayaran">Metode pembayaran</label>
            <select id="metode_pembayaran" name="metode_pembayaran" required>
                <option value="">Pilih metode</option>
                <option value="Tunai">Tunai (serahkan langsung ke Bendahara)</option>
                <option value="Transfer">Transfer bank</option>
                <option value="E-Wallet">E-Wallet</option>
                <option value="QRIS">QRIS</option>
            </select>
        </p>
        <div class="notice" id="info-tujuan">
            <strong>Kirim ke</strong>
            <span class="notice-body"><?php echo $info_pembayaran !== ''
                ? e($info_pembayaran)
                : 'Bendahara belum menambahkan nomor rekening atau e-wallet grup. Tanyakan tujuan transfer ke Bendahara sebelum membayar.'; ?></span>
        </div>
        <div class="field" id="field-bukti">
            <label for="berkas_bukti">Bukti pembayaran</label>
            <span class="file-pick">
                <input id="berkas_bukti" class="file-pick-input" type="file" name="berkas_bukti"
                    accept=".jpg,.jpeg,.png,.pdf" required>
                <label for="berkas_bukti" class="btn secondary small file-pick-btn">Pilih file</label>
                <span class="file-pick-name" id="berkas-nama">Belum ada file dipilih</span>
            </span>
            <span class="field-hint">Foto atau screenshot bukti transfer: JPG, PNG, atau PDF, maksimal 5 MB.</span>
        </div>
        <div class="form-actions">
            <button class="btn primary" type="submit">Kirim pembayaran</button>
            <span class="field-hint">Setelah dikirim, Bendahara memeriksa pembayaranmu sebelum ditandai lunas.</span>
        </div>
    </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
