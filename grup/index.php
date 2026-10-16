<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Grup & Anggota';
$page_css = ['grup.css', 'table.css'];
$id_user = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'buat_grup') {
        $nama = trim($_POST['nama_grup'] ?? '');
        $kode = trim($_POST['kode_grup'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        if ($nama === '' || $kode === '') {
            flash('error', 'Nama grup dan kode grup wajib diisi.');
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO grup (nama_grup, kode_grup, deskripsi, dibuat_oleh) VALUES (?, ?, ?, ?) RETURNING id");
                $stmt->execute([$nama, $kode, $deskripsi, $id_user]);
                $id_grup = $stmt->fetchColumn();
                $stmt = $pdo->prepare("INSERT INTO anggota_grup (id_grup, id_pengguna, peran) VALUES (?, ?, 'Admin')");
                $stmt->execute([$id_grup, $id_user]);
                $stmt = $pdo->prepare("INSERT INTO kategori (id_grup, nama_kategori, jenis) VALUES (?, 'Iuran Kas', 'Pemasukan'), (?, 'Lain-lain', 'Pemasukan'), (?, 'Kebutuhan Kelas', 'Pengeluaran')");
                $stmt->execute([$id_grup, $id_grup, $id_grup]);
                $stmt = $pdo->prepare("INSERT INTO akun_dana (id_grup, nama_akun, jenis_akun) VALUES (?, 'Kas Tunai', 'Kas_Tunai')");
                $stmt->execute([$id_grup]);
                $pdo->commit();
                $_SESSION['grup_id'] = $id_grup;
                flash('success', 'Grup berhasil dibuat.');
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                flash('error', 'Gagal membuat grup. Pastikan kode grup belum digunakan.');
            }
        }
        redirect('index.php');
    }

    if ($aksi === 'gabung_grup') {
        $kode = trim($_POST['kode_grup'] ?? '');
        $stmt = $pdo->prepare("SELECT id FROM grup WHERE kode_grup = ?");
        $stmt->execute([$kode]);
        $id_grup = $stmt->fetchColumn();
        if (!$id_grup) {
            flash('error', 'Kode grup tidak ditemukan.');
        } else {
            $stmt = $pdo->prepare("SELECT id FROM anggota_grup WHERE id_grup = ? AND id_pengguna = ?");
            $stmt->execute([$id_grup, $id_user]);
            if ($stmt->fetch()) {
                flash('error', 'Anda sudah menjadi anggota grup tersebut.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO anggota_grup (id_grup, id_pengguna, peran) VALUES (?, ?, 'Anggota')");
                $stmt->execute([$id_grup, $id_user]);
                $_SESSION['grup_id'] = $id_grup;
                flash('success', 'Berhasil bergabung dengan grup.');
            }
        }
        redirect('index.php');
    }

    if ($aksi === 'pilih_grup') {
        $id_grup = (int) ($_POST['id_grup'] ?? 0);
        if (role_group($pdo, $id_grup, $id_user)) {
            $_SESSION['grup_id'] = $id_grup;
            flash('success', 'Grup aktif berhasil diubah.');
        }
        redirect('index.php');
    }

    if ($aksi === 'ubah_role') {
        $id_grup = (int) ($_SESSION['grup_id'] ?? 0);
        cek_role($pdo, $id_grup, ['Admin']);
        $peran_baru = $_POST['peran'] ?? [];

        $stmt = $pdo->prepare("SELECT id, id_pengguna, peran FROM anggota_grup WHERE id_grup = ?");
        $stmt->execute([$id_grup]);
        $anggota_grup = [];
        foreach ($stmt->fetchAll() as $row) {
            $anggota_grup[(int) $row['id']] = $row;
        }

        $jumlah_ubah = 0;
        $pindah_admin = false;

        if (is_array($peran_baru)) {
            try {
                $pdo->beginTransaction();
                $upd = $pdo->prepare("UPDATE anggota_grup SET peran = ? WHERE id = ? AND id_grup = ?");
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
                    if ($role === 'Admin') {
                        $pindah_admin = true;
                    }
                    $upd->execute([$role, $id_anggota, $id_grup]);
                    $jumlah_ubah++;
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
        } else {
            flash('error', 'Tidak ada perubahan role.');
        }
        redirect('index.php');
    }
}

$grup_saya = $pdo->prepare("SELECT g.*, ag.peran FROM grup g JOIN anggota_grup ag ON ag.id_grup = g.id WHERE ag.id_pengguna = ? ORDER BY g.nama_grup");
$grup_saya->execute([$id_user]);
$grup_saya = $grup_saya->fetchAll();

$grup = grup_aktif($pdo);
$anggota = [];
$saya_admin = false;
if ($grup) {
    $stmt = $pdo->prepare("SELECT ag.id, ag.id_pengguna, ag.peran, p.nama, p.email FROM anggota_grup ag JOIN pengguna p ON p.id = ag.id_pengguna WHERE ag.id_grup = ? ORDER BY p.nama");
    $stmt->execute([$grup['id']]);
    $anggota = $stmt->fetchAll();
    $saya_admin = role_group($pdo, $grup['id'], $id_user) === 'Admin';
}

include __DIR__ . '/../includes/header.php';
?>
<section>
    <div class="section-heading">
        <div>
            <h1>Grup & Anggota</h1>
            <p class="muted">Pilih grup yang ingin dikelola atau buat grup baru.</p>
        </div>
    </div>
    <div class="grid-2">
        <div class="form-card form-card--create">
            <h2>
                <span class="card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                </span>
                Buat Grup Baru
            </h2>
            <p class="form-hint">Belum punya grup? Buat grup baru di sini &mdash; kamu otomatis menjadi Admin.</p>
            <form method="post">
                <input type="hidden" name="aksi" value="buat_grup">
                <p><label>Nama Grup</label><input type="text" name="nama_grup" required></p>
                <p><label>Kode Grup</label><input type="text" name="kode_grup" required placeholder="Contoh: KELAS2G25">
                </p>
                <p><label>Deskripsi</label><textarea name="deskripsi" rows="3"></textarea></p>
                <button class="btn primary" type="submit">Buat Grup</button>
            </form>
        </div>
        <div class="form-card form-card--join">
            <h2>
                <span class="card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                        <path d="M10 17l5-5-5-5" />
                        <path d="M15 12H3" />
                    </svg>
                </span>
                Gabung Grup
            </h2>
            <p class="form-hint">Punya kode dari teman? Masukkan kodenya untuk bergabung ke grup.</p>
            <form method="post">
                <input type="hidden" name="aksi" value="gabung_grup">
                <p><label>Kode Grup</label><input type="text" name="kode_grup" placeholder="Masukkan kode grup"
                        required></p>
                <button class="btn secondary" type="submit">Gabung</button>
            </form>
        </div>
    </div>

    <div class="form-card form-card--list">
        <h2>
            <span class="card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M3 6h18M3 12h18M3 18h18" />
                </svg>
            </span>
            Grup Saya
        </h2>
        <?php if (!$grup_saya): ?><p class="muted">Belum memiliki grup.</p><?php endif; ?>
        <?php if ($grup_saya): ?><p class="form-hint">Klik kartu grup untuk menjadikannya grup aktif.</p><?php endif; ?>
        <div class="grup-list">
            <?php foreach ($grup_saya as $g): $grup_ini_aktif = isset($_SESSION['grup_id']) && $_SESSION['grup_id'] == $g['id']; ?>
            <form method="post" class="group-item <?php echo $grup_ini_aktif ? 'active' : ''; ?>">
                <input type="hidden" name="aksi" value="pilih_grup">
                <input type="hidden" name="id_grup" value="<?php echo (int) $g['id']; ?>">
                <button type="submit" class="group-item-btn"
                    title="<?php echo $grup_ini_aktif ? 'Grup ini sedang aktif' : 'Klik kartu untuk memilih grup ini'; ?>"<?php echo $grup_ini_aktif ? ' disabled aria-current="true"' : ''; ?>>
                    <strong><?php echo e($g['nama_grup']); ?></strong>
                    <span><?php echo e($g['kode_grup']); ?> · <?php echo e($g['peran']); ?><?php echo $grup_ini_aktif ? ' · Aktif' : ''; ?></span>
                </button>
            </form>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if ($grup): ?>
<section>
    <?php if ($saya_admin): ?>
    <form method="post" id="form-role">
        <input type="hidden" name="aksi" value="ubah_role">
        <?php endif; ?>
        <div class="section-heading">
            <div>
                <h2>Anggota <?php echo e($grup['nama_grup']); ?></h2>
                <p class="muted">Ubah role di kolom aksi, lalu simpan sekali untuk menerapkan semua perubahan.</p>
            </div>
            <?php if ($saya_admin): ?>
            <button class="btn btn-simpan" type="submit" id="btn-simpan-role">Simpan Perubahan</button>
            <?php endif; ?>
        </div>
        <div class="table-responsive table-fancy-wrap">
            <table class="table-fancy">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($anggota as $a): ?>
                    <tr>
                        <td><?php echo e($a['nama']); ?></td>
                        <td><?php echo e($a['email']); ?></td>
                        <td><span class="badge role-<?php echo strtolower($a['peran']); ?>"><?php echo e($a['peran']); ?></span></td>
                        <td>
                            <?php if ((int) $a['id_pengguna'] === (int) $id_user): ?>
                            <span class="muted">Akun Anda</span>
                            <?php elseif ($saya_admin): ?>
                            <div class="role-form">
                                <select name="peran[<?php echo (int) $a['id']; ?>]" class="role-select"
                                    aria-label="Role untuk <?php echo e($a['nama']); ?>">
                                    <option value="Anggota" <?php echo $a['peran'] === 'Anggota' ? 'selected' : ''; ?>>
                                        Anggota</option>
                                    <option value="Bendahara" <?php echo $a['peran'] === 'Bendahara' ? 'selected' : ''; ?>>
                                        Bendahara</option>
                                    <option value="Admin" <?php echo $a['peran'] === 'Admin' ? 'selected' : ''; ?>>Admin
                                    </option>
                                </select>
                            </div>
                            <?php else: ?><span class="muted">Tidak ada aksi</span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($saya_admin): ?>
    </form>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>