<?php
// Landing page publik SIMKAS — tampil sebelum pengguna masuk.
// Root index.php mengarahkan pengunjung yang belum login ke halaman ini.
require_once __DIR__ . '/includes/sesi.php';
mulai_sesi();

// Landing tidak menyentuh database: hanya perlu sesi dan helper tampilan.
require_once __DIR__ . '/includes/helpers.php';

if (isset($_SESSION['user'])) {
    redirect('index.php');
}

$judul = 'SIMKAS — Kas kelas yang bisa dipertanggungjawabkan';
$deskripsi = 'Catat pemasukan, pengeluaran, tagihan iuran, dan bukti pembayaran kelas dalam satu tempat. Tiga peran, satu laporan.';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($judul); ?></title>
    <meta name="description" content="<?php echo e($deskripsi); ?>">
    <meta name="theme-color" content="#2f4a7a">
    <link rel="icon" type="image/svg+xml" href="assets/img/logo.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing.css">
    <link rel="stylesheet" href="assets/css/landing-sections.css">
</head>

<body class="lp">
    <a class="lp-skip" href="#konten">Lewati ke konten</a>

    <header class="lp-nav" id="lp-nav">
        <div class="lp-nav-in">
            <a class="lp-brand" href="index.php">SIMKAS<span>.</span></a>
            <nav class="lp-links" aria-label="Navigasi halaman">
                <a href="#alur">Alur uang</a>
                <a href="#fitur">Fitur</a>
                <a href="#peran">Peran</a>
                <a href="#tanya">Pertanyaan</a>
            </nav>
            <div class="lp-nav-cta">
                <a class="lp-btn lp-btn-ghost" href="auth/login.php">Masuk</a>
                <a class="lp-btn lp-btn-solid" href="auth/register.php">Daftar</a>
            </div>
        </div>
    </header>

    <main id="konten">
        <?php include __DIR__ . '/includes/landing/hero.php'; ?>
        <?php include __DIR__ . '/includes/landing/alur.php'; ?>
        <?php include __DIR__ . '/includes/landing/fitur.php'; ?>
        <?php include __DIR__ . '/includes/landing/peran.php'; ?>
        <?php include __DIR__ . '/includes/landing/faq.php'; ?>
        <?php include __DIR__ . '/includes/landing/cta.php'; ?>
    </main>

    <footer class="lp-foot">
        <div class="lp-wrap lp-foot-in">
            <div>
                <b>SIMKAS</b>
                <span>Kas kelas yang bisa dipertanggungjawabkan.</span>
            </div>
            <nav aria-label="Tautan kaki halaman">
                <a href="#alur">Alur uang</a>
                <a href="#fitur">Fitur</a>
                <a href="#tanya">Pertanyaan</a>
                <a href="auth/login.php">Masuk</a>
                <a href="auth/register.php">Daftar</a>
            </nav>
            <p class="lp-credit">Project Based Learning · Kelompok 7 — Oktavian Kusuma Al-Ghifari, Muhammad Nur Rochman, Muhammad Ferdi Afiyanto, Ibni Andarta.</p>
        </div>
    </footer>

    <script src="assets/js/landing.js" defer></script>
</body>

</html>
