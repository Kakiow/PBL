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
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk | SIMKAS</title>
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
                <input type="password" id="password" name="password" required>
            </p>
            <button type="submit" class="btn primary full">Masuk</button>
        </form>
        <p class="auth-link">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
    </div>
    <div class="toast-area" id="toast-area"></div>
    <script src="../assets/js/toast.js"></script>
    <script src="../assets/js/script.js"></script>
</body>

</html>
