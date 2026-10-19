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
