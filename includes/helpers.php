<?php

// Samakan zona waktu PHP dengan PostgreSQL (Asia/Jakarta) agar batas H+5 konsisten.
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/bukti.php';

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

// Tombol "Kembali" di atas judul halaman (dipakai halaman detail/form).
function tautan_kembali($href, $label = 'Kembali')
{
    echo '<a class="back-link" href="' . e($href) . '">'
        . '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"'
        . ' stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7" /></svg>'
        . e($label) . '</a>';
}

// Warna badge untuk status pembayaran (Disetujui/Ditolak/Menunggu) dan status tagihan anggota.
function kelas_status($status)
{
    $kelas = [
        'Lunas' => 'success',
        'Disetujui' => 'success',
        'Ditolak' => 'danger',
        'Menunggu' => 'warning',
    ];

    return $kelas[(string) $status] ?? '';
}

// Hasil di-cache per request: header.php dan halaman modul sering menanyakan peran yang sama.
// Aman karena setiap aksi yang mengubah peran selalu diakhiri redirect.
function role_group($pdo, $id_grup, $id_pengguna)
{
    static $cache = [];
    $kunci = (int) $id_grup . ':' . (int) $id_pengguna;

    if (!array_key_exists($kunci, $cache)) {
        $stmt = $pdo->prepare("SELECT peran FROM anggota_grup WHERE id_grup = ? AND id_pengguna = ?");
        $stmt->execute([$id_grup, $id_pengguna]);
        $cache[$kunci] = $stmt->fetchColumn() ?: null;
    }

    return $cache[$kunci];
}

function cek_role($pdo, $id_grup, $roles)
{
    $role = role_group($pdo, $id_grup, $_SESSION['user']['id']);

    if (!$role || !in_array($role, $roles, true)) {
        flash('error', 'Anda tidak memiliki akses ke halaman tersebut.');
        redirect('../index.php');
    }

    return $role;
}

// Di-cache per request (dipanggil halaman modul lalu header.php) dengan kunci id grup di sesi.
function grup_aktif($pdo)
{
    static $cache = [];

    if (!isset($_SESSION['grup_id'])) {
        return null;
    }

    $id = (int) $_SESSION['grup_id'];

    if (!array_key_exists($id, $cache)) {
        $stmt = $pdo->prepare("SELECT * FROM grup WHERE id = ?");
        $stmt->execute([$id]);
        $cache[$id] = $stmt->fetch();
    }

    return $cache[$id];
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

// Admin & Bendahara: boleh membuat tagihan, memverifikasi, mengelola transaksi, dan melihat laporan.
function peran_pengelola($peran)
{
    return in_array($peran, ['Admin', 'Bendahara'], true);
}

function boleh_kelola_transaksi($peran)
{
    return peran_pengelola($peran);
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

// Tanggal "Y-m-d" yang benar-benar ada (menolak "abc" atau "2026-13-40" sebelum sampai ke PostgreSQL).
function tanggal_valid($nilai)
{
    $nilai = (string) $nilai;
    $tgl = DateTime::createFromFormat('!Y-m-d', $nilai);

    return $tgl !== false && $tgl->format('Y-m-d') === $nilai;
}

// Bebankan satu tagihan ke semua anggota grup yang belum tercatat. Mengembalikan jumlah anggota yang ditambahkan.
function tagihkan_ke_anggota($pdo, $id_kewajiban, $id_grup)
{
    $stmt = $pdo->prepare("
        INSERT INTO anggota_kewajiban (id_kewajiban, id_pengguna)
        SELECT ?, id_pengguna FROM anggota_grup WHERE id_grup = ?
        ON CONFLICT (id_kewajiban, id_pengguna) DO NOTHING
    ");
    $stmt->execute([$id_kewajiban, $id_grup]);

    return $stmt->rowCount();
}

// Anggota yang baru gabung ikut dibebani tagihan grup yang tenggatnya belum lewat.
// Anggota yang keluar lalu gabung lagi tetap memakai status lamanya (ON CONFLICT).
function tagihkan_tagihan_berjalan($pdo, $id_grup, $id_pengguna)
{
    $stmt = $pdo->prepare("
        INSERT INTO anggota_kewajiban (id_kewajiban, id_pengguna)
        SELECT id, ? FROM kewajiban_pembayaran WHERE id_grup = ? AND tenggat_waktu >= CURRENT_DATE
        ON CONFLICT (id_kewajiban, id_pengguna) DO NOTHING
    ");
    $stmt->execute([$id_pengguna, $id_grup]);

    return $stmt->rowCount();
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
