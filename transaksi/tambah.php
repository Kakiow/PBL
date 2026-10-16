<?php
session_start();
require_once __DIR__.'/../config/koneksi.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/auth.php';
$page_title = 'Tambah Transaksi';
$id_grup = (int)($_SESSION['grup_id'] ?? 0);
cek_role($pdo, $id_grup, ['Admin','Bendahara']);
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
?><section><a href="index.php">← Kembali</a>
    <h1>Tambah Transaksi</h1>
    <form method="post" class="form-card">
        <div class="form-grid">
            <p><label>Jenis</label><select name="jenis" required>
                    <option value="">Pilih jenis</option>
                    <option>Pemasukan</option>
                    <option>Pengeluaran</option>
                </select></p>
            <p><label>Kategori</label><select name="id_kategori" required>
                    <option value="">Pilih kategori</option><?php foreach ($kategori as $k):?><option
                        value="<?php echo $k['id'];?>"><?php echo e($k['nama_kategori'].' - '.$k['jenis']);?></option>
                    <?php endforeach;?>
                </select></p>
            <p><label>Akun Dana</label><select name="id_akun_dana" required>
                    <option value="">Pilih akun</option><?php foreach ($akun as $a):?><option
                        value="<?php echo $a['id'];?>"><?php echo e($a['nama_akun']);?></option><?php endforeach;?>
                </select></p>
            <p><label>Jumlah</label><input type="number" name="jumlah" min="1" required></p>
            <p><label>Tanggal</label><input type="date" name="tanggal_transaksi" value="<?php echo date('Y-m-d');?>"
                    required></p>
        </div>
        <p><label>Deskripsi</label><textarea name="deskripsi" rows="3"></textarea></p><button class="btn primary">Simpan
            Transaksi</button>
    </form>
</section><?php include __DIR__.'/../includes/footer.php';?>