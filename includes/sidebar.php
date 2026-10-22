<?php
// Partial sidebar: merender <aside class="sidebar">.
// Disediakan oleh includes/header.php:
//   $base, $menu, $slug_aktif, $badge_nav, $label_peran, $ada_grup,
//   $nama_user, $email_user, $inisial.

$ikon_nav = [
    '' => '<rect x="3" y="4" width="6" height="6" rx="1.5"/><rect x="15" y="4" width="6" height="6" rx="1.5"/><rect x="3" y="14" width="6" height="6" rx="1.5"/><rect x="15" y="14" width="6" height="6" rx="1.5"/>',
    'grup' => '<rect x="7" y="3" width="14" height="14" rx="2"/><path d="M17 21H5a2 2 0 0 1-2-2V7"/>',
    'anggota' => '<circle cx="9" cy="8" r="3.5"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><path d="M16 5a3.5 3.5 0 0 1 0 7"/><path d="M16.5 19a5.5 5.5 0 0 1 4.5-5"/>',
    'kewajiban' => '<path d="M4 4h16v16l-3-2-2 2-3-2-3 2-2-2-3 2z"/><path d="M8 9h8M8 13h5"/>',
    'pembayaran' => '<path d="M20 6L9 17l-5-5"/>',
    'transaksi' => '<path d="M7 8h10l-3-3"/><path d="M17 16H7l3 3"/>',
    'laporan' => '<path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/>',
    'profil' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
];

$seksi = [];
foreach ($menu as $item) {
    $seksi[$item['seksi'] ?? 'Menu'][] = $item;
}
$tampil_label_seksi = count($seksi) > 1;

$tulis_item = function (array $item) use ($ikon_nav, $badge_nav, $base, $slug_aktif) {
    $aktif = $item['slug'] === $slug_aktif;
    $ikon = $ikon_nav[$item['slug']] ?? '<circle cx="12" cy="12" r="3"/>';
    $badge = (int) ($badge_nav[$item['slug']] ?? 0);
    $label = e($item['label']);
    ?>
                    <a class="nav-item<?php echo $aktif ? ' is-active' : ''; ?>" href="<?php echo $item['href']; ?>"
                        data-label="<?php echo $label; ?>" aria-label="<?php echo $label; ?>"
                        <?php echo $aktif ? 'aria-current="page"' : ''; ?>>
                        <span class="nav-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round"><?php echo $ikon; ?></svg>
                        </span>
                        <span class="nav-label"><?php echo $label; ?></span>
                        <?php if ($badge > 0): ?><span class="nav-badge"><?php echo $badge; ?></span><?php endif; ?>
                        <svg class="nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
    <?php
};
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-head">
        <a class="brand" href="<?php echo $base; ?>index.php">
            <span class="brand-mark" aria-hidden="true">S</span>
            <span class="brand-text">
                <span class="brand-name">SIMKAS</span>
                <span class="brand-role"><?php echo e($label_peran); ?></span>
            </span>
        </a>
        <button type="button" class="rail-toggle" id="rail-toggle" aria-label="Ciutkan menu navigasi"
            aria-controls="sidebar" aria-pressed="false">
            <svg class="rail-icon rail-icon--val" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M11 17l-5-5 5-5" /><path d="M18 17l-5-5 5-5" />
            </svg>
            <svg class="rail-icon rail-icon--col" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M13 7l5 5-5 5" /><path d="M6 7l5 5-5 5" />
            </svg>
        </button>
    </div>
    <nav class="sidebar-nav" id="sidebar-nav" aria-label="Navigasi utama">
        <div class="nav-list">
            <?php foreach ($seksi as $nama_seksi => $items_seksi): ?>
            <?php if ($tampil_label_seksi): ?><span class="nav-group"><?php echo e($nama_seksi); ?></span><?php endif; ?>
            <?php foreach ($items_seksi as $item) { $tulis_item($item); } ?>
            <?php endforeach; ?>
        </div>
    </nav>
    <div class="sidebar-foot">
        <a class="user-card" href="<?php echo $base; ?>profil/index.php"
            aria-label="Buka profil <?php echo e($nama_user); ?>">
            <span class="avatar" aria-hidden="true"><?php echo e($inisial); ?></span>
            <span class="user-meta">
                <span class="user-name"><?php echo e($nama_user); ?></span>
                <span class="user-email"><?php echo e($email_user); ?></span>
            </span>
        </a>
        <a class="logout-btn" href="<?php echo $base; ?>auth/logout.php" data-label="Logout" aria-label="Logout">
            <span class="logout-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <path d="M16 17l5-5-5-5M21 12H9" />
                </svg>
            </span>
            <span class="nav-label">Logout</span>
        </a>
    </div>
</aside>
