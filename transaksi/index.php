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
    <h1>Transaksi</h1>
    <p>Catatan seluruh pemasukan dan pengeluaran grup.</p>
</div>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Riwayat transaksi</h2>
            <p><?php echo $kelola
                    ? 'Kelola pemasukan dan pengeluaran kas grup.'
                    : 'Riwayat pemasukan dan pengeluaran kas grup. Hanya Admin dan Bendahara yang bisa mengubahnya.'; ?></p>
        </div>
        <?php if ($kelola): ?>
        <a class="btn primary" href="tambah.php">Catat transaksi</a>
        <?php endif; ?>
    </div>
    <div class="table-fancy-wrap">
        <table class="table-fancy table-stack">
            <thead>
                <tr>
                    <th scope="col">Deskripsi</th>
                    <th scope="col">Tanggal</th>
                    <th scope="col">Kategori</th>
                    <th scope="col">Akun dana</th>
                    <th scope="col" class="num">Jumlah</th>
                    <th scope="col">Dicatat oleh</th>
                    <?php if ($kelola): ?><th scope="col" class="num"><span class="sr-only">Aksi</span></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!$data): ?><tr class="empty">
                    <td colspan="<?php echo $kelola ? 7 : 6; ?>"><?php echo $kelola ? 'Belum ada transaksi. Catat pemasukan atau pengeluaran pertama grup ini.' : 'Belum ada transaksi.'; ?></td>
                </tr><?php endif; ?>
                <?php foreach ($data as $row): $masuk = $row['jenis'] === 'Pemasukan'; ?>
                <tr>
                    <td class="cell-title">
                        <?php echo e($row['deskripsi'] ?: $row['nama_kategori']); ?>
                        <?php if ($row['id_pembayaran']): ?><span class="cell-sub">Dari verifikasi pembayaran</span><?php endif; ?>
                    </td>
                    <td class="nowrap" data-label="Tanggal"><?php echo e(tanggal_id($row['tanggal_transaksi'])); ?></td>
                    <td data-label="Kategori"><span class="chip"><?php echo e($row['nama_kategori']); ?></span></td>
                    <td data-label="Akun dana"><?php echo e($row['nama_akun']); ?></td>
                    <td class="num <?php echo $masuk ? 'amount-in' : 'amount-out'; ?>" data-label="<?php echo e($row['jenis']); ?>">
                        <span class="sr-only"><?php echo e($row['jenis']); ?> </span><?php echo $masuk ? '+' : '&minus;'; ?> <?php echo rupiah($row['jumlah']); ?>
                    </td>
                    <td class="nowrap" data-label="Dicatat oleh"><?php echo e($row['nama_pengguna']); ?></td>
                    <?php if ($kelola): ?>
                    <td class="cell-actions">
                        <div class="row-actions">
                            <a class="btn secondary small" href="edit.php?id=<?php echo (int) $row['id']; ?>">Edit</a>
                            <form method="post" class="inline-form"
                                data-konfirmasi="Hapus transaksi <?php echo e(($masuk ? 'pemasukan ' : 'pengeluaran ') . rupiah($row['jumlah'])); ?>? Saldo kas akan berubah.">
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                <button class="btn ghost-danger small" type="submit">Hapus</button>
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
