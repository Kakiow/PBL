<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Verifikasi';
$page_css = ['table.css'];

$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
$role = cek_role($pdo, $id_grup, akses_menu()['pembayaran']);
$boleh_verifikasi = in_array($role, ['Admin', 'Bendahara'], true);

// Pastikan bukti kedaluwarsa tidak tampil sebagai link yang sudah rusak.
bersihkan_bukti_otomatis($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $boleh_verifikasi) {
    $id = (int) ($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if (in_array($status, ['Disetujui', 'Ditolak'])) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT p.*, ak.id_kewajiban, ak.id_pengguna, ak.status AS status_kewajiban
                FROM pembayaran p
                JOIN anggota_kewajiban ak ON ak.id = p.id_anggota_kewajiban
                JOIN kewajiban_pembayaran k ON k.id = ak.id_kewajiban
                WHERE p.id = ? AND k.id_grup = ?
                FOR UPDATE
            ");
            $stmt->execute([$id, $id_grup]);
            $bayar = $stmt->fetch();

            if (!$bayar) {
                throw new Exception('Pembayaran tidak ditemukan.');
            }

            if ($bayar['status'] !== 'Menunggu') {
                throw new Exception('Pembayaran ini sudah diproses.');
            }

            if ($bayar['status_kewajiban'] === 'Lunas') {
                throw new Exception('Kewajiban ini sudah lunas.');
            }

            $stmt = $pdo->prepare("UPDATE pembayaran SET status = ?, diverifikasi_oleh = ?, diverifikasi_pada = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$status, $_SESSION['user']['id'], $id]);

            $stmt = $pdo->prepare("UPDATE anggota_kewajiban SET status = ? WHERE id = ?");
            $stmt->execute([$status === 'Disetujui' ? 'Lunas' : 'Ditolak', $bayar['id_anggota_kewajiban']]);

            if ($status === 'Disetujui') {
                $stmt = $pdo->prepare("SELECT id FROM kategori WHERE id_grup = ? AND jenis = 'Pemasukan' ORDER BY id LIMIT 1");
                $stmt->execute([$id_grup]);
                $kategori = $stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT id FROM akun_dana WHERE id_grup = ? ORDER BY id LIMIT 1");
                $stmt->execute([$id_grup]);
                $akun = $stmt->fetchColumn();

                if (!$kategori || !$akun) {
                    throw new Exception('Kategori pemasukan dan akun dana harus tersedia.');
                }

                $stmt = $pdo->prepare("SELECT 1 FROM transaksi WHERE id_pembayaran = ?");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn()) {
                    throw new Exception('Transaksi untuk pembayaran ini sudah tercatat.');
                }

                $stmt = $pdo->prepare("
                    INSERT INTO transaksi
                        (id_grup, id_pengguna, id_kategori, id_akun_dana, id_pembayaran, jenis, jumlah, tanggal_transaksi, deskripsi)
                    VALUES (?, ?, ?, ?, ?, 'Pemasukan', ?, CURRENT_DATE, ?)
                ");
                $stmt->execute([
                    $id_grup,
                    $_SESSION['user']['id'],
                    $kategori,
                    $akun,
                    $id,
                    $bayar['jumlah'],
                    'Pembayaran kewajiban',
                ]);
            }

            $pdo->commit();
            flash('success', 'Pembayaran berhasil diverifikasi.');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', $e->getMessage());
        }
    }

    redirect('index.php');
}

$sql = "
    SELECT p.*, u.nama, k.judul
    FROM pembayaran p
    JOIN anggota_kewajiban ak ON ak.id = p.id_anggota_kewajiban
    JOIN pengguna u ON u.id = ak.id_pengguna
    JOIN kewajiban_pembayaran k ON k.id = ak.id_kewajiban
    WHERE k.id_grup = ?
    ORDER BY p.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_grup]);
$data = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <span class="eyebrow">Verifikasi</span>
    <h1>Verifikasi Pembayaran</h1>
    <p>Pantau dan verifikasi pembayaran yang dikirim anggota.</p>
</div>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Daftar Pembayaran</h2>
            <p><?php echo $boleh_verifikasi
                    ? 'Setujui atau tolak pembayaran yang masih menunggu verifikasi.'
                    : 'Anda hanya dapat melihat status pembayaran.'; ?></p>
        </div>
    </div>
    <div class="table-fancy-wrap">
        <table class="table-fancy">
            <thead>
                <tr>
                    <th>Anggota</th>
                    <th>Tagihan</th>
                    <th class="num">Jumlah</th>
                    <th>Metode</th>
                    <th>Bukti</th>
                    <th>Status</th>
                    <?php if ($boleh_verifikasi): ?><th class="num">Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!$data): ?><tr class="empty">
                    <td colspan="<?php echo $boleh_verifikasi ? 7 : 6; ?>">Belum ada pembayaran.</td>
                </tr><?php endif; ?>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td><?php echo e($row['nama']); ?></td>
                    <td class="desc"><?php echo e($row['judul']); ?></td>
                    <td class="num"><?php echo rupiah($row['jumlah']); ?></td>
                    <td><?php echo e($row['metode_pembayaran']); ?></td>
                    <td>
                        <?php if (!empty($row['berkas_bukti'])): ?>
                        <a href="../storage/uploads/<?php echo e($row['berkas_bukti']); ?>" target="_blank">Lihat
                            Bukti</a>
                        <?php else: ?><span class="muted">-</span><?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $kelas = $row['status'] === 'Disetujui' ? 'success' : ($row['status'] === 'Ditolak' ? 'danger' : 'warning');
                        ?>
                        <span class="badge <?php echo $kelas; ?>"><?php echo e($row['status']); ?></span>
                    </td>
                    <?php if ($boleh_verifikasi): ?>
                    <td>
                        <?php if ($row['status'] === 'Menunggu'): ?>
                        <div class="row-actions">
                            <form method="post" class="inline-form">
                                <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                <button name="status" value="Disetujui" class="btn success small">Setujui</button>
                                <button name="status" value="Ditolak" class="btn ghost-danger small">Tolak</button>
                            </form>
                        </div>
                        <?php else: ?><span class="muted">-</span><?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
