<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/helpers.php';

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

$page_title = $page_title ?? 'KasKelas';
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
    <title><?php echo e($page_title); ?> | KasKelas</title>
    <link rel="icon" type="image/svg+xml" href="<?php echo $base; ?>assets/img/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/layout.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/sidebar.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/inputs.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/form.css">
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/print.css">
    <?php foreach ($page_css as $css): ?>
    <link rel="stylesheet" href="<?php echo $base . 'assets/css/' . e($css); ?>">
    <?php endforeach; ?>
</head>

<body>
    <div class="app-shell">
        <div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-head">
                <a class="brand" href="<?php echo $base; ?>index.php">
                    <span class="brand-name">KasKelas</span>
                    <span class="brand-role"><?php echo e($label_peran); ?></span>
                </a>
            </div>
            <nav class="sidebar-nav" aria-label="Navigasi utama">
                <ul class="nav-list">
                    <?php foreach ($menu as $item): $aktif = $item['slug'] === $slug_aktif; ?>
                    <li>
                        <a class="nav-item<?php echo $aktif ? ' is-active' : ''; ?>" href="<?php echo $item['href']; ?>"
                            <?php echo $aktif ? 'aria-current="page"' : ''; ?>><?php echo e($item['label']); ?></a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <div class="sidebar-foot">
                <div class="user-card">
                    <span class="avatar" aria-hidden="true"><?php echo e($inisial); ?></span>
                    <span class="user-meta">
                        <span class="user-name"><?php echo e($nama_user); ?></span>
                        <span class="user-email"><?php echo e($email_user); ?></span>
                    </span>
                </div>
                <a class="logout-btn" href="<?php echo $base; ?>auth/logout.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <path d="M16 17l5-5-5-5M21 12H9" />
                    </svg>
                    Logout
                </a>
            </div>
        </aside>
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
