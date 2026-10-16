<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/helpers.php';

$base = '../';
if (basename(dirname($_SERVER['SCRIPT_FILENAME'])) === basename(__DIR__)) {
    $base = '';
}

$page_title = $page_title ?? 'SIMKAS';
$grup = null;
if (isset($_SESSION['grup_id']) && isset($pdo)) {
    $grup = grup_aktif($pdo);
}

$app_root = str_replace('\\', '/', dirname(__DIR__));
$script_file = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
$script_rel = $script_file;
if ($app_root !== '' && strpos($script_file, $app_root) === 0) {
    $script_rel = substr($script_file, strlen($app_root));
}
$segmen = array_values(array_filter(explode('/', trim($script_rel, '/'))));
$slug_aktif = count($segmen) > 1 ? $segmen[0] : '';

$menu = [
    ['slug' => '', 'label' => 'Dashboard', 'href' => $base . 'index.php'],
    ['slug' => 'grup', 'label' => 'Grup & Anggota', 'href' => $base . 'grup/index.php'],
];
if ($grup) {
    $menu[] = ['slug' => 'kategori', 'label' => 'Kategori', 'href' => $base . 'kategori/index.php'];
    $menu[] = ['slug' => 'kewajiban', 'label' => 'Kewajiban', 'href' => $base . 'kewajiban/index.php'];
    $menu[] = ['slug' => 'pembayaran', 'label' => 'Pembayaran', 'href' => $base . 'pembayaran/index.php'];
    $menu[] = ['slug' => 'transaksi', 'label' => 'Transaksi', 'href' => $base . 'transaksi/index.php'];
    $menu[] = ['slug' => 'laporan', 'label' => 'Laporan', 'href' => $base . 'laporan/index.php'];
}

$nama_user = trim((string) ($_SESSION['user']['nama'] ?? ''));
$email_user = trim((string) ($_SESSION['user']['email'] ?? ''));
$inisial = $nama_user !== ''
    ? strtoupper(function_exists('mb_substr') ? mb_substr($nama_user, 0, 1) : substr($nama_user, 0, 1))
    : 'U';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($page_title); ?> | SIMKAS</title>
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/navbar.css">
</head>

<body>
    <header class="topbar" id="topbar">
        <a class="brand" href="<?php echo $base; ?>index.php">
            <span class="brand-mark" aria-hidden="true">S</span>
            <span class="brand-name">SIMKAS</span>
        </a>
        <button type="button" id="nav-toggle-btn" class="nav-toggle" aria-label="Buka menu navigasi"
            aria-controls="main-nav" aria-expanded="false">
            <span class="nav-toggle-box" aria-hidden="true"><span></span><span></span><span></span></span>
        </button>
        <nav id="main-nav" class="main-nav" aria-label="Navigasi utama">
            <ul class="nav-links">
                <?php foreach ($menu as $item): $aktif = $item['slug'] === $slug_aktif; ?>
                <li>
                    <a class="nav-link<?php echo $aktif ? ' is-active' : ''; ?>" href="<?php echo $item['href']; ?>"
                        <?php echo $aktif ? 'aria-current="page"' : ''; ?>><?php echo e($item['label']); ?></a>
                </li>
                <?php endforeach; ?>
            </ul>
            <div class="nav-user" id="nav-user">
                <button type="button" class="user-btn" id="user-menu-btn" aria-haspopup="true" aria-expanded="false"
                    aria-controls="user-dropdown">
                    <span class="avatar" aria-hidden="true"><?php echo e($inisial); ?></span>
                    <span class="user-meta">
                        <span class="user-name"><?php echo e($nama_user); ?></span>
                        <span class="user-email"><?php echo e($email_user); ?></span>
                    </span>
                    <svg class="user-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </button>
                <div class="user-dropdown" id="user-dropdown" role="menu">
                    <div class="dropdown-head">
                        <span class="avatar lg" aria-hidden="true"><?php echo e($inisial); ?></span>
                        <span>
                            <strong><?php echo e($nama_user); ?></strong>
                            <small><?php echo e($email_user); ?></small>
                        </span>
                    </div>
                    <a class="dropdown-item" role="menuitem" href="<?php echo $base; ?>grup/index.php">Kelola Grup</a>
                    <a class="dropdown-item dropdown-item--danger" role="menuitem"
                        href="<?php echo $base; ?>auth/logout.php">Logout</a>
                </div>
            </div>
        </nav>
    </header>
    <main>
        <?php tampil_flash(); ?>
