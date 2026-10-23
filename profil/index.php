<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Profil';
$page_css = ['profil.css'];
$page_js = ['password-toggle.js', 'profil-verifikasi.js'];

$id_user = (int) $_SESSION['user']['id'];

$stmt = $pdo->prepare("SELECT id, nama, email, apakah_aktif, dibuat_pada FROM pengguna WHERE id = ?");
$stmt->execute([$id_user]);
$akun = $stmt->fetch();

// Sesi menunjuk akun yang sudah tidak ada: bersihkan dan minta masuk lagi.
if (!$akun) {
    session_unset();
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

$masa_draft = 10 * 60;
$batas_verifikasi = 5;
$jendela_verifikasi = 10 * 60;
$error_sandi = null;
$buka_verifikasi = isset($_GET['verifikasi']);

$draft = $_SESSION['profil_edit_draft'] ?? null;
if (!is_array($draft) || time() - (int) ($draft['waktu'] ?? 0) > $masa_draft) {
    unset($_SESSION['profil_edit_draft']);
    $draft = null;
}

$percobaan = $_SESSION['profil_verifikasi_coba'] ?? null;
if (!is_array($percobaan) || time() - (int) ($percobaan['mulai'] ?? 0) >= $jendela_verifikasi) {
    $percobaan = ['jumlah' => 0, 'mulai' => time()];
}

$sisa_blokir = (int) $percobaan['mulai'] + $jendela_verifikasi - time();
if ((int) $percobaan['jumlah'] >= $batas_verifikasi && $sisa_blokir > 0) {
    $menit = max(1, (int) ceil($sisa_blokir / 60));
    $error_sandi = 'Terlalu banyak percobaan. Coba lagi dalam ' . $menit . ' menit.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        flash('error', 'Sesi formulir berakhir. Muat ulang halaman lalu coba lagi.');
        redirect($aksi === 'verifikasi' ? 'index.php?verifikasi=1' : 'index.php');
    }

    if ($aksi === 'verifikasi') {
        $buka_verifikasi = true;

        if ($error_sandi === null) {
            $stmt = $pdo->prepare("SELECT hash_kata_sandi FROM pengguna WHERE id = ?");
            $stmt->execute([$id_user]);
            $hash_lama = (string) $stmt->fetchColumn();
            $sandi = $_POST['sandi'] ?? '';

            if ($sandi === '') {
                $error_sandi = 'Password wajib diisi.';
            } elseif (!password_verify($sandi, $hash_lama)) {
                $error_sandi = 'Password salah. Coba lagi.';
            } else {
                unset($_SESSION['profil_verifikasi_coba'], $_SESSION['csrf_token']);
                session_regenerate_id(true);
                $_SESSION['profil_edit_ok'] = time();

                $tujuan = 'edit.php';
                if ($draft !== null && ($draft['aksi'] ?? '') === 'simpan_profil') {
                    $tujuan = 'edit.php#data-akun';
                } elseif ($draft !== null && ($draft['aksi'] ?? '') === 'ganti_sandi') {
                    $tujuan = 'edit.php#kata-sandi';
                }

                redirect($tujuan);
            }

            $percobaan['jumlah'] = (int) $percobaan['jumlah'] + 1;
            $_SESSION['profil_verifikasi_coba'] = $percobaan;
        }
    } elseif ($aksi === 'pilih_grup') {
        $id_target = (int) ($_POST['id_grup'] ?? 0);

        if (role_group($pdo, $id_target, $id_user)) {
            $_SESSION['grup_id'] = $id_target;
            flash('success', 'Grup aktif berhasil diubah.');
        } else {
            flash('error', 'Kamu bukan anggota grup tersebut.');
        }

        redirect('index.php');
    } else {
        redirect('index.php');
    }
}

