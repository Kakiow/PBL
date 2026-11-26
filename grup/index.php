<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Grup';
$page_css = ['grup.css'];
$page_js = ['grup-dialog.js', 'grup.js'];
$id_user = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? '')) {
        flash('error', 'Sesi formulir kedaluwarsa. Silakan coba lagi.');
        redirect('index.php');
    }

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

                $stmt = $pdo->prepare("INSERT INTO grup (nama_grup, kode_grup, deskripsi) VALUES (?, ?, ?) RETURNING id");
                $stmt->execute([$nama, $kode, $deskripsi]);
                $id_grup = $stmt->fetchColumn();
                $stmt = $pdo->prepare("INSERT INTO anggota_grup (id_grup, id_pengguna, peran) VALUES (?, ?, 'Admin')");
                $stmt->execute([$id_grup, $id_user]);
                $stmt = $pdo->prepare("INSERT INTO kategori (id_grup, nama_kategori, jenis) VALUES (?, 'Iuran Kas', 'Pemasukan'), (?, 'Lain-lain', 'Pemasukan'), (?, 'Kebutuhan Kelas', 'Pengeluaran')");
                $stmt->execute([$id_grup, $id_grup, $id_grup]);
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

$stmt = $pdo->prepare("
    SELECT g.*, ag.peran,
        (SELECT COUNT(*) FROM anggota_grup ag2 WHERE ag2.id_grup = g.id) AS jumlah_anggota
    FROM grup g
    JOIN anggota_grup ag ON ag.id_grup = g.id
    WHERE ag.id_pengguna = ?
    ORDER BY g.nama_grup
");
$stmt->execute([$id_user]);
$grup_saya = $stmt->fetchAll();

$grup_terpilih = grup_aktif($pdo);
$id_aktif = $grup_terpilih ? (int) $grup_terpilih['id'] : 0;

include __DIR__ . '/../includes/header.php';
?>
<header class="grup-page-head">
    <div>
        <h1>Grup</h1>
        <p>Pilih grup aktif untuk bekerja, salin kode undangan, atau buat grup kas baru.</p>
    </div>
    <?php if ($grup_saya): ?>
    <div class="grup-head-actions">
        <button type="button" class="btn secondary" data-buka-dialog="dialog-gabung-grup">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" aria-hidden="true">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                <path d="M10 17l5-5-5-5" />
                <path d="M15 12H3" />
            </svg>
            Gabung grup
        </button>
        <button type="button" class="btn primary" data-buka-dialog="dialog-buat-grup">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" aria-hidden="true">
                <path d="M12 5v14M5 12h14" />
            </svg>
            Buat grup
        </button>
    </div>
    <?php endif; ?>
</header>

<?php
include __DIR__ . '/_daftar_grup.php';
include __DIR__ . '/_form_grup.php';
include __DIR__ . '/../includes/footer.php';
