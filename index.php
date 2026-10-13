
<?php
// Prototype frontend KasKelas. Autentikasi backend belum dihubungkan.
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk | KasKelas</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-body">
    <main class="login-shell">
        <section class="login-intro">
            <div class="brand brand-light"><span>KasKelas</span></div>
            <div class="intro-copy">
                <h1>Titik awal alur untuk Admin, Bendahara, dan Anggota.</h1>
                <div class="login-role-description">
                    <p>Admin: mulai dari Dashboard, lalu atur Grup, Anggota, Role, Kategori, dan Pengeluaran.</p>
                    <p>Bendahara: pilih grup aktif, verifikasi pembayaran, catat pengeluaran, lalu lihat laporan.</p>
                    <p>Anggota: lihat informasi grup, bayar tagihan, cek status, dan unduh bukti pembayaran.</p>
                </div>
            </div>
        </section>

        <section class="login-panel">
            <div class="login-content">
                <div class="mobile-brand brand"><span>KasKelas</span></div>
                <h2>Masuk</h2>
                <p class="muted">Gunakan akun yang terdaftar pada sistem.</p>

                <form id="login-form">
                    <label for="username">Email / Username</label>
                    <input id="username" name="username" type="text" placeholder="Email atau username"
                        autocomplete="username" required>

                    <label for="password">Password</label>
                    <div class="password-wrap">
                        <input id="password" name="password" type="password" placeholder="Password"
                            autocomplete="current-password" required>
                        <button class="show-password" type="button" id="toggle-password"
                            aria-label="Tampilkan password">Lihat</button>
                    </div>

                    <button class="btn btn-primary btn-full login-submit" type="submit">Login</button>
                </form>

                <p class="login-validation-note">Validasi kredensial → role &amp; membership grup → dashboard.</p>

                <div class="demo-area">
                    <div class="demo-heading"><strong>Demo Role</strong></div>
                    <p class="muted small">Pilih role untuk langsung melihat alur prototype.</p>
                    <div class="demo-roles" role="group" aria-label="Pilih role demo">
                        <button type="button" class="role-button" data-role="admin">Admin / Pengelola</button>
                        <button type="button" class="role-button" data-role="bendahara">Bendahara</button>
                        <button type="button" class="role-button" data-role="anggota">Anggota</button>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <script src="assets/js/script.js"></script>
</body>

</html>