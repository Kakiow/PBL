<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/reset_password.php';

$token = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
$reset = cari_token_reset($pdo, $token);
$error = null;

if (!$reset) {
    $error = 'Link reset tidak valid atau sudah kedaluwarsa. Silakan minta link baru.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT id
                FROM reset_password
                WHERE token_hash = ?
                  AND digunakan_pada IS NULL
                  AND kedaluwarsa_pada > CURRENT_TIMESTAMP
                FOR UPDATE
            ");
            $stmt->execute([hash('sha256', $token)]);

            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('Link reset sudah tidak berlaku. Silakan minta link baru.');
            }

            $hash_baru = password_hash($password, PASSWORD_DEFAULT);

            if ($hash_baru === false) {
                throw new RuntimeException('Password gagal diproses.');
            }

            $stmt = $pdo->prepare("UPDATE pengguna SET hash_kata_sandi = ? WHERE id = ?");
            $stmt->execute([$hash_baru, $reset['id_pengguna']]);

            $stmt = $pdo->prepare("UPDATE reset_password SET digunakan_pada = CURRENT_TIMESTAMP WHERE id_pengguna = ? AND digunakan_pada IS NULL");
            $stmt->execute([$reset['id_pengguna']]);

            $pdo->commit();

            $_SESSION = [];

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }

            session_destroy();
            session_start();
            flash('success', 'Password berhasil diubah. Silakan masuk dengan password baru.');
            redirect('login.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Reset password gagal: ' . $e->getMessage());
            $error = 'Password gagal diubah. Silakan coba lagi atau minta link baru.';
            $reset = false;
        }
    }
}

$page_title = 'Reset password';
$noindex = true;
include __DIR__ . '/../includes/auth_head.php';
?>
        <h1>Reset Password</h1>
        <p class="muted">Buat password baru untuk akun Anda. Password minimal 8 karakter.</p>

        <?php if ($error !== null): ?>
        <div class="flash error" role="alert">
            <span class="flash-text"><?php echo e($error); ?></span>
        </div>
        <?php endif; ?>

        <?php if ($reset): ?>
        <form method="post">
            <input type="hidden" name="token" value="<?php echo e($token); ?>">
            <p>
                <label for="password">Password Baru</label>
                <input type="password" id="password" name="password" minlength="8" required autocomplete="new-password"
                    autofocus>
            </p>
            <p>
                <label for="konfirmasi">Konfirmasi Password Baru</label>
                <input type="password" id="konfirmasi" name="konfirmasi" minlength="8" required
                    autocomplete="new-password">
            </p>
            <button type="submit" class="btn primary full">Simpan Password Baru</button>
        </form>
        <?php endif; ?>

        <p class="auth-link">
            <?php if ($reset): ?>
            <a href="login.php">Kembali ke halaman masuk</a>
            <?php else: ?>
            <a href="lupa_password.php">Minta link reset baru</a>
            <?php endif; ?>
        </p>
<?php include __DIR__ . '/../includes/auth_foot.php'; ?>
