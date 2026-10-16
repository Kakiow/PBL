<?php
session_start();
require_once __DIR__.'/../config/koneksi.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/auth.php';
$page_title = 'Kategori';
$id_grup = (int)($_SESSION['grup_id'] ?? 0);
cek_role($pdo, $id_grup, ['Admin','Bendahara','Anggota']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'tambah') {
        $nama = trim($_POST['nama_kategori'] ?? '');
        $jenis = $_POST['jenis'] ?? '';
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        if ($nama === '' || !in_array($jenis, ['Pemasukan','Pengeluaran'])) {
            flash('error', 'Data kategori belum lengkap.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO kategori (id_grup,nama_kategori,jenis,deskripsi) VALUES (?,?,?,?)");
            $stmt->execute([$id_grup,$nama,$jenis,$deskripsi]);
            flash('success', 'Kategori berhasil ditambahkan.');
        } redirect('index.php');
    }
    if ($aksi === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM kategori WHERE id=? AND id_grup=?");
            $stmt->execute([$id,$id_grup]);
            flash('success', 'Kategori berhasil dihapus.');
        } catch (PDOException $e) {
            flash('error', 'Kategori tidak dapat dihapus karena masih digunakan.');
        } redirect('index.php');
    }
}
$stmt = $pdo->prepare("SELECT * FROM kategori WHERE id_grup=? ORDER BY jenis,nama_kategori");
$stmt->execute([$id_grup]);
$data = $stmt->fetchAll();
include __DIR__.'/../includes/header.php';
?>
<section>
    <div class="section-heading">
        <div>
            <h1>Kategori</h1>
            <p class="muted">Kategori digunakan untuk mengelompokkan pemasukan dan pengeluaran.</p>
        </div>
    </div>
    <div class="form-card compact">
        <h2>Tambah Kategori</h2>
        <form method="post"><input type="hidden" name="aksi" value="tambah">
            <div class="form-grid">
                <p><label>Nama Kategori</label><input name="nama_kategori" required></p>
                <p><label>Jenis</label><select name="jenis" required>
                        <option value="">Pilih jenis</option>
                        <option>Pemasukan</option>
                        <option>Pengeluaran</option>
                    </select></p>
            </div>
            <p><label>Deskripsi</label><textarea name="deskripsi" rows="2"></textarea></p><button
                class="btn primary">Simpan</button>
        </form>
    </div>
</section>
<section>
    <h2>Daftar Kategori</h2>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Jenis</th>
                    <th>Deskripsi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody><?php if (!$data):?><tr>
                    <td colspan="4">Belum ada kategori.</td>
                </tr><?php endif;?><?php foreach ($data as $row):?><tr>
                    <td><?php echo e($row['nama_kategori']);?></td>
                    <td><span
                            class="badge <?php echo $row['jenis'] === 'Pemasukan' ? 'success' : 'danger';?>"><?php echo e($row['jenis']);?></span>
                    </td>
                    <td><?php echo e($row['deskripsi']);?></td>
                    <td>
                        <form method="post" class="inline-form form-hapus" onsubmit="return konfirmasiHapus();"><input
                                type="hidden" name="aksi" value="hapus"><input type="hidden" name="id"
                                value="<?php echo $row['id'];?>"><button class="btn danger" type="submit">Hapus</button>
                        </form>
                    </td>
                </tr><?php endforeach;?></tbody>
        </table>
    </div>
</section>
<?php include __DIR__.'/../includes/footer.php';?>