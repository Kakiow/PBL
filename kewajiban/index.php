<?php
session_start();
require_once __DIR__.'/../config/koneksi.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/auth.php';
$page_title = 'Kewajiban Pembayaran';
$page_css = ['kewajiban.css'];
$id_grup = (int)($_SESSION['grup_id'] ?? 0);
$role = cek_role($pdo, $id_grup, akses_menu()['kewajiban']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($role, ['Admin','Bendahara'])) {
    $judul = trim($_POST['judul'] ?? '');
    $jumlah = (float)($_POST['jumlah'] ?? 0);
    $tenggat = $_POST['tenggat_waktu'] ?? '';
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    if ($judul === '' || $jumlah <= 0 || $tenggat === '') {
        flash('error', 'Judul, jumlah, dan tenggat wajib diisi.');
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO kewajiban_pembayaran (id_grup,judul,jumlah,tenggat_waktu,deskripsi,dibuat_oleh) VALUES (?,?,?,?,?,?) RETURNING id");
            $stmt->execute([$id_grup,$judul,$jumlah,$tenggat,$deskripsi,$_SESSION['user']['id']]);
            $id = $stmt->fetchColumn();
            $stmt = $pdo->prepare("SELECT id_pengguna FROM anggota_grup WHERE id_grup=?");
            $stmt->execute([$id_grup]);
            $ins = $pdo->prepare("INSERT INTO anggota_kewajiban (id_kewajiban,id_pengguna) VALUES (?,?)");
            foreach ($stmt->fetchAll() as $a) {
                $ins->execute([$id,$a['id_pengguna']]);
            }$pdo->commit();
            flash('success', 'Kewajiban berhasil dibuat.');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }flash('error', 'Gagal membuat kewajiban.');
        }
    }
    redirect('index.php');
}
$stmt = $pdo->prepare("SELECT k.*, (SELECT COUNT(*) FROM anggota_kewajiban ak WHERE ak.id_kewajiban=k.id AND ak.status='Lunas') AS lunas, (SELECT COUNT(*) FROM anggota_kewajiban ak WHERE ak.id_kewajiban=k.id) AS total FROM kewajiban_pembayaran k WHERE k.id_grup=? ORDER BY k.tenggat_waktu DESC");
$stmt->execute([$id_grup]);
$data = $stmt->fetchAll();
include __DIR__.'/../includes/header.php';
?>
<section>
    <div class="section-heading">
        <div>
            <h1>Kewajiban Pembayaran</h1>
            <p class="muted">Buat tagihan kas dan pantau status pembayaran anggota.</p>
        </div>
    </div><?php if (in_array($role, ['Admin','Bendahara'])):?>
    <div class="form-card form-card--create">
        <h2>
            <span class="card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M12 5v14M5 12h14" />
                </svg>
            </span>
            Buat Kewajiban
        </h2>
        <p class="form-hint">Tagihan ini otomatis dibebankan ke seluruh anggota grup dan status lunasnya dapat
            dipantau dari daftar di bawah.</p>
        <form method="post" class="form-kewajiban">
            <div class="form-grid form-grid--kewajiban">
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
                        <input id="jumlah" name="jumlah" type="number" min="1" step="any" inputmode="numeric"
                            required data-error="Jumlah harus lebih dari 0." placeholder="25000">
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
                <button class="btn primary" type="submit" data-loading-text="Menyimpan…">Buat Kewajiban</button>
                <span class="form-status" role="status" aria-live="polite"></span>
            </div>
        </form>
    </div><?php endif;?>
</section>
<section>
    <h2>Daftar Kewajiban</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Jumlah</th>
                    <th>Tenggat</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody><?php if (!$data):?><tr>
                    <td colspan="5">Belum ada kewajiban.</td>
                </tr><?php endif;?><?php foreach ($data as $row):?><tr>
                    <td><?php echo e($row['judul']);?></td>
                    <td><?php echo rupiah($row['jumlah']);?></td>
                    <td><?php echo e($row['tenggat_waktu']);?></td>
                    <td><?php echo $row['lunas'].' / '.$row['total'].' lunas';?></td>
                    <td><a class="btn secondary" href="detail.php?id=<?php echo $row['id'];?>">Detail</a></td>
                </tr><?php endforeach;?></tbody>
        </table>
    </div>
</section>
<?php include __DIR__.'/../includes/footer.php';?>