<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Anggota';
$page_css = ['anggota.css'];
$page_js = ['anggota.js'];
$id_user = $_SESSION['user']['id'];

$grup = grup_aktif($pdo);

if (!$grup) {
    flash('error', 'Pilih atau buat grup terlebih dahulu untuk melihat daftar anggota.');
    redirect('../grup/index.php');
}

$id_grup = (int) $grup['id'];
$saya_admin = role_group($pdo, $id_grup, $id_user) === 'Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $saya_admin) {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        flash('error', 'Sesi formulir kedaluwarsa. Silakan coba lagi.');
        redirect('index.php');
    }

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
            flash('error', 'Tidak ada perubahan peran.');
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
                    flash('success', 'Tidak ada perubahan peran.');
                } elseif ($pindah_admin) {
                    flash('success', 'Perubahan peran disimpan. Peran Admin sudah dipindahkan; Anda kini Anggota.');
                } else {
                    flash('success', 'Perubahan peran berhasil disimpan.');
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                flash('error', 'Gagal menyimpan perubahan peran.');
            }
        }
        redirect('index.php');
    }
}

$stmt = $pdo->prepare("SELECT ag.id, ag.id_pengguna, ag.peran, ag.bergabung_pada, p.nama, p.email, p.foto_profil FROM anggota_grup ag JOIN pengguna p ON p.id = ag.id_pengguna WHERE ag.id_grup = ? ORDER BY p.nama");
$stmt->execute([$id_grup]);
$anggota = $stmt->fetchAll();

$hitung_peran = ['Semua' => count($anggota), 'Admin' => 0, 'Bendahara' => 0, 'Anggota' => 0];
foreach ($anggota as $a) {
    if (isset($hitung_peran[$a['peran']])) {
        $hitung_peran[$a['peran']] += 1;
    }
}

$kelas_peran = function (string $peran): string {
    if ($peran === 'Admin') {
        return 'danger';
    }
    if ($peran === 'Bendahara') {
        return 'success';
    }
    return '';
};

include __DIR__ . '/../includes/header.php';
?>
<header class="anggota-page-head">
    <div>
        <h1>Anggota <?php echo e($grup['nama_grup']); ?></h1>
        <p><?php echo count($anggota); ?> anggota tergabung<?php echo $saya_admin ? ' &middot; Admin hanya boleh satu orang.' : '.'; ?></p>
    </div>
    <div class="anggota-head-actions">
        <button type="button" class="code-btn" data-copy="<?php echo e($grup['kode_grup']); ?>"
            title="Salin kode undangan grup <?php echo e($grup['kode_grup']); ?>">
            Kode: <?php echo e($grup['kode_grup']); ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" aria-hidden="true">
                <rect x="9" y="9" width="11" height="11" rx="2" />
                <path d="M5 15V5a2 2 0 0 1 2-2h10" />
            </svg>
        </button>
    </div>
</header>

<form method="post" id="anggota-form-role">
    <?php csrf_field(); ?>
    <?php if ($saya_admin): ?><input type="hidden" name="aksi" value="ubah_role"><?php endif; ?>
    <section class="content-card">
        <div class="anggota-toolbar">
            <div class="role-tabs" role="tablist" aria-label="Filter peran anggota" id="role-tabs">
                <?php foreach ($hitung_peran as $label_tab => $jumlah_tab): ?>
                <button type="button" class="role-tab<?php echo $label_tab === 'Semua' ? ' is-active' : ''; ?>"
                    data-tab="<?php echo e($label_tab); ?>" role="tab"
                    aria-selected="<?php echo $label_tab === 'Semua' ? 'true' : 'false'; ?>">
                    <?php echo e($label_tab); ?> <span class="role-tab-count"><?php echo $jumlah_tab; ?></span>
                </button>
                <?php endforeach; ?>
            </div>
            <div class="anggota-search-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.3-4.3" />
                </svg>
                <input type="search" id="cari-anggota" class="anggota-search" placeholder="Cari nama atau email..."
                    aria-label="Cari nama atau email anggota">
            </div>
        </div>

        <?php if (!$anggota): ?>
        <p class="anggota-empty">Belum ada anggota.</p>
        <?php else: ?>
        <div class="anggota-rows" id="anggota-rows">
            <?php foreach ($anggota as $a): $is_saya = (int) $a['id_pengguna'] === (int) $id_user; ?>
            <div class="anggota-row" data-role="<?php echo e($a['peran']); ?>"
                data-nama="<?php echo e($a['nama']); ?>" data-email="<?php echo e($a['email']); ?>">
                <div class="anggota-person">
                    <?php echo avatar_pengguna($a['nama'], $a['foto_profil'], 'avatar-sm'); ?>
                    <div class="anggota-person-text">
                        <div class="anggota-name-row">
                            <strong><?php echo e($a['nama']); ?></strong>
                            <?php if ($is_saya): ?><span class="self-tag">Anda</span><?php endif; ?>
                        </div>
                        <div class="anggota-email"><?php echo e($a['email']); ?></div>
                    </div>
                </div>
                <div class="anggota-meta-right">
                    <span class="anggota-joined"><?php echo e(tanggal_id($a['bergabung_pada'])); ?></span>
                    <?php if ($saya_admin && !$is_saya): ?>
                    <select name="peran[<?php echo (int) $a['id']; ?>]" class="role-select"
                        data-awal="<?php echo e($a['peran']); ?>"
                        aria-label="Peran untuk <?php echo e($a['nama']); ?>">
                        <option value="Anggota" <?php echo $a['peran'] === 'Anggota' ? 'selected' : ''; ?>>Anggota</option>
                        <option value="Bendahara" <?php echo $a['peran'] === 'Bendahara' ? 'selected' : ''; ?>>Bendahara</option>
                        <option value="Admin" <?php echo $a['peran'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                    <?php else: ?>
                    <span class="badge <?php echo $kelas_peran($a['peran']); ?>"><?php echo e($a['peran']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="anggota-empty" id="anggota-empty" hidden></p>
        <?php endif; ?>
    </section>

    <?php if ($saya_admin): ?>
    <div class="save-bar" id="save-bar" hidden>
        <span class="save-bar-text" id="save-bar-text"></span>
        <div class="save-bar-actions">
            <button type="button" class="btn secondary small" id="btn-reset-role">Batal</button>
            <button type="submit" class="btn primary small" id="btn-simpan-role">Simpan perubahan</button>
        </div>
    </div>
    <?php endif; ?>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
