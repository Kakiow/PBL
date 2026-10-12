<?php
// Layout bersama untuk seluruh halaman role.
$roleNames = ['admin' => 'Admin / Pengelola', 'bendahara' => 'Bendahara', 'anggota' => 'Anggota'];
$menus = [
 'admin' => [
   ['key'=>'dashboard','label'=>'Dashboard','icon'=>'⌂','url'=>'dashboard.php'],
   ['key'=>'grup','label'=>'Kelola Grup','icon'=>'▦','url'=>'grup.php'],
   ['key'=>'anggota','label'=>'Kelola Anggota','icon'=>'♙','url'=>'anggota.php'],
   ['key'=>'pengeluaran','label'=>'Pengeluaran','icon'=>'↗','url'=>'pengeluaran.php'],
   ['key'=>'laporan','label'=>'Laporan','icon'=>'▤','url'=>'laporan.php']
 ],
 'bendahara' => [
   ['key'=>'dashboard','label'=>'Dashboard','icon'=>'⌂','url'=>'dashboard.php'],
   ['key'=>'tagihan','label'=>'Tagihan','icon'=>'▤','url'=>'tagihan.php'],
   ['key'=>'verifikasi','label'=>'Verifikasi','icon'=>'✓','url'=>'verifikasi.php'],
   ['key'=>'pengeluaran','label'=>'Pengeluaran','icon'=>'↗','url'=>'pengeluaran.php'],
   ['key'=>'laporan','label'=>'Laporan','icon'=>'▤','url'=>'laporan.php']
 ],
 'anggota' => [
   ['key'=>'dashboard','label'=>'Dashboard','icon'=>'⌂','url'=>'dashboard.php'],
   ['key'=>'grup','label'=>'Informasi Grup','icon'=>'▦','url'=>'grup.php'],
   ['key'=>'tagihan','label'=>'Kewajiban / Tagihan','icon'=>'▤','url'=>'tagihan.php'],
   ['key'=>'pembayaran','label'=>'Pembayaran','icon'=>'↗','url'=>'pembayaran.php']
 ]
];
$role = $role ?? 'admin'; $active = $active ?? 'dashboard';
$pageTitle = $pageTitle ?? 'Dashboard'; $pageDescription = $pageDescription ?? 'Ringkasan informasi kas dan keuangan.';
$base = '../../';
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?= htmlspecialchars($pageTitle) ?> | KasKelas</title><link rel="stylesheet" href="<?= $base ?>assets/css/style.css"></head>
<body class="app-body"><div class="app-layout">
  <aside class="sidebar" id="sidebar">
    <a class="brand brand-light sidebar-brand" href="dashboard.php"><span class="brand-mark">K</span><span>KasKelas</span></a>
    <div class="sidebar-label">MENU UTAMA</div>
    <nav class="side-nav" aria-label="Navigasi utama">
      <?php foreach ($menus[$role] as $menu): ?>
        <a class="nav-link <?= $active === $menu['key'] ? 'active' : '' ?>" href="<?= htmlspecialchars($menu['url']) ?>"><span class="nav-icon"><?= $menu['icon'] ?></span><span><?= htmlspecialchars($menu['label']) ?></span></a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-bottom"><div class="sidebar-user"><span class="avatar">K</span><span><strong><?= htmlspecialchars($roleNames[$role]) ?></strong><small>Demo pengguna</small></span></div><a class="nav-link logout-link" href="<?= $base ?>index.php"><span class="nav-icon">↪</span><span>Keluar</span></a></div>
  </aside>
  <main class="main-area">
    <header class="topbar"><button type="button" class="menu-toggle" id="menu-toggle" aria-label="Buka menu">☰</button><div class="breadcrumb">KasKelas <span>/</span> <strong><?= htmlspecialchars($pageTitle) ?></strong></div></header>
    <section class="page-content"><div class="page-heading"><div><span class="eyebrow blue"><?= strtoupper(htmlspecialchars($roleNames[$role])) ?></span><h1><?= htmlspecialchars($pageTitle) ?></h1><p><?= htmlspecialchars($pageDescription) ?></p></div></div>
      <?= $content ?? '' ?>
    </section>
    <footer class="app-footer">KasKelas <span>Sistem Informasi Manajemen Kas</span></footer>
  </main>
</div><script src="<?= $base ?>assets/js/script.js"></script></body></html>
