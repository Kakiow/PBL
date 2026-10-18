<?php

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
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="flash ' . e($flash['type']) . '">' . e($flash['pesan']) . '</div>';
    }
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
