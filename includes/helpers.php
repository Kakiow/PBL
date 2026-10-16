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
        'kategori' => ['Admin', 'Bendahara'],
        'kewajiban' => ['Admin', 'Bendahara', 'Anggota'],
        'pembayaran' => ['Admin', 'Bendahara', 'Anggota'],
        'transaksi' => ['Admin', 'Bendahara'],
        'laporan' => ['Admin', 'Bendahara'],
    ];
}

function menu_nav($base, $peran, $ada_grup)
{
    $menu = [
        ['slug' => '', 'label' => 'Dashboard', 'href' => $base . 'index.php'],
        ['slug' => 'grup', 'label' => 'Grup & Anggota', 'href' => $base . 'grup/index.php'],
    ];

    if (!$ada_grup) {
        return $menu;
    }

    $akses = akses_menu();
    $items = [
        'kategori' => ['Kategori', 'kategori/index.php'],
        'kewajiban' => ['Kewajiban', 'kewajiban/index.php'],
        'pembayaran' => ['Pembayaran', 'pembayaran/index.php'],
        'transaksi' => ['Transaksi', 'transaksi/index.php'],
        'laporan' => ['Laporan', 'laporan/index.php'],
    ];

    foreach ($items as $slug => $info) {
        if (in_array($peran, $akses[$slug] ?? [], true)) {
            $menu[] = ['slug' => $slug, 'label' => $info[0], 'href' => $base . $info[1]];
        }
    }

    return $menu;
}
