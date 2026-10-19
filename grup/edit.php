<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Edit Grup';
$id_user = (int) $_SESSION['user']['id'];
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM grup WHERE id = ?");
$stmt->execute([$id]);
$g = $stmt->fetch();

$peran = $g ? role_group($pdo, $id, $id_user) : null;

if (!$g || $peran !== 'Admin') {
    flash('error', 'Hanya Admin grup yang dapat mengubah data grup.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_grup'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($nama === '') {
        flash('error', 'Nama grup wajib diisi.');
    } else {
        $stmt = $pdo->prepare("UPDATE grup SET nama_grup = ?, deskripsi = ? WHERE id = ?");
        $stmt->execute([$nama, $deskripsi, $id]);
        flash('success', 'Data grup berhasil diperbarui.');
        redirect('detail.php?id=' . $id);
    }

    redirect('edit.php?id=' . $id);
}

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <?php tautan_kembali('detail.php?id=' . $id, 'Kembali ke Detail'); ?>
    <span class="eyebrow">Grup</span>
    <h1>Edit Grup</h1>
    <p>Perbarui nama dan deskripsi grup. Kode grup tidak dapat diubah.</p>
</div>

<section class="form-card form-card--create">
    <h2>
        <span class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <path d="M12 20h9" />
                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z" />
            </svg>
        </span>
        Data Grup
    </h2>
    <form method="post">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <p>
            <label for="nama_grup">Nama Grup</label>
            <input id="nama_grup" name="nama_grup" type="text" maxlength="120" required
                value="<?php echo e($g['nama_grup']); ?>">
        </p>
        <p>
            <label for="kode_grup">Kode Grup</label>
            <input id="kode_grup" type="text" value="<?php echo e($g['kode_grup']); ?>" readonly>
            <span class="field-hint">Kode terkunci karena sudah dibagikan ke anggota.</span>
        </p>
        <p>
            <label for="deskripsi">Deskripsi <span class="label-optional">opsional</span></label>
            <textarea id="deskripsi" name="deskripsi" rows="3"
                maxlength="200"><?php echo e($g['deskripsi']); ?></textarea>
        </p>
        <div class="form-actions">
            <button class="btn primary" type="submit">Simpan Perubahan</button>
            <a class="btn secondary" href="detail.php?id=<?php echo $id; ?>">Batal</a>
        </div>
    </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
