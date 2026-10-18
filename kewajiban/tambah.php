<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Buat Tagihan';
$page_css = ['kewajiban.css'];
$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
$role = cek_role($pdo, $id_grup, akses_menu()['kewajiban']);

if (!in_array($role, ['Admin', 'Bendahara'], true)) {
    flash('error', 'Hanya Admin dan Bendahara yang dapat membuat tagihan.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $jumlah = (float) ($_POST['jumlah'] ?? 0);
    $tenggat = $_POST['tenggat_waktu'] ?? '';
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($judul === '' || $jumlah <= 0 || $tenggat === '') {
        flash('error', 'Judul, jumlah, dan tenggat wajib diisi.');
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO kewajiban_pembayaran (id_grup,judul,jumlah,tenggat_waktu,deskripsi,dibuat_oleh) VALUES (?,?,?,?,?,?) RETURNING id");
            $stmt->execute([$id_grup, $judul, $jumlah, $tenggat, $deskripsi, $_SESSION['user']['id']]);
            $id = $stmt->fetchColumn();
            $stmt = $pdo->prepare("SELECT id_pengguna FROM anggota_grup WHERE id_grup=?");
            $stmt->execute([$id_grup]);
            $ins = $pdo->prepare("INSERT INTO anggota_kewajiban (id_kewajiban,id_pengguna) VALUES (?,?)");
            foreach ($stmt->fetchAll() as $a) {
                $ins->execute([$id, $a['id_pengguna']]);
            }
            $pdo->commit();
            flash('success', 'Tagihan berhasil dibuat.');
            redirect('index.php');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', 'Gagal membuat tagihan.');
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
    <h1>Buat Tagihan</h1>
    <p>Tagihan ini otomatis dibebankan ke seluruh anggota grup.</p>
</div>

<section class="form-card form-card--create">
    <h2>
        <span class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <path d="M12 5v14M5 12h14" />
            </svg>
        </span>
        Detail Tagihan
    </h2>
    <p class="form-hint">Isi judul, nominal, dan tenggat pembayaran. Status lunas tiap anggota dapat dipantau dari
        daftar tagihan.</p>
    <form method="post" class="form-kewajiban">
        <div class="form-grid">
            <p class="field field--full">
                <label for="judul">Judul</label>
                <input id="judul" name="judul" type="text" maxlength="120" required
                    data-error="Judul kewajiban wajib diisi." placeholder="Contoh: Kas mingguan Oktober">
                <span class="field-hint">Nama singkat tagihan agar mudah dikenali anggota.</span>
            </p>
            <p class="field">
                <label for="jumlah">Jumlah</label>
                <span class="input-affix">
                    <span class="input-affix-label" aria-hidden="true">Rp</span>
                    <input id="jumlah" name="jumlah" type="number" min="1" step="any" inputmode="numeric" required
                        data-error="Jumlah harus lebih dari 0." placeholder="25000">
                </span>
                <span class="chip-row">
                    <button type="button" class="chip-btn" data-nominal="10000">10.000</button>
                    <button type="button" class="chip-btn" data-nominal="25000">25.000</button>
                    <button type="button" class="chip-btn" data-nominal="50000">50.000</button>
                    <button type="button" class="chip-btn" data-nominal="100000">100.000</button>
                </span>
                <span class="field-hint" id="jumlah-preview">Pilih nominal cepat atau isi manual.</span>
            </p>
            <p class="field">
                <label for="tenggat_waktu">Tenggat</label>
                <input id="tenggat_waktu" name="tenggat_waktu" type="date" required
                    data-error="Tenggat pembayaran wajib dipilih.">
                <span class="chip-row">
                    <button type="button" class="chip-btn" data-tenggat="0">Hari ini</button>
                    <button type="button" class="chip-btn" data-tenggat="7">+7 hari</button>
                    <button type="button" class="chip-btn" data-tenggat="akhir-bulan">Akhir bulan</button>
                </span>
                <span class="field-hint" id="tenggat-preview">Tanggal terakhir pembayaran.</span>
            </p>
            <p class="field field--full">
                <label for="deskripsi">Deskripsi <span class="label-optional">opsional</span></label>
                <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="200"
                    placeholder="Contoh: Iuran kas untuk kegiatan bakti sosial."></textarea>
                <span class="field-hint field-hint--counter" id="deskripsi-counter">0/200</span>
            </p>
        </div>
        <div class="form-actions">
            <button class="btn primary" type="submit" data-loading-text="Menyimpan…">Buat Tagihan</button>
            <span class="form-status" role="status" aria-live="polite"></span>
        </div>
    </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
