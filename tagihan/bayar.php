<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Bayar tagihan';
$page_css = ['tagihan.css'];
$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
$id_user = (int) $_SESSION['user']['id'];
cek_role($pdo, $id_grup, akses_menu()['tagihan']);

// Kalau ukuran kiriman melebihi post_max_size, PHP mengosongkan $_POST dan $_FILES.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    flash('error', 'Ukuran bukti pembayaran terlalu besar. Maksimal 5 MB.');
    redirect('index.php');
}

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM tagihan WHERE id = ? AND id_grup = ?");
$stmt->execute([$id, $id_grup]);
$k = $stmt->fetch();

if (!$k) {
    flash('error', 'Tagihan tidak ditemukan.');
    redirect('index.php');
}

// Ikut ambil alasan penolakan terakhir, supaya anggota tahu apa yang harus diperbaiki.
$stmt = $pdo->prepare("
    SELECT ak.id, ak.status,
        (SELECT catatan FROM pembayaran WHERE id_tagihan_anggota = ak.id ORDER BY id DESC LIMIT 1) AS catatan_terakhir
    FROM tagihan_anggota ak
    WHERE ak.id_tagihan = ? AND ak.id_pengguna = ?
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
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        flash('error', 'Sesi formulir kedaluwarsa. Silakan coba lagi.');
        redirect('bayar.php?id=' . $id);
    }

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

            $stmt = $pdo->prepare("SELECT status FROM tagihan_anggota WHERE id = ? FOR UPDATE");
            $stmt->execute([$ak['id']]);
            $status_kini = $stmt->fetchColumn();

            if (!$status_kini || in_array($status_kini, ['Lunas', 'Menunggu'], true)) {
                throw new Exception('Pembayaran tidak dapat dikirim lagi untuk tagihan ini.');
            }

            $stmt = $pdo->prepare("INSERT INTO pembayaran (id_tagihan_anggota, jumlah, metode_pembayaran, berkas_bukti) VALUES (?, ?, ?, ?)");
            $stmt->execute([$ak['id'], $jumlah, $metode, $nama_file]);

            $stmt = $pdo->prepare("UPDATE tagihan_anggota SET status = 'Menunggu' WHERE id = ?");
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
        <?php csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo (int) $k['id']; ?>">
        <div class="field">
            <span class="field-label">Pilih metode pembayaran</span>
            <div class="pay-method-grid" role="radiogroup" aria-label="Metode pembayaran">
                <?php
                $metode_terpilih = $_POST['metode_pembayaran'] ?? '';
                $daftar_metode = [
                    [
                        'id' => 'metode-tunai',
                        'val' => 'Tunai',
                        'judul' => 'Tunai',
                        'desc' => 'Langsung ke Bendahara',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>',
                    ],
                    [
                        'id' => 'metode-transfer',
                        'val' => 'Transfer',
                        'judul' => 'Transfer',
                        'desc' => 'Transfer bank grup',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18M3 10h18M5 10v11M19 10v11M9 10v11M15 10v11M12 2L2 7h20L12 2z"/></svg>',
                    ],
                    [
                        'id' => 'metode-ewallet',
                        'val' => 'E-Wallet',
                        'judul' => 'E-Wallet',
                        'desc' => 'DANA, GoPay, OVO',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/><path d="M9 6h6"/></svg>',
                    ],
                    [
                        'id' => 'metode-qris',
                        'val' => 'QRIS',
                        'judul' => 'QRIS',
                        'desc' => 'Scan kode QR grup',
                        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M7 7h.01M17 7h.01M7 17h.01M17 17h.01"/></svg>',
                    ],
                ];
                foreach ($daftar_metode as $m):
                    $checked = $metode_terpilih === $m['val'] ? ' checked' : '';
                ?>
                <label for="<?php echo $m['id']; ?>" class="pay-method-card">
                    <input type="radio" id="<?php echo $m['id']; ?>" name="metode_pembayaran" value="<?php echo $m['val']; ?>" class="pay-method-radio" required<?php echo $checked; ?>>
                    <span class="pay-method-content">
                        <span class="pay-method-top">
                            <span class="pay-method-icon"><?php echo $m['icon']; ?></span>
                            <span class="pay-method-check" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            </span>
                        </span>
                        <strong class="pay-method-title"><?php echo $m['judul']; ?></strong>
                        <span class="pay-method-desc"><?php echo $m['desc']; ?></span>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
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
