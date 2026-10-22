<?php
require_once __DIR__ . '/../includes/auth.php';

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

// Izin edit hanya hidup selama halaman ini terbuka; mulai_sesi() menghapusnya di halaman lain.
$masa_verifikasi = 15 * 60;
$masa_draft = 10 * 60;
$terverifikasi = isset($_SESSION['profil_edit_ok'])
    && time() - (int) $_SESSION['profil_edit_ok'] < $masa_verifikasi;

if (!$terverifikasi) {
    unset($_SESSION['profil_edit_ok']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_valid($_POST['csrf_token'] ?? '')) {
            flash('error', 'Sesi formulir berakhir. Muat ulang halaman lalu coba lagi.');
            redirect('index.php?verifikasi=1');
        }

        $aksi = $_POST['aksi'] ?? '';

        // Sesi habis di tengah edit: simpan isian non-sensitif agar tidak hilang.
        if ($aksi === 'simpan_profil' || $aksi === 'ganti_sandi') {
            $nama_draft = trim($_POST['nama'] ?? '');

            if ($aksi === 'simpan_profil' && $nama_draft !== '') {
                $_SESSION['profil_edit_draft'] = [
                    'aksi' => 'simpan_profil',
                    'nama' => $nama_draft,
                    'waktu' => time(),
                ];
            } elseif ($aksi === 'ganti_sandi') {
                $_SESSION['profil_edit_draft'] = [
                    'aksi' => 'ganti_sandi',
                    'waktu' => time(),
                ];
            } else {
                unset($_SESSION['profil_edit_draft']);
            }

            flash('error', 'Sesi edit berakhir. Masukkan password lagi untuk melanjutkan.');
        }
    }

    redirect('index.php?verifikasi=1');
}

$error_nama = null;
$error_sandi_baru = null;
$error_sandi_konfirmasi = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_valid($_POST['csrf_token'] ?? '')) {
    flash('error', 'Sesi formulir berakhir. Muat ulang halaman lalu coba lagi.');
    redirect('edit.php');
}

$draft = $_SESSION['profil_edit_draft'] ?? null;
if (!is_array($draft) || time() - (int) ($draft['waktu'] ?? 0) > $masa_draft) {
    unset($_SESSION['profil_edit_draft']);
    $draft = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan_profil') {
        $nama = trim($_POST['nama'] ?? '');

        if ($nama === '') {
            $error_nama = 'Nama wajib diisi.';
        } else {
            $stmt = $pdo->prepare("UPDATE pengguna SET nama = ? WHERE id = ?");
            $stmt->execute([$nama, $id_user]);

            $_SESSION['user']['nama'] = $nama;
            unset($_SESSION['profil_edit_ok'], $_SESSION['profil_edit_draft']);
            flash('success', 'Data akun berhasil diperbarui.');
            redirect('index.php');
        }
    }

    if ($aksi === 'ganti_sandi') {
        $baru = $_POST['sandi_baru'] ?? '';
        $konfirmasi = $_POST['sandi_konfirmasi'] ?? '';

        $stmt = $pdo->prepare("SELECT hash_kata_sandi FROM pengguna WHERE id = ?");
        $stmt->execute([$id_user]);
        $hash_lama = (string) $stmt->fetchColumn();

        if ($baru === '') {
            $error_sandi_baru = 'Password baru wajib diisi.';
        }
        if ($konfirmasi === '') {
            $error_sandi_konfirmasi = 'Konfirmasi password baru wajib diisi.';
        }

        if ($error_sandi_baru === null && $error_sandi_konfirmasi === null) {
            if (strlen($baru) < 8) {
                $error_sandi_baru = 'Password baru minimal 8 karakter.';
            } elseif ($baru !== $konfirmasi) {
                $error_sandi_konfirmasi = 'Konfirmasi password baru tidak sama.';
            } elseif (password_verify($baru, $hash_lama)) {
                $error_sandi_baru = 'Password baru harus berbeda dari password saat ini.';
            }
        }

        if ($error_sandi_baru === null && $error_sandi_konfirmasi === null) {
            $hash_baru = password_hash($baru, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE pengguna SET hash_kata_sandi = ? WHERE id = ?");
            $stmt->execute([$hash_baru, $id_user]);

            unset($_SESSION['csrf_token']);
            session_regenerate_id(true);
            $_SESSION['sandi_stempel'] = hash('sha256', $hash_baru);
            $_SESSION['profil_edit_ok'] = time();
            flash('success', 'Password berhasil diperbarui. Sesi ini tetap aktif.');
            redirect('edit.php#kata-sandi');
        }
    }
}

