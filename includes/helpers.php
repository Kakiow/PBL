<?php

// Samakan zona waktu PHP dengan PostgreSQL (Asia/Jakarta) agar batas H+5 konsisten.
date_default_timezone_set('Asia/Jakarta');

function e($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function rupiah($angka)
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

function flash($type, $pesan)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'pesan' => $pesan
    ];
}

function tampil_flash()
{
    if (!isset($_SESSION['flash'])) {
        return;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    echo '<div class="flash ' . e($flash['type']) . '" data-flash role="'
        . ($flash['type'] === 'error' ? 'alert' : 'status') . '">'
        . '<span class="flash-text">' . e($flash['pesan']) . '</span>'
        . '<button type="button" class="flash-close" aria-label="Tutup notifikasi">&times;</button>'
        . '</div>';
}

function user_login()
{
    return isset($_SESSION['user']);
}

function role_group($pdo, $id_grup, $id_pengguna)
{
    $stmt = $pdo->prepare("SELECT peran FROM anggota_grup WHERE id_grup = ? AND id_pengguna = ?");
    $stmt->execute([$id_grup, $id_pengguna]);
    $data = $stmt->fetch();
    return $data ? $data['peran'] : null;
}

function cek_role($pdo, $id_grup, $roles)
{
    $role = role_group($pdo, $id_grup, $_SESSION['user']['id']);

    if (!$role || !in_array($role, $roles)) {
        flash('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        redirect('../index.php');
    }

    return $role;
}

function grup_aktif($pdo)
{
    if (!isset($_SESSION['grup_id'])) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT * FROM grup WHERE id = ?");
    $stmt->execute([$_SESSION['grup_id']]);
    return $stmt->fetch();
}

function akses_menu()
{
    return [
        'anggota' => ['Admin', 'Bendahara', 'Anggota'],
        'kewajiban' => ['Admin', 'Bendahara', 'Anggota'],
        'pembayaran' => ['Admin', 'Bendahara', 'Anggota'],
        'transaksi' => ['Admin', 'Bendahara', 'Anggota'],
        'laporan' => ['Admin', 'Bendahara'],
    ];
}

function boleh_kelola_transaksi($peran)
{
    return in_array($peran, ['Admin', 'Bendahara'], true);
}

function label_peran($peran)
{
    $label = [
        'Admin' => 'Admin / Pengelola',
        'Bendahara' => 'Bendahara',
        'Anggota' => 'Anggota',
    ];

    return $label[(string) $peran] ?? 'Belum ada grup';
}

function jumlah_anggota_grup($pdo, $id_grup)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM anggota_grup WHERE id_grup = ?");
    $stmt->execute([$id_grup]);

    return (int) $stmt->fetchColumn();
}

function jumlah_admin_grup($pdo, $id_grup)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM anggota_grup WHERE id_grup = ? AND peran = 'Admin'");
    $stmt->execute([$id_grup]);

    return (int) $stmt->fetchColumn();
}

function grup_aktif_berikutnya($pdo, $id_pengguna, $id_grup_ditinggalkan)
{
    if ((int) ($_SESSION['grup_id'] ?? 0) !== (int) $id_grup_ditinggalkan) {
        return;
    }

    $stmt = $pdo->prepare("SELECT id_grup FROM anggota_grup WHERE id_pengguna = ? ORDER BY id LIMIT 1");
    $stmt->execute([$id_pengguna]);
    $berikutnya = $stmt->fetchColumn();

    if ($berikutnya) {
        $_SESSION['grup_id'] = $berikutnya;
    } else {
        unset($_SESSION['grup_id']);
    }
}

