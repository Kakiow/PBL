<?php
require_once __DIR__ . '/../includes/sesi.php';
mulai_sesi();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/batas_login.php';

if (isset($_SESSION['user'])) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        flash('error', 'Email dan password wajib diisi.');
        redirect('login.php');
    }

    $email_kunci = batas_login_email($email);
    $ip = batas_login_ip();
    $sisa_blokir = batas_login_tersisa($pdo, $email_kunci, $ip);

    if ($sisa_blokir > 0) {
        flash('error', pesan_batas_login($sisa_blokir));
        redirect('login.php');
    }

    $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE email = ? AND apakah_aktif = TRUE");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['hash_kata_sandi'])) {
        catat_login_gagal($pdo, $email_kunci, $ip);
        $sisa_blokir = batas_login_tersisa($pdo, $email_kunci, $ip);
        flash('error', $sisa_blokir > 0 ? pesan_batas_login($sisa_blokir) : 'Email atau password salah.');
        redirect('login.php');
    }

    bersihkan_login_gagal($pdo, $email_kunci, $ip);
    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);
    $_SESSION['user'] = [
        'id' => $user['id'],
        'nama' => $user['nama'],
        'email' => $user['email']
    ];
    $_SESSION['sandi_stempel'] = hash('sha256', $user['hash_kata_sandi']);

    $stmt = $pdo->prepare("SELECT id_grup FROM anggota_grup WHERE id_pengguna = ? ORDER BY id LIMIT 1");
    $stmt->execute([$user['id']]);
    $grup = $stmt->fetch();
    if ($grup) {
        $_SESSION['grup_id'] = $grup['id_grup'];
    }

    redirect('../index.php');
}

$page_title = 'Masuk';
include __DIR__ . '/../includes/auth_head.php';
?>
        <h1>Masuk</h1>
        <p class="muted">Masuk untuk mengelola kas kelasmu.</p>
        <?php tampil_flash(); ?>
        <form method="post">
            <p>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </p>
            <p>
                <label for="password">Password</label>
                <?php sandi_field(['id' => 'password', 'required' => true]); ?>
            </p>
            <button type="submit" class="btn primary full">Masuk</button>
        </form>
        <p class="auth-link"><a href="lupa_password.php">Lupa password?</a></p>
        <p class="auth-link">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
<?php include __DIR__ . '/../includes/auth_foot.php'; ?>
