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
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($page_title); ?> | SIMKAS</title>
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
</head>

<body>
    <header class="topbar">
        <div class="brand">SIMKAS</div>
        <button type="button" id="nav-toggle-btn" class="nav-toggle">☰</button>
        <nav id="main-nav">
            <a href="<?php echo $base; ?>index.php">Dashboard</a>
            <a href="<?php echo $base; ?>grup/index.php">Grup & Anggota</a>
            <?php if ($grup): ?>
            <a href="<?php echo $base; ?>kategori/index.php">Kategori</a>
            <a href="<?php echo $base; ?>kewajiban/index.php">Kewajiban</a>
            <a href="<?php echo $base; ?>pembayaran/index.php">Pembayaran</a>
            <a href="<?php echo $base; ?>transaksi/index.php">Transaksi</a>
            <a href="<?php echo $base; ?>laporan/index.php">Laporan</a>
            <?php endif; ?>
            <span class="nav-user"><?php echo e($_SESSION['user']['nama']); ?></span>
            <a href="<?php echo $base; ?>auth/logout.php">Logout</a>
        </nav>
    </header>
    <main>
        <?php tampil_flash(); ?>