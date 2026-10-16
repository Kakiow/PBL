<?php
session_start();
require_once __DIR__.'/../config/koneksi.php';
require_once __DIR__.'/../includes/helpers.php';
require_once __DIR__.'/../includes/auth.php';
$page_title = 'Kewajiban Pembayaran';
$id_grup = (int)($_SESSION['grup_id'] ?? 0);
$role = cek_role($pdo, $id_grup, ['Admin','Bendahara','Anggota']);
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
    </div><?php if (in_array($role, ['Admin','Bendahara'])):?><div class="form-card">
        <h2>Buat Kewajiban</h2>
        <form method="post">
            <div class="form-grid">
                <p><label>Judul</label><input name="judul" required></p>
                <p><label>Jumlah</label><input type="number" name="jumlah" min="1" required></p>
                <p><label>Tenggat</label><input type="date" name="tenggat_waktu" required></p>
            </div>
            <p><label>Deskripsi</label><textarea name="deskripsi" rows="2"></textarea></p><button
                class="btn primary">Buat Kewajiban</button>
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