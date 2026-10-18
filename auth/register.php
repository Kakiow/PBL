<?php
session_start();
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
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar | SIMKAS</title>
    <link rel="icon" type="image/svg+xml" href="../assets/img/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/inputs.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="../assets/css/toast.css">
</head>

<body class="auth-page">
    <div class="auth-box">
        <div class="auth-logo">SIMKAS</div>
        <h1>Buat Akun</h1>
        <p class="muted">Daftarkan akun untuk mulai menggunakan SIMKAS.</p>
        <?php tampil_flash(); ?>
        <form method="post">
            <p><label for="nama">Nama</label><input type="text" id="nama" name="nama" required></p>
            <p><label for="email">Email</label><input type="email" id="email" name="email" required></p>
            <p><label for="password">Password</label><input type="password" id="password" name="password" required></p>
            <p><label for="konfirmasi">Konfirmasi Password</label><input type="password" id="konfirmasi"
                    name="konfirmasi" required></p>
            <button type="submit" class="btn primary full">Daftar</button>
        </form>
        <p class="auth-link">Sudah punya akun? <a href="login.php">Masuk</a></p>
    </div>
    <div class="toast-area" id="toast-area"></div>
    <script src="../assets/js/toast.js"></script>
    <script src="../assets/js/script.js"></script>
</body>

</html>
