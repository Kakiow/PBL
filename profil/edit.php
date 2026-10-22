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

// Verifikasi berlaku 15 menit; halaman profil juga menutupnya saat dibuka.
$masa_verifikasi = 15 * 60;
$terverifikasi = isset($_SESSION['profil_edit_ok'])
    && time() - (int) $_SESSION['profil_edit_ok'] <= $masa_verifikasi;

if (!$terverifikasi) {
    unset($_SESSION['profil_edit_ok']);
}

$error_verifikasi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$terverifikasi) {
    $sandi = $_POST['sandi'] ?? '';

    $stmt = $pdo->prepare("SELECT hash_kata_sandi FROM pengguna WHERE id = ?");
    $stmt->execute([$id_user]);
    $hash_lama = (string) $stmt->fetchColumn();

    if ($sandi === '') {
        $error_verifikasi = 'Password wajib diisi.';
    } elseif (!password_verify($sandi, $hash_lama)) {
        $error_verifikasi = 'Password salah. Coba lagi.';
    } else {
        $_SESSION['profil_edit_ok'] = time();
        redirect('edit.php');
    }
}

if ($terverifikasi && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan_profil') {
        $nama = trim($_POST['nama'] ?? '');

        if ($nama === '') {
            flash('error', 'Nama wajib diisi.');
            redirect('edit.php');
        }

        $stmt = $pdo->prepare("UPDATE pengguna SET nama = ? WHERE id = ?");
        $stmt->execute([$nama, $id_user]);

        $_SESSION['user']['nama'] = $nama;
        unset($_SESSION['profil_edit_ok']);
        flash('success', 'Data akun berhasil diperbarui.');
        redirect('index.php');
    }

    if ($aksi === 'ganti_sandi') {
        $baru = $_POST['sandi_baru'] ?? '';
        $konfirmasi = $_POST['sandi_konfirmasi'] ?? '';

        $stmt = $pdo->prepare("SELECT hash_kata_sandi FROM pengguna WHERE id = ?");
        $stmt->execute([$id_user]);
        $hash_lama = (string) $stmt->fetchColumn();

        $gagal = null;
        if ($baru === '' || $konfirmasi === '') {
            $gagal = 'Password baru dan konfirmasi wajib diisi.';
        } elseif (strlen($baru) < 8) {
            $gagal = 'Password baru minimal 8 karakter.';
        } elseif ($baru !== $konfirmasi) {
            $gagal = 'Konfirmasi password baru tidak sama.';
        } elseif (password_verify($baru, $hash_lama)) {
            $gagal = 'Password baru harus berbeda dari password saat ini.';
        }

        if ($gagal !== null) {
            $_SESSION['sandi_notice'] = ['tipe' => 'error', 'pesan' => $gagal];
        } else {
            $stmt = $pdo->prepare("UPDATE pengguna SET hash_kata_sandi = ? WHERE id = ?");
            $stmt->execute([password_hash($baru, PASSWORD_DEFAULT), $id_user]);
            session_regenerate_id(true);
            $_SESSION['profil_edit_ok'] = time();
            $_SESSION['sandi_notice'] = ['tipe' => 'success', 'pesan' => 'Password baru berlaku untuk masuk berikutnya; sesi ini tetap aktif.'];
        }

        redirect('edit.php#kata-sandi');
    }
}

$notice_sandi = $terverifikasi ? ($_SESSION['sandi_notice'] ?? null) : null;
unset($_SESSION['sandi_notice']);

$page_title = $terverifikasi ? 'Edit profil' : 'Verifikasi password';
$page_css = ['profil.css'];

include __DIR__ . '/../includes/header.php';

if (!$terverifikasi):
?>
<div class="page-head">
    <?php tautan_kembali('index.php', 'Kembali ke Profil'); ?>
    <h1>Verifikasi password</h1>
    <p>Masukkan password akunmu untuk membuka halaman edit profil.</p>
</div>

<section class="form-card profile-verify" aria-labelledby="judul-verifikasi">
    <h2 id="judul-verifikasi">
        <span class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <rect x="4" y="10" width="16" height="10" rx="2" />
                <path d="M8 10V7a4 4 0 0 1 8 0v3" />
            </svg>
        </span>
        Password saat ini
    </h2>
    <?php if ($error_verifikasi !== ''): ?>
    <div class="notice is-danger" role="alert">
        <strong>Verifikasi gagal</strong>
        <?php echo e($error_verifikasi); ?>
    </div>
    <?php endif; ?>
    <p class="form-hint">Demi keamanan, edit profil selalu diawali dengan memasukkan password saat ini.</p>
    <form method="post" novalidate>
        <p>
            <label for="sandi">Password</label>
            <input id="sandi" name="sandi" type="password" autocomplete="current-password" aria-required="true"
                autofocus>
        </p>
        <div class="form-actions">
            <button class="btn primary" type="submit">Lanjutkan</button>
            <a class="btn secondary" href="index.php">Batal</a>
        </div>
    </form>
</section>
<?php else: ?>
<div class="page-head">
    <?php tautan_kembali('index.php', 'Kembali ke Profil'); ?>
    <h1>Edit profil</h1>
    <p>Perbarui nama atau password akun. Email tidak dapat diubah.</p>
</div>

<div class="profile-grid">
    <section class="form-card" aria-labelledby="judul-data-akun">
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
        <form method="post">
            <input type="hidden" name="aksi" value="simpan_profil">
            <p>
                <label for="nama">Nama</label>
                <input id="nama" name="nama" type="text" maxlength="120" required
                    value="<?php echo e($akun['nama']); ?>">
            </p>
            <p>
                <label for="email">Email</label>
                <input id="email" type="email" value="<?php echo e($akun['email']); ?>" readonly>
                <span class="field-hint">Email dipakai untuk masuk dan tidak dapat diubah.</span>
            </p>
            <div class="form-actions">
                <button class="btn primary" type="submit">Simpan perubahan</button>
                <a class="btn secondary" href="index.php">Batal</a>
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
        <?php if ($notice_sandi): $sandi_error = $notice_sandi['tipe'] === 'error'; ?>
        <div class="notice <?php echo $sandi_error ? 'is-danger' : 'is-success'; ?>"
            role="<?php echo $sandi_error ? 'alert' : 'status'; ?>">
            <strong><?php echo $sandi_error ? 'Password belum diubah' : 'Password diperbarui'; ?></strong>
            <?php echo e($notice_sandi['pesan']); ?>
        </div>
        <?php endif; ?>
        <p class="form-hint">Identitas sudah diverifikasi saat membuka halaman ini. Minimal 8 karakter; sesi ini tetap
            aktif setelah diganti.</p>
        <form method="post" novalidate>
            <input type="hidden" name="aksi" value="ganti_sandi">
            <p>
                <label for="sandi_baru">Password baru</label>
                <input id="sandi_baru" name="sandi_baru" type="password" minlength="8" aria-required="true"
                    autocomplete="new-password">
            </p>
            <p>
                <label for="sandi_konfirmasi">Konfirmasi password baru</label>
                <input id="sandi_konfirmasi" name="sandi_konfirmasi" type="password" minlength="8"
                    aria-required="true" autocomplete="new-password">
            </p>
            <div class="form-actions">
                <button class="btn primary" type="submit">Perbarui password</button>
            </div>
        </form>
    </section>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
