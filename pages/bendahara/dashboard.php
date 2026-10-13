
<?php
$role = 'bendahara';
$active = 'dashboard';
$pageTitle = 'Dashboard';
$pageDescription = 'Pantau saldo grup, tagihan, dan pembayaran anggota.';
$content = <<<'HTML'
<div class="stats-grid">
    <article class="stat-card">
        <div class="stat-top">Grup Aktif <span class="stat-icon">▦</span></div>
        <div class="stat-value">Angkatan 2025</div>
        <div class="stat-foot">Grup yang dikelola</div>
    </article>
    <article class="stat-card">
        <div class="stat-top">Total Anggota <span class="stat-icon green">♙</span></div>
        <div class="stat-value">32</div>
        <div class="stat-foot">Anggota dalam grup</div>
    </article>
    <article class="stat-card">
        <div class="stat-top">Saldo Grup <span class="stat-icon purple">Rp</span></div>
        <div class="stat-value">Rp 1.250.000</div>
        <div class="stat-foot">Saldo dari transaksi terverifikasi</div>
    </article>
    <article class="stat-card">
        <div class="stat-top">Menunggu Verifikasi <span class="stat-icon orange">!</span></div>
        <div class="stat-value">5</div>
        <div class="stat-foot">Bukti pembayaran</div>
    </article>
</div>

<div class="dashboard-grid">
    <section class="panel">
        <div class="panel-heading">
            <div>
                <h3>Pembayaran Terbaru</h3>
                <p>Periksa bukti bayar yang baru dikirim.</p>
            </div>
            <a class="text-link" href="verifikasi.php">Lihat semua →</a>
        </div>

        <div class="transaction-list">
            <div class="transaction-row">
                <span class="person-avatar">DP</span>
                <div class="transaction-info">
                    <strong>Dinda Putri · Kas Oktober</strong>
                    <small>10 Okt 2026 · Transfer bank</small>
                </div>
                <span class="status pending">Menunggu</span>
            </div>
            <div class="transaction-row">
                <span class="person-avatar">AF</span>
                <div class="transaction-info">
                    <strong>Ahmad Fauzi · Kas Oktober</strong>
                    <small>10 Okt 2026 · Transfer bank</small>
                </div>
                <span class="status pending">Menunggu</span>
            </div>
            <div class="transaction-row">
                <span class="person-avatar">SR</span>
                <div class="transaction-info">
                    <strong>Salsa Rahma · Kas Oktober</strong>
                    <small>09 Okt 2026 · Tunai</small>
                </div>
                <span class="status pending">Menunggu</span>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <h3>Akses Cepat</h3>
                <p>Kelola kas grup.</p>
            </div>
        </div>

        <div class="quick-actions">
            <a class="quick-action" href="tagihan.php"><span>＋</span>Buat Tagihan</a>
            <a class="quick-action" href="verifikasi.php"><span>✓</span>Verifikasi</a>
            <a class="quick-action" href="pengeluaran.php"><span>↗</span>Catat Pengeluaran</a>
            <a class="quick-action" href="laporan.php"><span>▤</span>Laporan</a>
        </div>

        <div style="height:20px"></div>

        <div class="group-card">
            <span class="group-symbol">25</span>
            <div class="group-main">
                <strong>Angkatan 2025</strong>
                <small>Periode kas bulanan</small>
            </div>
            <div class="group-balance">
                Rp 1,25 jt
                <small>Saldo saat ini</small>
            </div>
        </div>
    </section>
</div>
HTML;

require '../../components/layout.php';