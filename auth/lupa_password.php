<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/reset_password.php';

if (isset($_SESSION['user'])) {
    redirect('../index.php');
}

$mail_config = require __DIR__ . '/../config/mail.php';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Format email tidak valid.');
        redirect('lupa_password.php');
    }

    $sekarang = time();

    // Batasi percobaan dari sesi yang sama agar tidak spam.
    if (isset($_SESSION['reset_terakhir']) && ($sekarang - (int) $_SESSION['reset_terakhir']) < 60) {
        flash('success', 'Jika email terdaftar, link reset sudah dikirim. Cek inbox atau folder spam.');
        redirect('lupa_password.php');
    }

    try {
        $stmt = $pdo->prepare("SELECT id, nama, email FROM pengguna WHERE email = ? AND apakah_aktif = TRUE");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Jangan kirim email baru jika permintaan sebelumnya masih terlalu baru.
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM reset_password WHERE id_pengguna = ? AND dibuat_pada > CURRENT_TIMESTAMP - INTERVAL '2 minutes'");
            $stmt->execute([$user['id']]);

            if ((int) $stmt->fetchColumn() === 0) {
                bersihkan_token_reset($pdo);
                $token = buat_token_reset($pdo, $user['id']);
                $tautan = url_dasar_aplikasi($mail_config) . '/auth/reset_password.php?token=' . urlencode($token);

                kirim_email_reset($mail_config, $user['email'], $user['nama'], $tautan);
            }
        }

        $_SESSION['reset_terakhir'] = $sekarang;
    } catch (Throwable $e) {
        // Respons tetap netral agar keberadaan email tidak bocor.
        error_log('Lupa password gagal: ' . $e->getMessage());
    }

    flash('success', 'Jika email terdaftar, link reset sudah dikirim. Cek inbox atau folder spam.');
    redirect('lupa_password.php');
}

$page_title = 'Lupa Password';
$noindex = true;
include __DIR__ . '/../includes/auth_head.php';
?>
        <h1>Lupa Password</h1>
        <p class="muted">Masukkan email akun Anda. Kami akan mengirim link untuk membuat password baru jika email
            terdaftar.</p>
        <?php tampil_flash(); ?>
        <form method="post">
            <p>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo e($email); ?>" required
                    autocomplete="email" autofocus>
            </p>
            <button type="submit" class="btn primary full">Kirim Link Reset</button>
        </form>
        <p class="auth-link"><a href="login.php">Kembali ke halaman masuk</a></p>
<?php include __DIR__ . '/../includes/auth_foot.php'; ?>
