<?php

// Satu pintu start sesi: cookie dikunci HttpOnly + SameSite Lax sebelum sesi dibuat.
function mulai_sesi()
{
    if (session_status() === PHP_SESSION_NONE) {
        $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    sesi_akhiri_izin_edit();
}

// Izin edit hanya hidup selama halaman edit terbuka: halaman lain mengakhirinya,
// jadi setiap klik Edit dari Profil selalu meminta password lagi.
function sesi_akhiri_izin_edit()
{
    $skrip = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
    if (substr($skrip, -16) !== '/profil/edit.php') {
        unset($_SESSION['profil_edit_ok']);
    }
}

// Stempel hash password membuat sesi yang dibuat sebelum password berubah langsung gugur.
function sesi_stempel_sah($pdo)
{
    $id_user = (int) ($_SESSION['user']['id'] ?? 0);
    if ($id_user < 1) {
        return false;
    }

    $stmt = $pdo->prepare("SELECT hash_kata_sandi FROM pengguna WHERE id = ?");
    $stmt->execute([$id_user]);
    $hash = $stmt->fetchColumn();

    if ($hash === false) {
        return false;
    }

    return hash_equals(hash('sha256', (string) $hash), (string) ($_SESSION['sandi_stempel'] ?? ''));
}

function sesi_gugurkan($tujuan)
{
    unset(
        $_SESSION['user'],
        $_SESSION['grup_id'],
        $_SESSION['profil_edit_ok'],
        $_SESSION['sandi_stempel'],
        $_SESSION['csrf_token']
    );
    flash('error', 'Sesi berakhir karena password akun berubah. Silakan masuk lagi.');
    header('Location: ' . $tujuan);
    exit;
}
