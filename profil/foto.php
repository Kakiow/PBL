<?php

require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sukses' => false, 'pesan' => 'Metode permintaan tidak diizinkan.']);
    exit;
}

if (!csrf_valid($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['sukses' => false, 'pesan' => 'Sesi formulir berakhir. Silakan muat ulang halaman.']);
    exit;
}

$id_user = (int) $_SESSION['user']['id'];
$aksi = (string) ($_POST['aksi'] ?? '');

$folder_avatars = dirname(__DIR__) . '/storage/avatars';
if (!is_dir($folder_avatars)) {
    if (!@mkdir($folder_avatars, 0775, true) && !is_dir($folder_avatars)) {
        http_response_code(500);
        echo json_encode(['sukses' => false, 'pesan' => 'Folder penyimpanan foto profil gagal dibuat.']);
        exit;
    }
    @chmod($folder_avatars, 0775);
}

if ($aksi === 'simpan_foto') {
    $berkas = $_FILES['foto'] ?? null;

    if (!is_array($berkas) || !isset($berkas['error']) || (int) $berkas['error'] !== UPLOAD_ERR_OK) {
        $pesan = 'Berkas foto tidak valid atau gagal diunggah.';
        if (isset($berkas['error']) && (int) $berkas['error'] === UPLOAD_ERR_INI_SIZE) {
            $pesan = 'Ukuran berkas melebihi batas upload server.';
        }
        http_response_code(400);
        echo json_encode(['sukses' => false, 'pesan' => $pesan]);
        exit;
    }

    $ukuran = (int) ($berkas['size'] ?? 0);
    $maks_bytes = 5 * 1024 * 1024; // 5 MB

    if ($ukuran <= 0 || $ukuran > $maks_bytes) {
        http_response_code(400);
        echo json_encode(['sukses' => false, 'pesan' => 'Ukuran foto profil maksimal 5 MB.']);
        exit;
    }

    $tmp = (string) ($berkas['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        http_response_code(400);
        echo json_encode(['sukses' => false, 'pesan' => 'Berkas unggahan tidak sah.']);
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    $ekstensi_sah = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($ekstensi_sah[$mime])) {
        http_response_code(400);
        echo json_encode(['sukses' => false, 'pesan' => 'Format foto harus berupa JPG, PNG, atau WebP.']);
        exit;
    }

    $nama_baru = sprintf(
        'avatar_%d_%s_%s.%s',
        $id_user,
        date('Ymd_His'),
        bin2hex(random_bytes(6)),
        $ekstensi_sah[$mime]
    );

    $tujuan = $folder_avatars . '/' . $nama_baru;
    if (!move_uploaded_file($tmp, $tujuan)) {
        http_response_code(500);
        echo json_encode(['sukses' => false, 'pesan' => 'Gagal menyimpan foto profil ke disk server.']);
        exit;
    }

    @chmod($tujuan, 0664);

    // Ambil foto lama untuk dihapus dari disk
    $stmt = $pdo->prepare("SELECT foto_profil FROM pengguna WHERE id = ?");
    $stmt->execute([$id_user]);
    $foto_lama = (string) $stmt->fetchColumn();

    if ($foto_lama !== '' && $foto_lama !== $nama_baru) {
        $berkas_lama = $folder_avatars . '/' . basename($foto_lama);
        if (is_file($berkas_lama)) {
            @unlink($berkas_lama);
        }
    }

    $upd = $pdo->prepare("UPDATE pengguna SET foto_profil = ? WHERE id = ?");
    $upd->execute([$nama_baru, $id_user]);

    $_SESSION['user']['foto_profil'] = $nama_baru;

    $url = base_url_relatif() . 'storage/avatars/' . rawurlencode($nama_baru) . '?v=' . time();

    echo json_encode([
        'sukses' => true,
        'pesan' => 'Foto profil berhasil diperbarui.',
        'foto' => $nama_baru,
        'url' => $url,
    ]);
    exit;
}

if ($aksi === 'hapus_foto') {
    $stmt = $pdo->prepare("SELECT foto_profil FROM pengguna WHERE id = ?");
    $stmt->execute([$id_user]);
    $foto_lama = (string) $stmt->fetchColumn();

    if ($foto_lama !== '') {
        $berkas_lama = $folder_avatars . '/' . basename($foto_lama);
        if (is_file($berkas_lama)) {
            @unlink($berkas_lama);
        }
    }

    $upd = $pdo->prepare("UPDATE pengguna SET foto_profil = NULL WHERE id = ?");
    $upd->execute([$id_user]);

    $_SESSION['user']['foto_profil'] = null;

    echo json_encode([
        'sukses' => true,
        'pesan' => 'Foto profil berhasil dihapus.',
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['sukses' => false, 'pesan' => 'Aksi tidak dikenal.']);
exit;
