<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Bayar Tagihan';
$page_css = ['kewajiban.css'];
$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
$id_user = (int) $_SESSION['user']['id'];
cek_role($pdo, $id_grup, akses_menu()['kewajiban']);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM kewajiban_pembayaran WHERE id = ? AND id_grup = ?");
$stmt->execute([$id, $id_grup]);
$k = $stmt->fetch();

if (!$k) {
    flash('error', 'Tagihan tidak ditemukan.');
    redirect('index.php');
}

$stmt = $pdo->prepare("SELECT id, status FROM anggota_kewajiban WHERE id_kewajiban = ? AND id_pengguna = ?");
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
    $tujuan = null;
    $gagal = null;

    if (!in_array($metode, ['Tunai', 'Transfer', 'E-Wallet', 'QRIS'], true)) {
        $gagal = 'Metode pembayaran tidak valid.';
    } elseif ($tunai) {
        $nama_file = '';
    } elseif (!isset($_FILES['berkas_bukti']) || $_FILES['berkas_bukti']['error'] !== UPLOAD_ERR_OK) {
        $gagal = 'Bukti pembayaran belum dipilih.';
    } else {
        $folder = __DIR__ . '/../storage/uploads/';

        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $nama_file = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($_FILES['berkas_bukti']['name']));
        $tujuan = $folder . $nama_file;

        if (!move_uploaded_file($_FILES['berkas_bukti']['tmp_name'], $tujuan)) {
            $tujuan = null;
            $nama_file = '';
            $gagal = 'Bukti pembayaran gagal diupload.';
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

            flash('success', 'Pembayaran terkirim. Menunggu verifikasi Bendahara/Admin.');
            redirect('index.php');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($tujuan !== null && is_file($tujuan)) {
                unlink($tujuan);
            }
            flash('error', $e->getMessage());
        }
    }
}

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
    <h1>Bayar Tagihan</h1>
    <p><?php echo e($k['judul']); ?> &middot; Tenggat <?php echo e($k['tenggat_waktu']); ?></p>
</div>

<section class="form-card form-card--create">
    <h2>
        <span class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <path d="M17 7L7 17M7 17h8M7 17V9" />
            </svg>
        </span>
        Detail Pembayaran
    </h2>
    <p class="form-hint">Setelah dikirim, pembayaran masuk ke tab Verifikasi untuk disetujui Bendahara atau Admin.</p>
    <form method="post" enctype="multipart/form-data" id="form-bayar">
        <input type="hidden" name="id" value="<?php echo (int) $k['id']; ?>">
        <div class="form-grid">
            <p>
                <label>Jumlah Tagihan</label>
                <input type="text" value="<?php echo e(rupiah($k['jumlah'])); ?>" readonly>
                <span class="field-hint">Nominal terkunci sesuai jumlah tagihan.</span>
            </p>
            <p>
                <label for="metode_pembayaran">Metode</label>
                <select id="metode_pembayaran" name="metode_pembayaran" required>
                    <option value="">Pilih</option>
                    <option value="Tunai">Tunai</option>
                    <option value="Transfer">Transfer</option>
                    <option value="E-Wallet">E-Wallet</option>
                    <option value="QRIS">QRIS</option>
                </select>
                <span class="field-hint">Tunai tidak perlu melampirkan bukti.</span>
            </p>
        </div>
        <p id="field-bukti">
            <label for="berkas_bukti">Bukti Pembayaran</label>
            <input id="berkas_bukti" type="file" name="berkas_bukti" accept=".jpg,.jpeg,.png,.pdf" required>
            <span class="field-hint">Format JPG, PNG, atau PDF. Wajib untuk Transfer, E-Wallet, dan QRIS.</span>
        </p>
        <div class="form-actions">
            <button class="btn primary" type="submit">Kirim Pembayaran</button>
        </div>
    </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
