
<?php
$role = 'anggota';
$active = 'grup';
$pageTitle = 'Informasi Grup';
$pageDescription = 'Informasi grup angkatan dan pengurus kas.';
$content = <<<'HTML'
<section class="content-card">
    <div class="content-card-header">
        <div>
            <h3>Angkatan 2025</h3>
            <p>Informasi grup tempat akunmu terdaftar.</p>
        </div>
        <span class="status success">Grup Aktif</span>
    </div>

    <div class="stats-grid">
        <article class="stat-card">
            <div class="stat-top">Anggota <span class="stat-icon">♙</span></div>
            <div class="stat-value">32</div>
            <div class="stat-foot">Anggota terdaftar</div>
        </article>
        <article class="stat-card">
            <div class="stat-top">Kas Bulanan <span class="stat-icon green">Rp</span></div>
            <div class="stat-value">Rp 50.000</div>
            <div class="stat-foot">Per anggota</div>
        </article>
        <article class="stat-card">
            <div class="stat-top">Bendahara <span class="stat-icon purple">♙</span></div>
            <div class="stat-value">Rizky F.</div>
            <div class="stat-foot">Pengelola kas grup</div>
        </article>
        <article class="stat-card">
            <div class="stat-top">Saldo Grup <span class="stat-icon orange">▤</span></div>
            <div class="stat-value">Rp 1,25 jt</div>
            <div class="stat-foot">Saldo contoh</div>
        </article>
    </div>

    <div class="panel-heading">
        <div>
            <h3>Pengurus Grup</h3>
            <p>Kontak pengurus dapat ditampilkan setelah data tersedia.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Peran</th>
                    <th>Kontak</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="person-cell">
                            <span class="person-avatar">RF</span>
                            <span>
                                <strong>Rizky Firmansyah</strong>
                                <small>rizky25</small>
                            </span>
                        </div>
                    </td>
                    <td>Bendahara</td>
                    <td>rizky@example.com</td>
                </tr>
                <tr>
                    <td>
                        <div class="person-cell">
                            <span class="person-avatar">NA</span>
                            <span>
                                <strong>Nadia Anindya</strong>
                                <small>nadia25</small>
                            </span>
                        </div>
                    </td>
                    <td>Admin</td>
                    <td>nadia@example.com</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
HTML;

require '../../components/layout.php';