$stmt = $pdo->prepare("
    SELECT g.id, g.nama_grup, g.kode_grup, ag.peran, ag.bergabung_pada
    FROM anggota_grup ag
    JOIN grup g ON g.id = ag.id_grup
    WHERE ag.id_pengguna = ?
    ORDER BY ag.bergabung_pada, g.nama_grup
");
$stmt->execute([$id_user]);
$grup_saya = $stmt->fetchAll();

$grup_aktif = grup_aktif($pdo);
$id_grup_aktif = $grup_aktif ? (int) $grup_aktif['id'] : 0;

$nama_user = (string) $akun['nama'];
$inisial = strtoupper(function_exists('mb_substr') ? mb_substr($nama_user, 0, 1) : substr($nama_user, 0, 1));
$aktif = in_array($akun['apakah_aktif'], [true, 't', '1', 1], true);

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <h1>Profil</h1>
    <p>Data akun dan grup yang kamu ikuti di SIMKAS.</p>
</div>

<section class="profile-hero" aria-labelledby="profil-nama">
    <span class="profile-avatar" aria-hidden="true"><?php echo e($inisial); ?></span>
    <div class="profile-ident">
        <h2 id="profil-nama"><?php echo e($akun['nama']); ?></h2>
        <p><?php echo e($akun['email']); ?></p>
        <div class="profile-tags">
            <span class="badge <?php echo $aktif ? 'success' : 'danger'; ?>">
                <?php echo $aktif ? 'Akun aktif' : 'Akun nonaktif'; ?>
            </span>
            <span class="chip"><?php echo count($grup_saya); ?> grup</span>
            <span class="chip">Bergabung <?php echo e(tanggal_id($akun['dibuat_pada'])); ?></span>
        </div>
    </div>
    <a class="btn primary profile-edit-btn" href="index.php?verifikasi=1" data-buka-verifikasi
        aria-haspopup="dialog" aria-controls="dialog-verifikasi">Edit profil &amp; password</a>
</section>

<section class="content-card" aria-labelledby="judul-grup-saya">
    <div class="card-head">
        <div>
            <h2 id="judul-grup-saya">Grup saya</h2>
            <p>Grup yang kamu ikuti beserta peranmu di dalamnya.</p>
        </div>
        <a class="text-link" href="../grup/index.php">Kelola grup →</a>
    </div>

    <?php if (!$grup_saya): ?>
    <p class="muted">Belum ada grup. <a href="../grup/index.php">Buat atau gabung grup</a> untuk mulai mencatat kas.</p>
    <?php else: ?>
    <ul class="profile-groups">
        <?php foreach ($grup_saya as $g): $aktif_grup = (int) $g['id'] === $id_grup_aktif; ?>
        <li>
            <div class="profile-group-main">
                <b><?php echo e($g['nama_grup']); ?></b>
                <span>Kode <?php echo e($g['kode_grup']); ?></span>
            </div>
            <span class="badge role-<?php echo strtolower($g['peran']); ?>"><?php echo e($g['peran']); ?></span>
            <span class="profile-group-date">Sejak <?php echo e(tanggal_id($g['bergabung_pada'])); ?></span>
            <?php if ($aktif_grup): ?>
            <span class="badge success">Grup aktif</span>
            <?php else: ?>
            <form method="post" class="profile-group-form">
                <?php csrf_field(); ?>
                <input type="hidden" name="aksi" value="pilih_grup">
                <input type="hidden" name="id_grup" value="<?php echo (int) $g['id']; ?>">
                <button type="submit" class="profile-group-action">Jadikan aktif</button>
            </form>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</section>

<dialog class="profile-dialog" id="dialog-verifikasi" aria-labelledby="judul-verifikasi"<?php echo $buka_verifikasi ? ' open' : ''; ?>>
    <div class="form-card profile-verify">
        <a class="profile-dialog-close" href="index.php" data-tutup-verifikasi aria-label="Tutup">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                aria-hidden="true">
                <path d="m6 6 12 12M18 6 6 18" />
            </svg>
        </a>
        <h2 id="judul-verifikasi">
            <span class="card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <rect x="4" y="10" width="16" height="10" rx="2" />
                    <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                </svg>
            </span>
            Konfirmasi akun
        </h2>
        <p class="form-hint">Masukkan password saat ini untuk membuka halaman edit.</p>
        <?php if ($draft !== null): ?>
        <div class="profile-verify-note">
            <strong>Sesi edit berakhir sebelum perubahan tersimpan.</strong>
            <p>
                <?php if (($draft['aksi'] ?? '') === 'simpan_profil'): ?>
                Isian nama sudah dipertahankan dan akan dipulihkan setelah verifikasi.
                <?php else: ?>
                Password baru tidak disimpan demi keamanan; masukkan ulang setelah verifikasi.
                <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>
        <form method="post" action="index.php" novalidate>
            <?php csrf_field(); ?>
            <input type="hidden" name="aksi" value="verifikasi">
            <div class="field<?php echo $error_sandi !== null ? ' is-invalid' : ''; ?>">
                <label for="sandi">Password</label>
                <?php sandi_field([
                    'id' => 'sandi',
                    'autocomplete' => 'current-password',
                    'aria_required' => true,
                    'aria_invalid' => $error_sandi !== null,
                    'aria_describedby' => $error_sandi !== null ? 'sandi-error' : null,
                    'autofocus' => true,
                ]); ?>
                <?php if ($error_sandi !== null): ?>
                <span class="field-error" id="sandi-error" role="alert"><?php echo e($error_sandi); ?></span>
                <?php endif; ?>
            </div>
            <div class="form-actions">
                <button class="btn primary" type="submit">Lanjutkan</button>
            </div>
        </form>
    </div>
</dialog>
<?php include __DIR__ . '/../includes/footer.php'; ?>
