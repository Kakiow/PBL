<?php
$role = 'admin';
$active = 'anggota';
$pageTitle = 'Kelola Anggota';
$pageDescription = 'Kelola akun, grup angkatan, dan peran pengguna.';
$content = <<<'HTML'
<section class="content-card">
    <div class="content-card-header">
        <div>
            <h3>Daftar Anggota</h3>
            <p>Data pengguna dari seluruh grup.</p>
        </div>
        <button class="btn btn-primary" data-open-modal="member-modal">＋ Tambah Anggota</button>
    </div>

    <div class="table-tools">
        <input class="table-search" data-filter-table="#members-table" placeholder="Cari nama / username...">
        <select>
            <option>Semua Grup</option>
            <option>Angkatan 2025</option>
            <option>Angkatan 2024</option>
        </select>
        <select>
            <option>Semua Role</option>
            <option>Admin</option>
            <option>Bendahara</option>
            <option>Anggota</option>
        </select>
    </div>

    <div class="table-wrap">
        <table id="members-table">
            <thead>
                <tr>
                    <th>Nama Anggota</th>
                    <th>Username</th>
                    <th>Grup</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="person-cell">
                            <span class="person-avatar">NA</span>
                            <span>
                                <strong>Nadia Anindya</strong>
                                <small>nadia@example.com</small>
                            </span>
                        </div>
                    </td>
                    <td>nadia25</td>
                    <td>Angkatan 2025</td>
                    <td>Admin</td>
                    <td><span class="status success">Aktif</span></td>
                    <td><button class="table-action" data-demo-action="Edit anggota belum terhubung.">Edit</button></td>
                </tr>
                <tr>
                    <td>
                        <div class="person-cell">
                            <span class="person-avatar">RF</span>
                            <span>
                                <strong>Rizky Firmansyah</strong>
                                <small>rizky@example.com</small>
                            </span>
                        </div>
                    </td>
                    <td>rizky25</td>
                    <td>Angkatan 2025</td>
                    <td>Bendahara</td>
                    <td><span class="status success">Aktif</span></td>
                    <td><button class="table-action" data-demo-action="Edit anggota belum terhubung.">Edit</button></td>
                </tr>
                <tr>
                    <td>
                        <div class="person-cell">
                            <span class="person-avatar">DP</span>
                            <span>
                                <strong>Dinda Putri</strong>
                                <small>dinda@example.com</small>
                            </span>
                        </div>
                    </td>
                    <td>dinda25</td>
                    <td>Angkatan 2025</td>
                    <td>Anggota</td>
                    <td><span class="status success">Aktif</span></td>
                    <td><button class="table-action" data-demo-action="Edit anggota belum terhubung.">Edit</button></td>
                </tr>
                <tr>
                    <td>
                        <div class="person-cell">
                            <span class="person-avatar">AF</span>
                            <span>
                                <strong>Ahmad Fauzi</strong>
                                <small>ahmad@example.com</small>
                            </span>
                        </div>
                    </td>
                    <td>ahmad24</td>
                    <td>Angkatan 2024</td>
                    <td>Anggota</td>
                    <td><span class="status neutral">Nonaktif</span></td>
                    <td><button class="table-action" data-demo-action="Edit anggota belum terhubung.">Edit</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<div class="modal-backdrop" id="member-modal" hidden>
    <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="member-modal-title">
        <div class="modal-heading">
            <div>
                <h3 id="member-modal-title">Tambah Anggota</h3>
                <p>Lengkapi informasi akun anggota.</p>
            </div>
            <button type="button" class="modal-close" data-close-modal aria-label="Tutup">×</button>
        </div>

        <form class="demo-form">
            <div class="form-grid">
                <div class="field">
                    <label>Nama Lengkap</label>
                    <input required placeholder="Nama anggota">
                </div>
                <div class="field">
                    <label>Username</label>
                    <input required placeholder="username">
                </div>
                <div class="field">
                    <label>Email</label>
                    <input type="email" placeholder="nama@email.com">
                </div>
                <div class="field">
                    <label>Grup Angkatan</label>
                    <select required>
                        <option value="">Pilih grup</option>
                        <option>Angkatan 2025</option>
                        <option>Angkatan 2024</option>
                        <option>Angkatan 2023</option>
                    </select>
                </div>
                <div class="field">
                    <label>Role</label>
                    <select required>
                        <option value="">Pilih role</option>
                        <option>Admin</option>
                        <option>Bendahara</option>
                        <option>Anggota</option>
                    </select>
                </div>
                <div class="field">
                    <label>Password</label>
                    <input type="password" required placeholder="Buat password">
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-outline" data-close-modal>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Anggota</button>
            </div>
        </form>
    </section>
</div>
HTML;

require '../../components/layout.php';