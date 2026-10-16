<?php
session_start();
require_once __DIR__.'/../config/koneksi.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/auth.php';
$page_title = 'Tambah Transaksi';
$page_css = ['transaksi.css', 'transaksi-jenis.css', 'transaksi-fields.css', 'transaksi-preview.css'];
$page_js = ['transaksi.js'];
$id_grup = (int)($_SESSION['grup_id'] ?? 0);
cek_role($pdo, $id_grup, akses_menu()['transaksi']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kategori = (int)($_POST['id_kategori'] ?? 0);
    $akun = (int)($_POST['id_akun_dana'] ?? 0);
    $jenis = $_POST['jenis'] ?? '';
    $jumlah = (float)($_POST['jumlah'] ?? 0);
    $tanggal = $_POST['tanggal_transaksi'] ?? '';
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    if (!$kategori || !$akun || !in_array($jenis, ['Pemasukan','Pengeluaran']) || $jumlah <= 0 || $tanggal === '') {
        flash('error', 'Semua data transaksi wajib diisi.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO transaksi (id_grup,id_pengguna,id_kategori,id_akun_dana,jenis,jumlah,tanggal_transaksi,deskripsi) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$id_grup,$_SESSION['user']['id'],$kategori,$akun,$jenis,$jumlah,$tanggal,$deskripsi]);
        flash('success', 'Transaksi berhasil ditambahkan.');
        redirect('index.php');
    }
}
$stmt = $pdo->prepare("SELECT * FROM kategori WHERE id_grup=? ORDER BY nama_kategori");
$stmt->execute([$id_grup]);
$kategori = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT * FROM akun_dana WHERE id_grup=? ORDER BY nama_akun");
$stmt->execute([$id_grup]);
$akun = $stmt->fetchAll();
include __DIR__.'/../includes/header.php';
?>
<section>
    <div class="section-heading trx-heading">
        <div>
            <a class="trx-back" href="index.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5M12 19l-7-7 7-7" />
                </svg>
                Kembali
            </a>
            <h1>Tambah Transaksi</h1>
            <p class="muted">Catat pemasukan atau pengeluaran baru dan pantau ringkasannya secara langsung.</p>
        </div>
    </div>
    <div class="form-card trx-card">
        <div class="trx-card-head">
            <span class="trx-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M4 4h16v16l-3-2-2 2-3-2-3 2-2-2-3 2z" />
                    <path d="M8 9h8M8 13h5" />
                </svg>
            </span>
            <h2>Detail Transaksi</h2>
        </div>
        <form method="post" class="form-transaksi">
            <div class="trx-layout">
                <div class="trx-fields">
                    <p class="field">
                        <label>Jenis Transaksi</label>
                        <span class="jenis-toggle" role="radiogroup" aria-label="Jenis transaksi">
                            <label class="jenis-option jenis-option--in">
                                <input type="radio" name="jenis" value="Pemasukan" required
                                    data-error="Pilih jenis transaksi terlebih dahulu.">
                                <span class="jenis-body">
                                    <span class="jenis-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17 7L7 17M7 17h8M7 17V9" />
                                        </svg>
                                    </span>
                                    <span class="jenis-text">
                                        <strong>Pemasukan</strong>
                                        <small>Dana masuk ke kas</small>
                                    </span>
                                    <span class="jenis-check" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 6L9 17l-5-5" />
                                        </svg>
                                    </span>
                                </span>
                            </label>
                            <label class="jenis-option jenis-option--out">
                                <input type="radio" name="jenis" value="Pengeluaran" required
                                    data-error="Pilih jenis transaksi terlebih dahulu.">
                                <span class="jenis-body">
                                    <span class="jenis-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M7 17L17 7M17 7h-8M17 7v8" />
                                        </svg>
                                    </span>
                                    <span class="jenis-text">
                                        <strong>Pengeluaran</strong>
                                        <small>Dana keluar dari kas</small>
                                    </span>
                                    <span class="jenis-check" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 6L9 17l-5-5" />
                                        </svg>
                                    </span>
                                </span>
                            </label>
                        </span>
                    </p>
                    <div class="trx-row">
                        <p class="field">
                            <label for="id_kategori">Kategori</label>
                            <select id="id_kategori" name="id_kategori" required data-error="Kategori wajib dipilih.">
                                <option value="">Pilih kategori</option><?php foreach ($kategori as $k):?><option
                                    value="<?php echo $k['id'];?>">
                                    <?php echo e($k['nama_kategori'].' - '.$k['jenis']);?></option>
                                <?php endforeach;?>
                            </select>
                        </p>
                        <p class="field">
                            <label for="id_akun_dana">Akun Dana</label>
                            <select id="id_akun_dana" name="id_akun_dana" required
                                data-error="Akun dana wajib dipilih.">
                                <option value="">Pilih akun</option><?php foreach ($akun as $a):?><option
                                    value="<?php echo $a['id'];?>"><?php echo e($a['nama_akun']);?></option>
                                <?php endforeach;?>
                            </select>
                        </p>
                    </div>
                    <div class="trx-row">
                        <p class="field">
                            <label for="jumlah">Jumlah</label>
                            <span class="input-affix">
                                <span class="input-affix-label" aria-hidden="true">Rp</span>
                                <input id="jumlah" name="jumlah" type="number" min="1" step="any" inputmode="numeric"
                                    required data-error="Jumlah harus lebih dari 0." placeholder="50000">
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
                            <label for="tanggal_transaksi">Tanggal</label>
                            <input id="tanggal_transaksi" name="tanggal_transaksi" type="date"
                                value="<?php echo date('Y-m-d');?>" required
                                data-error="Tanggal transaksi wajib diisi.">
                            <span class="chip-row">
                                <button type="button" class="chip-btn" data-tanggal="0">Hari ini</button>
                                <button type="button" class="chip-btn" data-tanggal="-1">Kemarin</button>
                                <button type="button" class="chip-btn" data-tanggal="7">+7 hari</button>
                            </span>
                            <span class="field-hint" id="tanggal-preview">Tanggal pencatatan transaksi.</span>
                        </p>
                    </div>
                    <p class="field">
                        <label for="deskripsi">Deskripsi <span class="label-optional">opsional</span></label>
                        <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="200"
                            placeholder="Contoh: Iuran kas untuk kegiatan bakti sosial."></textarea>
                        <span class="field-hint field-hint--counter" id="deskripsi-counter">0/200</span>
                    </p>
                </div>
                <aside class="trx-preview" aria-label="Ringkasan transaksi">
                    <span class="trx-preview-badge">Ringkasan</span>
                    <span class="trx-preview-amount">
                        <small>Jumlah tercatat</small>
                        <strong id="trx-preview-amount">Rp 0</strong>
                    </span>
                    <dl class="trx-preview-list">
                        <div>
                            <dt>Jenis</dt>
                            <dd class="is-empty" id="trx-preview-jenis">—</dd>
                        </div>
                        <div>
                            <dt>Kategori</dt>
                            <dd class="is-empty" id="trx-preview-kategori">—</dd>
                        </div>
                        <div>
                            <dt>Akun Dana</dt>
                            <dd class="is-empty" id="trx-preview-akun">—</dd>
                        </div>
                        <div>
                            <dt>Tanggal</dt>
                            <dd class="is-empty" id="trx-preview-tanggal">—</dd>
                        </div>
                    </dl>
                </aside>
            </div>
            <div class="trx-actions">
                <button class="btn primary" type="submit" data-loading-text="Menyimpan…">Simpan Transaksi</button>
                <span class="form-status" role="status" aria-live="polite"></span>
            </div>
        </form>
    </div>
</section><?php include __DIR__.'/../includes/footer.php';?>
