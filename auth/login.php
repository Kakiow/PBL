<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';

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

    $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE email = ? AND apakah_aktif = TRUE");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['hash_kata_sandi'])) {
        flash('error', 'Email atau password salah.');
        redirect('login.php');
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $user['id'],
        'nama' => $user['nama'],
        'email' => $user['email']
    ];

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
                <span class="sandi-field">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="sandi-toggle" data-sandi-toggle aria-controls="password"
                        aria-pressed="false" aria-label="Tampilkan password"
                        data-label-sembunyi="Sembunyikan password">
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
            </p>
            <button type="submit" class="btn primary full">Masuk</button>
        </form>
        <p class="auth-link"><a href="lupa_password.php">Lupa password?</a></p>
        <p class="auth-link">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
<?php include __DIR__ . '/../includes/auth_foot.php'; ?>