function kode_grup_baru($pdo)
{
    // Buang huruf/angka yang mudah tertukar saat dibagikan: I, O, 0, 1.
    $abjad = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $panjang = 8;

    for ($percobaan = 0; $percobaan < 12; $percobaan++) {
        $kode = '';

        for ($i = 0; $i < $panjang; $i++) {
            $kode .= $abjad[random_int(0, strlen($abjad) - 1)];
        }

        $stmt = $pdo->prepare("SELECT 1 FROM grup WHERE kode_grup = ?");
        $stmt->execute([$kode]);

        if (!$stmt->fetchColumn()) {
            return $kode;
        }
    }

    return null;
}

function kategori_id_untuk($pdo, $id_grup, $nama, $jenis)
{
    $nama = trim($nama);

    $stmt = $pdo->prepare("SELECT id FROM kategori WHERE id_grup = ? AND LOWER(nama_kategori) = LOWER(?) ORDER BY id LIMIT 1");
    $stmt->execute([$id_grup, $nama]);
    $id = $stmt->fetchColumn();

    if ($id) {
        return $id;
    }

    $stmt = $pdo->prepare("INSERT INTO kategori (id_grup, nama_kategori, jenis) VALUES (?, ?, ?) RETURNING id");
    $stmt->execute([$id_grup, $nama, $jenis]);

    return $stmt->fetchColumn();
}

const BUKTI_MAKS_BYTES = 5 * 1024 * 1024;
const BUKTI_HARI_SIMPAN = 5;
const RESET_TOKEN_MENIT = 30;

function folder_bukti($buat = true)
{
    $folder = dirname(__DIR__) . '/storage/uploads';

    if (!is_dir($folder)) {
        if (!$buat) {
            return null;
        }

        if (!@mkdir($folder, 0775, true) && !is_dir($folder)) {
            throw new RuntimeException('Folder penyimpanan bukti tidak dapat dibuat. Periksa izin storage/uploads di server.');
        }

        @chmod($folder, 0775);
    }

    if ($buat && !is_writable($folder)) {
        throw new RuntimeException('Folder penyimpanan bukti tidak dapat ditulis. Periksa izin storage/uploads di server.');
    }

    return $folder;
}

function pesan_error_upload($kode)
{
    switch ((int) $kode) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'Ukuran bukti pembayaran terlalu besar. Maksimal 5 MB.';
        case UPLOAD_ERR_PARTIAL:
            return 'Bukti pembayaran hanya terunggah sebagian. Silakan coba lagi.';
        case UPLOAD_ERR_NO_FILE:
            return 'Bukti pembayaran belum dipilih.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Folder sementara upload tidak tersedia di server.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Server gagal menulis bukti pembayaran ke disk.';
        case UPLOAD_ERR_EXTENSION:
            return 'Upload bukti pembayaran dihentikan oleh ekstensi PHP.';
        default:
            return 'Bukti pembayaran gagal diupload.';
    }
}

