<?php
session_start();
require_once __DIR__.'/../config/koneksi.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/auth.php';
$page_title = 'Transaksi';
$id_grup = (int)($_SESSION['grup_id'] ?? 0);
$role = cek_role($pdo, $id_grup, akses_menu()['transaksi']);
$stmt = $pdo->prepare("SELECT t.*,k.nama_kategori,a.nama_akun,u.nama AS nama_pengguna FROM transaksi t JOIN kategori k ON k.id=t.id_kategori JOIN akun_dana a ON a.id=t.id_akun_dana JOIN pengguna u ON u.id=t.id_pengguna WHERE t.id_grup=? ORDER BY t.tanggal_transaksi DESC,t.id DESC");
$stmt->execute([$id_grup]);
$data = $stmt->fetchAll();
include __DIR__.'/../includes/header.php';
?><section>
    <div class="section-heading">
        <div>
            <h1>Transaksi</h1>
            <p class="muted">Catatan seluruh pemasukan dan pengeluaran grup.</p>
        </div><?php if (in_array($role, ['Admin','Bendahara'])):?><a class="btn primary" href="tambah.php">+ Tambah
            Transaksi</a><?php endif;?>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Jenis</th>
                    <th>Akun Dana</th>
                    <th>Jumlah</th>
                    <th>Deskripsi</th>
                    <th>Dibuat Oleh</th>
                </tr>
            </thead>
            <tbody><?php if (!$data):?><tr>
                    <td colspan="7">Belum ada transaksi.</td>
                </tr><?php endif;?><?php foreach ($data as $row):?><tr>
                    <td><?php echo e($row['tanggal_transaksi']);?></td>
                    <td><?php echo e($row['nama_kategori']);?></td>
                    <td><span
                            class="badge <?php echo $row['jenis'] === 'Pemasukan' ? 'success' : 'danger';?>"><?php echo e($row['jenis']);?></span>
                    </td>
                    <td><?php echo e($row['nama_akun']);?></td>
                    <td><?php echo rupiah($row['jumlah']);?></td>
                    <td><?php echo e($row['deskripsi']);?></td>
                    <td><?php echo e($row['nama_pengguna']);?></td>
                </tr><?php endforeach;?></tbody>
        </table>
    </div>
</section><?php include __DIR__.'/../includes/footer.php';?>