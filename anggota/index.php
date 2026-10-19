<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Anggota';
$page_css = ['anggota.css', 'table.css'];
$id_user = $_SESSION['user']['id'];

$grup = grup_aktif($pdo);

if (!$grup) {
    flash('error', 'Pilih atau buat grup terlebih dahulu untuk melihat daftar anggota.');
    redirect('../grup/index.php');
}

$id_grup = (int) $grup['id'];
$saya_admin = role_group($pdo, $id_grup, $id_user) === 'Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $saya_admin) {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'ubah_role') {
        $peran_baru = $_POST['peran'] ?? [];

        $stmt = $pdo->prepare("SELECT id, id_pengguna, peran FROM anggota_grup WHERE id_grup = ?");
        $stmt->execute([$id_grup]);
        $anggota_grup = [];
        foreach ($stmt->fetchAll() as $row) {
            $anggota_grup[(int) $row['id']] = $row;
        }

        // Kumpulkan perubahan yang valid dulu, baru tulis ke DB.
        $perubahan = [];
        if (is_array($peran_baru)) {
            foreach ($peran_baru as $id_anggota => $role) {
                $id_anggota = (int) $id_anggota;
                if (!isset($anggota_grup[$id_anggota])) {
                    continue;
                }
                if ((int) $anggota_grup[$id_anggota]['id_pengguna'] === (int) $id_user) {
                    continue;
                }
                if (!in_array($role, ['Admin', 'Bendahara', 'Anggota'], true)) {
                    continue;
                }
                if ($role === $anggota_grup[$id_anggota]['peran']) {
                    continue;
                }
                $perubahan[$id_anggota] = $role;
            }
        }

        $jumlah_ubah = count($perubahan);
        $jumlah_admin_baru = count(array_keys($perubahan, 'Admin', true));
        $pindah_admin = $jumlah_admin_baru === 1;

        // Grup harus selalu punya tepat 1 Admin: kepemilikan hanya bisa dipindah ke satu orang.
        if (!is_array($peran_baru)) {
            flash('error', 'Tidak ada perubahan role.');
        } elseif ($jumlah_admin_baru > 1) {
            flash('error', 'Hanya satu anggota yang dapat dijadikan Admin. Tidak ada perubahan yang disimpan.');
        } else {
            try {
                $pdo->beginTransaction();
                $upd = $pdo->prepare("UPDATE anggota_grup SET peran = ? WHERE id = ? AND id_grup = ?");
                foreach ($perubahan as $id_anggota => $role) {
                    $upd->execute([$role, $id_anggota, $id_grup]);
                }
                if ($pindah_admin) {
                    $stmt = $pdo->prepare("UPDATE anggota_grup SET peran = 'Anggota' WHERE id_grup = ? AND id_pengguna = ?");
                    $stmt->execute([$id_grup, $id_user]);
                }
                $pdo->commit();
                if ($jumlah_ubah === 0) {
                    flash('success', 'Tidak ada perubahan role.');
                } elseif ($pindah_admin) {
                    flash('success', 'Perubahan role disimpan. Kepemilikan admin dipindahkan; Anda kini Anggota.');
                } else {
                    flash('success', 'Perubahan role berhasil disimpan.');
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                flash('error', 'Gagal menyimpan perubahan role.');
            }
        }
        redirect('index.php');
    }
}

$stmt = $pdo->prepare("SELECT ag.id, ag.id_pengguna, ag.peran, ag.bergabung_pada, p.nama, p.email FROM anggota_grup ag JOIN pengguna p ON p.id = ag.id_pengguna WHERE ag.id_grup = ? ORDER BY p.nama");
$stmt->execute([$id_grup]);
$anggota = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <span class="eyebrow">Anggota</span>
    <h1>Anggota <?php echo e($grup['nama_grup']); ?></h1>
    <p><?php echo count($anggota); ?> orang bergabung pada grup ini.</p>
</div>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Daftar Anggota</h2>
            <p><?php echo $saya_admin
                    ? 'Ubah role pada kolom aksi, lalu simpan sekali untuk menerapkan semua perubahan.'
                    : 'Hanya Admin yang dapat mengubah role anggota.'; ?></p>
        </div>
        <?php if ($saya_admin): ?>
        <button class="btn success btn-simpan" type="submit" form="form-role" id="btn-simpan-role">Simpan
            Perubahan</button>
        <?php endif; ?>
    </div>

    <?php $form_id = $saya_admin ? ' id="form-role"' : ''; ?>
    <form method="post"<?php echo $form_id; ?>>
        <?php if ($saya_admin): ?><input type="hidden" name="aksi" value="ubah_role"><?php endif; ?>
        <div class="table-responsive table-fancy-wrap">
            <table class="table-fancy">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Bergabung</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$anggota): ?><tr class="empty">
                        <td colspan="5">Belum ada anggota.</td>
                    </tr><?php endif; ?>
                    <?php foreach ($anggota as $a): ?>
                    <tr>
                        <td><?php echo e($a['nama']); ?></td>
                        <td class="desc"><?php echo e($a['email']); ?></td>
                        <td><span class="badge role-<?php echo strtolower($a['peran']); ?>"><?php echo e($a['peran']); ?></span></td>
                        <td class="nowrap"><?php echo e(substr((string) $a['bergabung_pada'], 0, 10)); ?></td>
                        <td>
                            <?php if ((int) $a['id_pengguna'] === (int) $id_user): ?>
                            <span class="muted">Akun Anda</span>
                            <?php elseif ($saya_admin): ?>
                            <div class="role-form">
                                <select name="peran[<?php echo (int) $a['id']; ?>]" class="role-select"
                                    aria-label="Role untuk <?php echo e($a['nama']); ?>">
                                    <option value="Anggota" <?php echo $a['peran'] === 'Anggota' ? 'selected' : ''; ?>>Anggota</option>
                                    <option value="Bendahara" <?php echo $a['peran'] === 'Bendahara' ? 'selected' : ''; ?>>Bendahara</option>
                                    <option value="Admin" <?php echo $a['peran'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            <?php else: ?><span class="muted">Tidak ada aksi</span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
