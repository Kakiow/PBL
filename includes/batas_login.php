<?php

// Pembatas percobaan login: maksimal 5 kegagalan per email + IP dalam 10 menit.
const BATAS_LOGIN_MAKS = 5;
const BATAS_LOGIN_MENIT = 10;

function batas_login_email($email)
{
    $email = trim((string) $email);

    return function_exists('mb_strtolower') ? mb_strtolower($email, 'UTF-8') : strtolower($email);
}

function batas_login_ip()
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

// Sisa detik blokir; 0 berarti belum mencapai batas.
function batas_login_tersisa($pdo, $email, $ip)
{
    $menit = (int) BATAS_LOGIN_MENIT;

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS jumlah,
                   EXTRACT(EPOCH FROM (MAX(dibuat_pada) + INTERVAL '{$menit} minutes' - CURRENT_TIMESTAMP)) AS sisa
            FROM percobaan_login
            WHERE email = ? AND ip = ?
              AND dibuat_pada > CURRENT_TIMESTAMP - INTERVAL '{$menit} minutes'
        ");
        $stmt->execute([$email, $ip]);
        $baris = $stmt->fetch();
    } catch (Throwable $e) {
        // Tabel percobaan_login belum ada (skema belum dijalankan): login tetap bisa dipakai.
        error_log('Pembatas login tidak tersedia: ' . $e->getMessage());
        return 0;
    }

    if (!$baris || (int) $baris['jumlah'] < BATAS_LOGIN_MAKS) {
        return 0;
    }

    return max(1, (int) ceil((float) $baris['sisa']));
}

function catat_login_gagal($pdo, $email, $ip)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO percobaan_login (email, ip) VALUES (?, ?)");
        $stmt->execute([$email, $ip]);

        $pdo->exec("DELETE FROM percobaan_login WHERE dibuat_pada < CURRENT_TIMESTAMP - INTERVAL '1 day'");
    } catch (Throwable $e) {
        error_log('Gagal mencatat percobaan login: ' . $e->getMessage());
    }
}

function bersihkan_login_gagal($pdo, $email, $ip)
{
    try {
        $stmt = $pdo->prepare("DELETE FROM percobaan_login WHERE email = ? AND ip = ?");
        $stmt->execute([$email, $ip]);
    } catch (Throwable $e) {
        error_log('Gagal membersihkan percobaan login: ' . $e->getMessage());
    }
}

function pesan_batas_login($sisa)
{
    $menit = max(1, (int) ceil($sisa / 60));

    return 'Terlalu banyak percobaan masuk. Coba lagi dalam ' . $menit . ' menit.';
}
