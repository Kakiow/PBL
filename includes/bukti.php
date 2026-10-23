<?php

// Upload, penyimpanan, dan pembersihan otomatis bukti pembayaran (storage/uploads).

const BUKTI_MAKS_BYTES = 5 * 1024 * 1024;
const BUKTI_HARI_SIMPAN = 5;

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
        JOIN tagihan_anggota ak ON ak.id = p.id_tagihan_anggota
        JOIN tagihan k ON k.id = ak.id_tagihan
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
    // Cukup sekali per request (pembayaran/index.php memanggilnya lebih dulu, lalu header.php).
    static $sudah = false;

    if ($sudah) {
        return;
    }

    $sudah = true;
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
