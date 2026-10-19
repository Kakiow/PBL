<?php

// Token reset password + pengiriman email lewat API Brevo.
// Hanya dimuat oleh auth/lupa_password.php dan auth/reset_password.php.

const RESET_TOKEN_MENIT = 30;

function url_dasar_aplikasi($konfigurasi = [])
{
    $app_url = trim((string) ($konfigurasi['app_url'] ?? ''));

    if ($app_url !== '') {
        return rtrim($app_url, '/');
    }

    $skema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $base = str_replace('\\', '/', dirname(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))));
    $base = ($base === '.' || $base === '/') ? '' : rtrim($base, '/');

    return $skema . '://' . $host . $base;
}

function buat_token_reset($pdo, $id_pengguna)
{
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);

    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("UPDATE reset_password SET digunakan_pada = CURRENT_TIMESTAMP WHERE id_pengguna = ? AND digunakan_pada IS NULL");
        $stmt->execute([$id_pengguna]);

        $stmt = $pdo->prepare("INSERT INTO reset_password (id_pengguna, token_hash, kedaluwarsa_pada) VALUES (?, ?, CURRENT_TIMESTAMP + (CAST(? AS integer) * INTERVAL '1 minute'))");
        $stmt->execute([$id_pengguna, $hash, RESET_TOKEN_MENIT]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }

    return $token;
}

function cari_token_reset($pdo, $token)
{
    if (!is_string($token) || !preg_match('/\A[a-f0-9]{64}\z/', $token)) {
        return false;
    }

    $stmt = $pdo->prepare("
        SELECT r.id, r.id_pengguna, r.kedaluwarsa_pada, u.nama, u.email
        FROM reset_password r
        JOIN pengguna u ON u.id = r.id_pengguna
        WHERE r.token_hash = ?
          AND r.digunakan_pada IS NULL
          AND r.kedaluwarsa_pada > CURRENT_TIMESTAMP
          AND u.apakah_aktif = TRUE
    ");
    $stmt->execute([hash('sha256', $token)]);

    return $stmt->fetch();
}

function bersihkan_token_reset($pdo)
{
    $stmt = $pdo->prepare("
        DELETE FROM reset_password
        WHERE kedaluwarsa_pada < CURRENT_TIMESTAMP - INTERVAL '1 day'
           OR (digunakan_pada IS NOT NULL AND digunakan_pada < CURRENT_TIMESTAMP - INTERVAL '1 day')
    ");
    $stmt->execute();
}

function kirim_email_reset($konfigurasi, $email, $nama, $tautan)
{
    $api_key = trim((string) ($konfigurasi['brevo_api_key'] ?? ''));
    $from_email = trim((string) ($konfigurasi['from_email'] ?? ''));
    $from_name = trim((string) ($konfigurasi['from_name'] ?? 'SIMKAS')) ?: 'SIMKAS';

    if ($api_key === '' || $from_email === '') {
        throw new RuntimeException('Konfigurasi email belum lengkap.');
    }

    $nama = trim((string) $nama) ?: 'Pengguna SIMKAS';
    $tautan_aman = e($tautan);
    $nama_aman = e($nama);

    $html = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;color:#1f2937;line-height:1.6">'
        . '<h2 style="margin:0 0 16px">Reset Password SIMKAS</h2>'
        . '<p>Halo ' . $nama_aman . ',</p>'
        . '<p>Kami menerima permintaan untuk mengatur ulang password akun SIMKAS Anda. Klik tombol di bawah ini:</p>'
        . '<p style="margin:24px 0">'
        . '<a href="' . $tautan_aman . '" style="background:#2563eb;color:#ffffff;padding:12px 20px;border-radius:8px;text-decoration:none;display:inline-block">Reset Password</a>'
        . '</p>'
        . '<p>Link ini berlaku selama ' . RESET_TOKEN_MENIT . ' menit dan hanya bisa dipakai sekali.</p>'
        . '<p>Jika Anda tidak meminta reset password, abaikan email ini.</p>'
        . '</div>';

    $teks = "Halo {$nama},\n\n"
        . "Kami menerima permintaan untuk mengatur ulang password akun SIMKAS Anda.\n"
        . "Buka link berikut untuk membuat password baru:\n\n"
        . $tautan . "\n\n"
        . "Link berlaku selama " . RESET_TOKEN_MENIT . " menit dan hanya bisa dipakai sekali.\n"
        . "Jika Anda tidak meminta reset password, abaikan email ini.\n";

    $payload = [
        'sender' => [
            'name' => $from_name,
            'email' => $from_email,
        ],
        'to' => [
            [
                'email' => $email,
                'name' => $nama,
            ],
        ],
        'subject' => 'Reset Password SIMKAS',
        'htmlContent' => $html,
        'textContent' => $teks,
    ];

    $body = json_encode($payload);

    if ($body === false) {
        throw new RuntimeException('Gagal menyiapkan email reset.');
    }

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'api-key: ' . $api_key,
    ];

    $url = 'https://api.brevo.com/v3/smtp/email';
    $status = 0;
    $respons = '';
    $error = '';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
        ]);

        $respons = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($respons === false) {
            $respons = '';
        }
    } else {
        $opsi = [
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ];

        $respons = @file_get_contents($url, false, stream_context_create($opsi));

        // PHP 8.4+ punya http_get_last_response_headers(); $http_response_header deprecated sejak 8.5.
        $header_respons = function_exists('http_get_last_response_headers')
            ? (http_get_last_response_headers() ?? [])
            : (get_defined_vars()['http_response_header'] ?? []);

        if (isset($header_respons[0]) && preg_match('/\s(\d{3})\s/', $header_respons[0], $cocok)) {
            $status = (int) $cocok[1];
        }

        if ($respons === false) {
            $error = error_get_last()['message'] ?? 'Permintaan HTTP gagal.';
            $respons = '';
        }
    }

    if ($status < 200 || $status >= 300) {
        error_log('Brevo reset password gagal. Status: ' . $status . ' Respons: ' . $respons . ' Error: ' . $error);
        throw new RuntimeException('Email reset gagal dikirim.');
    }

    return true;
}