$nama_form = (string) $akun['nama'];
if ($error_nama !== null) {
    $nama_form = trim($_POST['nama'] ?? '');
}

$fokus_sandi = $error_sandi_baru !== null ? 'sandi_baru'
    : ($error_sandi_konfirmasi !== null ? 'sandi_konfirmasi' : '');

if ($draft !== null) {
    if (($draft['aksi'] ?? '') === 'simpan_profil' && ($draft['nama'] ?? '') !== '') {
        $nama_form = (string) $draft['nama'];
        flash('success', 'Isian nama sebelumnya dipulihkan. Periksa lalu simpan.');
    } elseif (($draft['aksi'] ?? '') === 'ganti_sandi') {
        flash('error', 'Password baru tidak disimpan demi keamanan. Masukkan ulang password baru.');
    }

    unset($_SESSION['profil_edit_draft']);
}

$sisa_verifikasi = 0;
$pukul_kedaluwarsa = '';
if ($terverifikasi) {
    $kedaluwarsa = (int) $_SESSION['profil_edit_ok'] + $masa_verifikasi;
    $sisa_verifikasi = max(0, $kedaluwarsa - time());
    $pukul_kedaluwarsa = date('H:i', $kedaluwarsa);
}

$page_title = 'Edit profil';
$page_css = ['profil.css'];
$page_js = ['password-toggle.js', 'profil.js'];

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <?php tautan_kembali('index.php', 'Kembali ke Profil'); ?>
    <h1>Edit profil</h1>
    <p>Perbarui nama atau password akun.</p>
</div>

<div class="profile-session" data-edit-session data-sisa="<?php echo (int) $sisa_verifikasi; ?>">
    <span class="profile-session-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
        </svg>
    </span>
    <p class="profile-session-text">
        <strong>Sesi edit aktif.</strong>
        <span>Berakhir dalam <span class="profile-session-count" data-edit-countdown><?php echo sprintf('%02d:%02d', intdiv($sisa_verifikasi, 60), $sisa_verifikasi % 60); ?></span> · pukul <?php echo e($pukul_kedaluwarsa); ?></span>
    </p>
    <p class="profile-session-warning" data-edit-warning hidden role="status">Sesi edit berakhir. Isian nama akan dipertahankan saat kamu menyimpan; password perlu dimasukkan ulang.</p>
</div>