function simpan_bukti($berkas)
{
    if (!is_array($berkas) || !isset($berkas['error'])) {
        throw new RuntimeException('Bukti pembayaran belum dipilih.');
    }

    if ((int) $berkas['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(pesan_error_upload($berkas['error']));
    }

    $ukuran = (int) ($berkas['size'] ?? 0);

    if ($ukuran <= 0) {
        throw new RuntimeException('Berkas bukti kosong. Silakan pilih ulang.');
    }

    if ($ukuran > BUKTI_MAKS_BYTES) {
        throw new RuntimeException('Ukuran bukti pembayaran terlalu besar. Maksimal 5 MB.');
    }

    $tmp = (string) ($berkas['tmp_name'] ?? '');

    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('Berkas bukti tidak valid. Silakan pilih ulang.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $jenis = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    if (!isset($jenis[$mime])) {
        throw new RuntimeException('Format bukti harus JPG, PNG, atau PDF.');
    }

    $folder = folder_bukti(true);
    $nama = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $jenis[$mime];
    $tujuan = $folder . '/' . $nama;

    if (!move_uploaded_file($tmp, $tujuan)) {
        throw new RuntimeException('Bukti pembayaran gagal disimpan di server. Periksa izin folder storage/uploads.');
    }

    return $nama;
}

function path_bukti($nama)
{
    $nama = basename((string) $nama);

    if ($nama === '' || $nama === '.' || $nama === '..') {
        return null;
    }

    $folder = folder_bukti(false);

    return $folder === null ? null : $folder . '/' . $nama;
}

function hapus_bukti($nama)
{
    $path = path_bukti($nama);

    if ($path === null) {
        return false;
    }

    if (!is_file($path)) {
        return true;
    }

    return @unlink($path);
}

function tagihan_bukti_kedaluwarsa($tenggat, $hari = BUKTI_HARI_SIMPAN)
{
    $batas = strtotime((string) $tenggat . ' +' . (int) $hari . ' days');

    return $batas !== false && strtotime(date('Y-m-d')) >= $batas;
}

function bersihkan_bukti_kedaluwarsa($pdo, $hari = BUKTI_HARI_SIMPAN)
{
    $stmt = $pdo->prepare("
        SELECT p.id, p.berkas_bukti
        FROM pembayaran p
        JOIN anggota_kewajiban ak ON ak.id = p.id_anggota_kewajiban
        JOIN kewajiban_pembayaran k ON k.id = ak.id_kewajiban
        WHERE p.berkas_bukti <> ''
          AND k.tenggat_waktu <= CURRENT_DATE - (CAST(? AS integer) * INTERVAL '1 day')
    ");
    $stmt->execute([(int) $hari]);
    $baris = $stmt->fetchAll();

    if (!$baris) {
        return 0;
    }

    $ubah = $pdo->prepare("UPDATE pembayaran SET berkas_bukti = '' WHERE id = ?");
    $terhapus = 0;

    foreach ($baris as $row) {
        $path = path_bukti($row['berkas_bukti']);

        // Jangan hapus referensi di database kalau file masih gagal dihapus dari disk.
        if ($path !== null && is_file($path) && !@unlink($path)) {
            error_log('Gagal menghapus bukti pembayaran: ' . $path);
            continue;
        }

        $ubah->execute([$row['id']]);
        $terhapus++;
    }

    return $terhapus;
}

function bersihkan_bukti_otomatis($pdo)
{
    $folder = folder_bukti(false);

    if ($folder === null) {
        return;
    }

    $penanda = $folder . '/.pembersihan_terakhir';

    if (is_file($penanda) && @file_get_contents($penanda) === date('Y-m-d')) {
        return;
    }

    try {
        bersihkan_bukti_kedaluwarsa($pdo);
    } catch (Throwable $e) {
        error_log('Pembersihan bukti gagal: ' . $e->getMessage());
        return;
    }

    @file_put_contents($penanda, date('Y-m-d'));
}

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

        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $cocok)) {
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

function menu_nav($base, $peran, $ada_grup)
{
    $menu = [
        ['seksi' => 'Menu', 'slug' => '', 'label' => 'Dashboard', 'href' => $base . 'index.php'],
        ['seksi' => 'Menu', 'slug' => 'grup', 'label' => 'Grup', 'href' => $base . 'grup/index.php'],
    ];

    if (!$ada_grup) {
        return $menu;
    }

    $akses = akses_menu();
    $items = [
        'anggota' => ['Anggota', 'anggota/index.php', 'Menu'],
        'kewajiban' => ['Tagihan', 'kewajiban/index.php', 'Kas'],
        'pembayaran' => ['Verifikasi', 'pembayaran/index.php', 'Kas'],
        'transaksi' => ['Transaksi', 'transaksi/index.php', 'Kas'],
        'laporan' => ['Laporan', 'laporan/index.php', 'Kas'],
    ];

    foreach ($items as $slug => $info) {
        if (in_array($peran, $akses[$slug] ?? [], true)) {
            $menu[] = ['seksi' => $info[2], 'slug' => $slug, 'label' => $info[0], 'href' => $base . $info[1]];
        }
    }

    return $menu;
}
