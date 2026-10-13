
<?php
$role = 'admin';
$active = 'pengeluaran';
$pageTitle = 'Pengeluaran';
$pageDescription = 'Pantau pengeluaran dari grup-grup yang dikelola.';
$content = <<<'HTML'
<div class="stats-grid">
    <article class="stat-card">
        <div class="stat-top">Total Pengeluaran <span class="stat-icon orange">↗</span></div>
        <div class="stat-value">Rp 3,2 jt</div>
        <div class="stat-foot">Periode berjalan</div>
    </article>
    <article class="stat-card">
        <div class="stat-top">Transaksi <span class="stat-icon">▤</span></div>
        <div class="stat-value">24</div>
        <div class="stat-foot">Semua grup</div>
    </article>
    <article class="stat-card">
        <div class="stat-top">Grup Aktif <span class="stat-icon green">▦</span></div>
        <div class="stat-value">8</div>
        <div class="stat-foot">Memiliki catatan kas</div>
    </article>
    <article class="stat-card">
        <div class="stat-top">Perlu Ditinjau <span class="stat-icon purple">!</span></div>
        <div class="stat-value">2</div>
        <div class="stat-foot">Data contoh</div>
    </article>
</div>

<section class="content-card">
    <div class="content-card-header">
        <div>
            <h3>Riwayat Pengeluaran</h3>
            <p>Daftar transaksi dari berbagai grup.</p>
        </div>
    </div>

    <div class="table-tools">
        <input class="table-search" data-filter-table="#expenses-table" placeholder="Cari transaksi...">
        <select>
            <option>Semua Grup</option>
            <option>Angkatan 2025</option>
            <option>Angkatan 2024</option>
        </select>
    </div>

    <div class="table-wrap">
        <table id="expenses-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Grup</th>
                    <th>Keterangan</th>
                    <th>Kategori</th>
                    <th>Nominal</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>10 Okt 2026</td>
                    <td>Angkatan 2025</td>
                    <td>Konsumsi rapat</td>
                    <td>Kegiatan</td>
                    <td class="negative">- Rp 350.000</td>
                    <td><span class="status success">Tercatat</span></td>
                </tr>
                <tr>
                    <td>08 Okt 2026</td>
                    <td>Angkatan 2024</td>
                    <td>Perlengkapan acara</td>
                    <td>Operasional</td>
                    <td class="negative">- Rp 625.000</td>
                    <td><span class="status success">Tercatat</span></td>
                </tr>
                <tr>
                    <td>04 Okt 2026</td>
                    <td>Angkatan 2023</td>
                    <td>Fotokopi dokumen</td>
                    <td>Administrasi</td>
                    <td class="negative">- Rp 85.000</td>
                    <td><span class="status success">Tercatat</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
HTML;

require '../../components/layout.php';