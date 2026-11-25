<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Profil';
$page_css = ['profil.css'];
$page_js = ['password-toggle.js', 'profil-verifikasi.js', 'crop-foto.js'];

$id_user = (int) $_SESSION['user']['id'];

$stmt = $pdo->prepare("SELECT id, nama, email, apakah_aktif, foto_profil, dibuat_pada FROM pengguna WHERE id = ?");
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

$stmt = $pdo->prepare("SELECT COUNT(*) FROM anggota_grup WHERE id_pengguna = ?");
$stmt->execute([$id_user]);
$jumlah_grup = (int) $stmt->fetchColumn();

$grup_aktif = grup_aktif($pdo);
$peran_aktif = $grup_aktif ? role_group($pdo, (int) $grup_aktif['id'], $id_user) : null;

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <h1>Profil</h1>
    <p>Identitas akun SIMKAS dan grup yang sedang aktif.</p>
</div>

<div class="profile-pair-grid">
    <section class="content-card profile-pair-card" aria-labelledby="profil-nama">
        <div class="profile-identity-top">
            <div class="profile-avatar-col">
                <div class="profile-avatar-wrap">
                    <div class="profile-avatar-box" id="profile-avatar-box">
                        <?php echo avatar_pengguna($akun['nama'], $akun['foto_profil'], 'profile-avatar'); ?>
                    </div>
                    <button type="button" class="btn-avatar-camera" id="btn-pilih-foto" title="Kelola foto profil"
                        aria-label="Kelola foto profil" aria-haspopup="menu" aria-expanded="false">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" aria-hidden="true">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                            <circle cx="12" cy="13" r="4" />
                        </svg>
                    </button>
                    <div class="avatar-menu" id="avatar-menu" role="menu" hidden>
                        <button type="button" class="avatar-menu-item" id="btn-ganti-foto" role="menuitem">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                                <circle cx="12" cy="13" r="4" />
                            </svg>
                            <span>Ubah foto</span>
                        </button>
                        <button type="button" class="avatar-menu-item is-danger" id="btn-hapus-foto" role="menuitem">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polyline points="3 6 5 6 21 6" />
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                <path d="M10 11v6M14 11v6" />
                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                            </svg>
                            <span>Hapus foto</span>
                        </button>
                    </div>
                </div>
            </div>
            <input type="file" id="input-foto-profil" accept="image/jpeg,image/png,image/webp" style="display:none"
                aria-hidden="true">
            <div class="profile-ident">
                <h2 id="profil-nama"><?php echo e($akun['nama']); ?></h2>
                <p class="profile-email-sub"><?php echo e($akun['email']); ?></p>
                <p class="profile-joined-meta">Bergabung sejak <?php echo e(tanggal_id($akun['dibuat_pada'])); ?></p>
            </div>
        </div>
        <div class="profile-pair-foot">
            <a class="btn primary" href="index.php?verifikasi=1" data-buka-verifikasi aria-haspopup="dialog"
                aria-controls="dialog-verifikasi">Edit profil &amp; password</a>
        </div>
    </section>

    <section class="content-card profile-pair-card" aria-labelledby="judul-grup-aktif">
        <div class="card-head">
            <div>
                <h2 id="judul-grup-aktif">Grup aktif</h2>
                <p>
                    <?php if ($jumlah_grup > 0): ?>
                    Tergabung di <?php echo $jumlah_grup; ?> grup
                    <?php else: ?>
                    Belum tergabung di grup mana pun
                    <?php endif; ?>
                </p>
            </div>
            <a class="text-link" href="../grup/index.php">Kelola grup →</a>
        </div>
        <div class="profile-active-group-body">
            <?php if ($grup_aktif && $peran_aktif): ?>
            <div class="profile-active-box">
                <div class="profile-group-title-row">
                    <span class="profile-group-name"><?php echo e($grup_aktif['nama_grup']); ?></span>
                    <span class="badge role-<?php echo strtolower($peran_aktif); ?>"><?php echo e($peran_aktif); ?></span>
                </div>
                <div class="profile-active-meta">
                    <span>Kode undangan:</span>
                    <span class="profile-group-code"><?php echo e($grup_aktif['kode_grup']); ?></span>
                    <button type="button" class="btn-copy-inline"
                        data-copy="<?php echo e($grup_aktif['kode_grup']); ?>">Salin</button>
                </div>
            </div>
            <?php else: ?>
            <div class="profile-empty-inline">
                <p>Pilih atau buat grup baru untuk mulai mencatat kas kelas.</p>
                <a class="btn primary small" href="../grup/index.php">Buat atau gabung grup →</a>
            </div>
            <?php endif; ?>
        </div>
    </section>
</div>

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
<dialog id="dialog-crop-foto" class="crop-dialog" aria-labelledby="judul-crop">
    <div class="crop-dialog-in">
        <div class="crop-dialog-head">
            <h2 id="judul-crop">Sesuaikan Foto Profil</h2>
            <button type="button" class="crop-close-btn" id="btn-tutup-crop" aria-label="Tutup dialog">✕</button>
        </div>
        <p class="crop-dialog-desc">Geser untuk memposisikan dan gunakan slider untuk memperbesar foto.</p>
        <div class="crop-viewport-wrap">
            <div class="crop-viewport" id="crop-viewport">
                <canvas id="crop-canvas" width="280" height="280"></canvas>
                <div class="crop-circle-mask" aria-hidden="true"></div>
            </div>
        </div>
        <div class="crop-controls">
            <span class="crop-zoom-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/>
                </svg>
            </span>
            <input type="range" id="crop-zoom" min="1" max="3" step="0.01" value="1" aria-label="Perbesar foto">
        </div>
        <div class="crop-dialog-foot">
            <button type="button" class="btn text" id="btn-batal-crop">Batal</button>
            <button type="button" class="btn primary" id="btn-simpan-crop">
                <span class="btn-text">Gunakan Foto</span>
                <span class="btn-spinner" aria-hidden="true" style="display:none">Menyimpan...</span>
            </button>
        </div>
    </div>
</dialog>
<?php include __DIR__ . '/../includes/footer.php'; ?>