<div class="profile-grid">
    <section class="form-card" id="data-akun" aria-labelledby="judul-data-akun">
        <h2 id="judul-data-akun">
            <span class="card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="12" cy="8" r="4" />
                    <path d="M4 21a8 8 0 0 1 16 0" />
                </svg>
            </span>
            Data akun
        </h2>
        <p class="form-hint">Nama tampil di daftar anggota, riwayat pembayaran, dan laporan grup.</p>
        <div class="profile-email">
            <span class="profile-email-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <rect x="4" y="10" width="16" height="10" rx="2" />
                    <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                </svg>
            </span>
            <div class="profile-email-body">
                <span class="profile-email-label">Email</span>
                <span class="profile-email-value"><?php echo e($akun['email']); ?></span>
                <span class="profile-email-hint">Dipakai untuk masuk dan tidak dapat diubah.</span>
            </div>
        </div>
        <form method="post">
            <?php csrf_field(); ?>
            <input type="hidden" name="aksi" value="simpan_profil">
            <div class="field<?php echo $error_nama !== null ? ' is-invalid' : ''; ?>">
                <label for="nama">Nama</label>
                <input id="nama" name="nama" type="text" maxlength="120" required
                    <?php echo $error_nama !== null ? 'aria-invalid="true" aria-describedby="nama-error" autofocus ' : ''; ?>value="<?php echo e($nama_form); ?>">
                <?php if ($error_nama !== null): ?>
                <span class="field-error" id="nama-error" role="alert"><?php echo e($error_nama); ?></span>
                <?php endif; ?>
            </div>
            <div class="form-actions">
                <button class="btn primary" type="submit">Simpan perubahan</button>
            </div>
        </form>
    </section>

    <section class="form-card" id="kata-sandi" aria-labelledby="judul-password">
        <h2 id="judul-password">
            <span class="card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <rect x="4" y="10" width="16" height="10" rx="2" />
                    <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                </svg>
            </span>
            Password
        </h2>
        <p class="form-hint">Minimal 8 karakter.</p>
        <form method="post" novalidate>
            <?php csrf_field(); ?>
            <input type="hidden" name="aksi" value="ganti_sandi">
            <div class="field<?php echo $error_sandi_baru !== null ? ' is-invalid' : ''; ?>">
                <label for="sandi_baru">Password baru</label>
                <span class="sandi-field">
                    <input id="sandi_baru" name="sandi_baru" type="password" minlength="8" aria-required="true"
                        autocomplete="new-password"
                        <?php echo $error_sandi_baru !== null ? 'aria-invalid="true" aria-describedby="sandi-baru-error" ' : ''; ?><?php echo $fokus_sandi === 'sandi_baru' ? 'autofocus' : ''; ?>>
                    <button type="button" class="sandi-toggle" data-sandi-toggle aria-controls="sandi_baru"
                        aria-pressed="false" aria-label="Tampilkan password baru"
                        data-label-sembunyi="Sembunyikan password baru">
                        <svg class="ikon-lihat" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg class="ikon-tutup" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m3 3 18 18" />
                            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" />
                            <path d="M9.9 5.7A10.5 10.5 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17.6 17.6 0 0 1-3.3 4.2" />
                            <path d="M6.2 6.2A17.4 17.4 0 0 0 2.5 12S6 18.5 12 18.5a10.2 10.2 0 0 0 4.3-.9" />
                        </svg>
                    </button>
                </span>
                <?php if ($error_sandi_baru !== null): ?>
                <span class="field-error" id="sandi-baru-error" role="alert"><?php echo e($error_sandi_baru); ?></span>
                <?php endif; ?>
            </div>
            <div class="field<?php echo $error_sandi_konfirmasi !== null ? ' is-invalid' : ''; ?>">
                <label for="sandi_konfirmasi">Konfirmasi password baru</label>
                <span class="sandi-field">
                    <input id="sandi_konfirmasi" name="sandi_konfirmasi" type="password" minlength="8"
                        aria-required="true" autocomplete="new-password"
                        <?php echo $error_sandi_konfirmasi !== null ? 'aria-invalid="true" aria-describedby="sandi-konfirmasi-error" ' : ''; ?><?php echo $fokus_sandi === 'sandi_konfirmasi' ? 'autofocus' : ''; ?>>
                    <button type="button" class="sandi-toggle" data-sandi-toggle aria-controls="sandi_konfirmasi"
                        aria-pressed="false" aria-label="Tampilkan konfirmasi password baru"
                        data-label-sembunyi="Sembunyikan konfirmasi password baru">
                        <svg class="ikon-lihat" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg class="ikon-tutup" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m3 3 18 18" />
                            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" />
                            <path d="M9.9 5.7A10.5 10.5 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17.6 17.6 0 0 1-3.3 4.2" />
                            <path d="M6.2 6.2A17.4 17.4 0 0 0 2.5 12S6 18.5 12 18.5a10.2 10.2 0 0 0 4.3-.9" />
                        </svg>
                    </button>
                </span>
                <?php if ($error_sandi_konfirmasi !== null): ?>
                <span class="field-error" id="sandi-konfirmasi-error"
                    role="alert"><?php echo e($error_sandi_konfirmasi); ?></span>
                <?php endif; ?>
            </div>
            <div class="form-actions">
                <button class="btn primary" type="submit">Perbarui password</button>
            </div>
        </form>
    </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
