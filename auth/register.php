<?php
require_once __DIR__ . '/../includes/sesi.php';
mulai_sesi();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';

if (isset($_SESSION['user'])) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if ($nama === '' || $email === '' || $password === '') {
        flash('error', 'Semua data wajib diisi.');
    } elseif ($password !== $konfirmasi) {
        flash('error', 'Konfirmasi password tidak sama.');
    } else {
        $stmt = $pdo->prepare("SELECT id FROM pengguna WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            flash('error', 'Email sudah digunakan.');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO pengguna (nama, email, hash_kata_sandi) VALUES (?, ?, ?)");
            $stmt->execute([$nama, $email, $hash]);
            flash('success', 'Registrasi berhasil. Silakan masuk.');
            redirect('login.php');
        }
    }
}

$page_title = 'Daftar';
include __DIR__ . '/../includes/auth_head.php';
?>
        <h1>Buat Akun</h1>
        <p class="muted">Daftarkan akun untuk mulai menggunakan SIMKAS.</p>
        <?php tampil_flash(); ?>
        <form method="post">
            <p><label for="nama">Nama</label><input type="text" id="nama" name="nama" required></p>
            <p><label for="email">Email</label><input type="email" id="email" name="email" required></p>
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
            <p>
                <label for="konfirmasi">Konfirmasi Password</label>
                <span class="sandi-field">
                    <input type="password" id="konfirmasi" name="konfirmasi" required>
                    <button type="button" class="sandi-toggle" data-sandi-toggle aria-controls="konfirmasi"
                        aria-pressed="false" aria-label="Tampilkan konfirmasi password"
                        data-label-sembunyi="Sembunyikan konfirmasi password">
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
            <button type="submit" class="btn primary full">Daftar</button>
        </form>
        <p class="auth-link">Sudah punya akun? <a href="login.php">Masuk</a></p>
<?php include __DIR__ . '/../includes/auth_foot.php'; ?>
