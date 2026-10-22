<?php

require_once __DIR__ . '/sesi.php';
mulai_sesi();

require_once __DIR__ . '/helpers.php';

// Bersihkan bukti yang sudah lewat 5 hari dari tenggat, maksimal sekali sehari.
if (isset($pdo) && $pdo instanceof PDO) {
    bersihkan_bukti_otomatis($pdo);
}

$app_root = str_replace('\\', '/', dirname(__DIR__));
$script_file = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');

$base = '../';
$script_rel = $script_file;
if ($app_root !== '' && strpos($script_file, $app_root) === 0) {
    $script_rel = substr($script_file, strlen($app_root));
    $sub_dir = dirname(trim($script_rel, '/'));
    $base = ($sub_dir === '.' || $sub_dir === '')
        ? ''
        : str_repeat('../', count(array_filter(explode('/', $sub_dir))));
}

$page_title = $page_title ?? 'SIMKAS';
$page_css = $page_css ?? [];
$grup = null;
if (isset($_SESSION['grup_id']) && isset($pdo)) {
    $grup = grup_aktif($pdo);
}

$peran_aktif = null;
if ($grup && isset($_SESSION['user']['id'])) {
    $peran_aktif = role_group($pdo, $grup['id'], $_SESSION['user']['id']);
}

$segmen = array_values(array_filter(explode('/', trim($script_rel, '/'))));
$slug_aktif = count($segmen) > 1 ? $segmen[0] : '';

$menu = menu_nav($base, $peran_aktif, (bool) $grup);

// Angka hidup di sidebar: hanya yang punya aksi, bukan sekadar counter.
// Satu query untuk kedua badge; Verifikasi hanya dihitung untuk Admin/Bendahara.
$badge_nav = [];
if ($grup && $peran_aktif) {
    $stmt = $pdo->prepare("
        SELECT
            COUNT(DISTINCT ak.id) FILTER (WHERE ak.id_pengguna = ? AND ak.status IN ('Belum_Bayar', 'Ditolak')) AS kewajiban,
            COUNT(p.id) AS pembayaran
        FROM kewajiban_pembayaran k
        JOIN anggota_kewajiban ak ON ak.id_kewajiban = k.id
        LEFT JOIN pembayaran p ON p.id_anggota_kewajiban = ak.id AND p.status = 'Menunggu'
        WHERE k.id_grup = ?
    ");
    $stmt->execute([$_SESSION['user']['id'], $grup['id']]);
    $hitung = $stmt->fetch();

    $badge_nav['kewajiban'] = (int) $hitung['kewajiban'];
    if (peran_pengelola($peran_aktif)) {
        $badge_nav['pembayaran'] = (int) $hitung['pembayaran'];
    }
}

$nama_user = trim((string) ($_SESSION['user']['nama'] ?? ''));
$email_user = trim((string) ($_SESSION['user']['email'] ?? ''));
$inisial = $nama_user !== ''
    ? strtoupper(function_exists('mb_substr') ? mb_substr($nama_user, 0, 1) : substr($nama_user, 0, 1))
    : 'U';
$label_peran = label_peran($peran_aktif);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($page_title); ?> | SIMKAS</title>
    <link rel="icon" type="image/svg+xml" href="<?php echo aset($base, 'assets/img/logo.svg'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?php echo aset($base, 'assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo aset($base, 'assets/css/layout.css'); ?>">
    <link rel="stylesheet" href="<?php echo aset($base, 'assets/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="<?php echo aset($base, 'assets/css/nav.css'); ?>">
    <link rel="stylesheet" href="<?php echo aset($base, 'assets/css/rail.css'); ?>">
    <link rel="stylesheet" href="<?php echo aset($base, 'assets/css/inputs.css'); ?>">
    <link rel="stylesheet" href="<?php echo aset($base, 'assets/css/form.css'); ?>">
    <link rel="stylesheet" href="<?php echo aset($base, 'assets/css/toast.css'); ?>">
    <link rel="stylesheet" href="<?php echo aset($base, 'assets/css/print.css'); ?>">
    <?php foreach ($page_css as $css): ?>
    <link rel="stylesheet" href="<?php echo aset($base, 'assets/css/' . e($css)); ?>">
    <?php endforeach; ?>
</head>

<body>
    <div class="app-shell">
        <div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
<?php include __DIR__ . '/sidebar.php'; ?>
        <div class="app-main">
            <header class="topbar">
                <button type="button" class="nav-toggle" id="nav-toggle-btn" aria-label="Buka menu navigasi"
                    aria-controls="sidebar" aria-expanded="false">
                    <span class="nav-toggle-box" aria-hidden="true"><span></span><span></span><span></span></span>
                </button>
                <span class="topbar-title"><?php echo e($page_title); ?></span>
            </header>
            <main class="page-content">
                <?php tampil_flash(); ?>
