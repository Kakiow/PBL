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
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Format email tidak valid.');
    } elseif (strlen($password) < 8) {
        flash('error', 'Password minimal 8 karakter.');
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
        <h1>Buat akun</h1>
        <p class="muted">Daftarkan akun untuk mulai menggunakan SIMKAS.</p>
        <?php tampil_flash(); ?>
        <form method="post">
            <p><label for="nama">Nama</label><input type="text" id="nama" name="nama" required></p>
            <p><label for="email">Email</label><input type="email" id="email" name="email" required></p>
            <p>
                <label for="password">Password</label>
                <?php sandi_field(['id' => 'password', 'minlength' => 8, 'required' => true]); ?>
            </p>
            <p>
                <label for="konfirmasi">Konfirmasi password</label>
                <?php sandi_field([
                    'id' => 'konfirmasi',
                    'required' => true,
                    'label_tampil' => 'Tampilkan konfirmasi password',
                    'label_sembunyi' => 'Sembunyikan konfirmasi password',
                ]); ?>
            </p>
            <button type="submit" class="btn primary full">Daftar</button>
        </form>
        <p class="auth-link">Sudah punya akun? <a href="login.php">Masuk</a></p>
<?php include __DIR__ . '/../includes/auth_foot.php'; ?>
