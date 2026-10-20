<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Grup';
$page_css = ['grup.css'];
$id_user = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'buat_grup') {
        $nama = trim($_POST['nama_grup'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        if ($nama === '') {
            flash('error', 'Nama grup wajib diisi.');
        } else {
            try {
                $pdo->beginTransaction();

                $kode = kode_grup_baru($pdo);

                if ($kode === null) {
                    throw new Exception('kode gagal dibuat');
                }

                $stmt = $pdo->prepare("INSERT INTO grup (nama_grup, kode_grup, deskripsi, dibuat_oleh) VALUES (?, ?, ?, ?) RETURNING id");
                $stmt->execute([$nama, $kode, $deskripsi, $id_user]);
                $id_grup = $stmt->fetchColumn();
                $stmt = $pdo->prepare("INSERT INTO anggota_grup (id_grup, id_pengguna, peran) VALUES (?, ?, 'Admin')");
                $stmt->execute([$id_grup, $id_user]);
                $stmt = $pdo->prepare("INSERT INTO kategori (id_grup, nama_kategori, jenis) VALUES (?, 'Iuran Kas', 'Pemasukan'), (?, 'Lain-lain', 'Pemasukan'), (?, 'Kebutuhan Kelas', 'Pengeluaran')");
                $stmt->execute([$id_grup, $id_grup, $id_grup]);
                $stmt = $pdo->prepare("INSERT INTO akun_dana (id_grup, nama_akun, jenis_akun) VALUES (?, 'Kas Tunai', 'Kas_Tunai')");
                $stmt->execute([$id_grup]);
                $pdo->commit();
                $_SESSION['grup_id'] = $id_grup;
                flash('success', 'Grup berhasil dibuat. Kode grup: ' . $kode);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                flash('error', 'Gagal membuat grup. Silakan coba lagi.');
            }
        }
        redirect('index.php');
    }

    if ($aksi === 'gabung_grup') {
        $kode = trim($_POST['kode_grup'] ?? '');
        $stmt = $pdo->prepare("SELECT id FROM grup WHERE kode_grup = ?");
        $stmt->execute([$kode]);
        $id_grup = $stmt->fetchColumn();
        if (!$id_grup) {
            flash('error', 'Kode grup tidak ditemukan.');
        } else {
            $stmt = $pdo->prepare("SELECT id FROM anggota_grup WHERE id_grup = ? AND id_pengguna = ?");
            $stmt->execute([$id_grup, $id_user]);
            if ($stmt->fetch()) {
                flash('error', 'Anda sudah menjadi anggota grup tersebut.');
            } else {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("INSERT INTO anggota_grup (id_grup, id_pengguna, peran) VALUES (?, ?, 'Anggota')");
                    $stmt->execute([$id_grup, $id_user]);
                    $jumlah_tagihan = tagihkan_tagihan_berjalan($pdo, $id_grup, $id_user);
                    $pdo->commit();
                    $_SESSION['grup_id'] = $id_grup;
                    flash('success', $jumlah_tagihan > 0
                        ? 'Berhasil bergabung dengan grup. ' . $jumlah_tagihan . ' tagihan yang masih berjalan ditambahkan ke daftar tagihanmu.'
                        : 'Berhasil bergabung dengan grup.');
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    flash('error', 'Gagal bergabung dengan grup. Silakan coba lagi.');
                }
            }
        }
        redirect('index.php');
    }

    if ($aksi === 'pilih_grup') {
        $id_grup = (int) ($_POST['id_grup'] ?? 0);
        if (role_group($pdo, $id_grup, $id_user)) {
            $_SESSION['grup_id'] = $id_grup;
            flash('success', 'Grup aktif berhasil diubah.');
        }
        redirect('index.php');
    }
}

$stmt = $pdo->prepare("SELECT g.*, ag.peran FROM grup g JOIN anggota_grup ag ON ag.id_grup = g.id WHERE ag.id_pengguna = ? ORDER BY g.nama_grup");
$stmt->execute([$id_user]);
$grup_saya = $stmt->fetchAll();

$grup_terpilih = grup_aktif($pdo);
$id_aktif = $grup_terpilih ? (int) $grup_terpilih['id'] : 0;

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <h1>Grup kas</h1>
    <p>Pilih grup yang ingin dikelola, buat grup baru, atau gabung memakai kode.</p>
</div>

<?php
// Sudah punya grup: daftar grup lebih dulu. Belum: form buat/gabung lebih dulu.
if ($grup_saya) {
    include __DIR__ . '/_daftar_grup.php';
    include __DIR__ . '/_form_grup.php';
} else {
    include __DIR__ . '/_form_grup.php';
    include __DIR__ . '/_daftar_grup.php';
}
include __DIR__ . '/../includes/footer.php';
