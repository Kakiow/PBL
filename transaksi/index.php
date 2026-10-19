<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Transaksi';
$page_css = ['table.css'];
$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
$role = cek_role($pdo, $id_grup, akses_menu()['transaksi']);
$kelola = boleh_kelola_transaksi($role);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $kelola) {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id = ? AND id_grup = ?");
            $stmt->execute([$id, $id_grup]);
            flash('success', 'Transaksi berhasil dihapus.');
        } catch (PDOException $e) {
            flash('error', 'Transaksi tidak dapat dihapus.');
        }
        redirect('index.php');
    }
}

$stmt = $pdo->prepare("SELECT t.*, k.nama_kategori, a.nama_akun, u.nama AS nama_pengguna FROM transaksi t JOIN kategori k ON k.id = t.id_kategori JOIN akun_dana a ON a.id = t.id_akun_dana JOIN pengguna u ON u.id = t.id_pengguna WHERE t.id_grup = ? ORDER BY t.tanggal_transaksi DESC, t.id DESC");
$stmt->execute([$id_grup]);
$data = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <span class="eyebrow">Transaksi</span>
    <h1>Transaksi</h1>
    <p>Catatan seluruh pemasukan dan pengeluaran grup.</p>
</div>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Riwayat Transaksi</h2>
            <p><?php echo $kelola
                    ? 'Kelola pemasukan dan pengeluaran kas grup.'
                    : 'Anda hanya dapat melihat riwayat transaksi grup.'; ?></p>
        </div>
        <?php if ($kelola): ?>
        <a class="btn primary" href="tambah.php">+ Tambah Transaksi</a>
        <?php endif; ?>
    </div>
    <div class="table-fancy-wrap">
        <table class="table-fancy">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Jenis</th>
                    <th>Akun Dana</th>
                    <th class="num">Jumlah</th>
                    <th>Deskripsi</th>
                    <th>Dibuat Oleh</th>
                    <?php if ($kelola): ?><th class="num">Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!$data): ?><tr class="empty">
                    <td colspan="<?php echo $kelola ? 8 : 7; ?>">Belum ada transaksi.</td>
                </tr><?php endif; ?>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td class="nowrap"><?php echo e($row['tanggal_transaksi']); ?></td>
                    <td><span class="chip"><?php echo e($row['nama_kategori']); ?></span></td>
                    <td><span class="badge <?php echo $row['jenis'] === 'Pemasukan' ? 'success' : 'danger'; ?>"><?php echo e($row['jenis']); ?></span></td>
                    <td><?php echo e($row['nama_akun']); ?></td>
                    <td class="num <?php echo $row['jenis'] === 'Pemasukan' ? 'amount-in' : 'amount-out'; ?>">
                        <?php echo $row['jenis'] === 'Pemasukan' ? '+' : '&minus;'; ?> <?php echo rupiah($row['jumlah']); ?>
                    </td>
                    <td class="desc">
                        <?php echo e($row['deskripsi']); ?>
                        <?php if ($row['id_pembayaran']): ?><span class="chip">Dari verifikasi</span><?php endif; ?>
                    </td>
                    <td class="nowrap"><?php echo e($row['nama_pengguna']); ?></td>
                    <?php if ($kelola): ?>
                    <td>
                        <div class="row-actions">
                            <a class="btn secondary small" href="edit.php?id=<?php echo (int) $row['id']; ?>">Edit</a>
                            <form method="post" class="inline-form" data-konfirmasi="Apakah Anda yakin ingin menghapus data ini?">
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                <button class="btn danger small" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
