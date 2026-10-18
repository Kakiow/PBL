<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Grup';
$page_css = ['grup.css'];
$id_user = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'buat_grup') {
        $nama = trim($_POST['nama_grup'] ?? '');
        $kode = trim($_POST['kode_grup'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        if ($nama === '' || $kode === '') {
            flash('error', 'Nama grup dan kode grup wajib diisi.');
        } else {
            try {
                $pdo->beginTransaction();
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
                flash('success', 'Grup berhasil dibuat.');
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                flash('error', 'Gagal membuat grup. Pastikan kode grup belum digunakan.');
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
                $stmt = $pdo->prepare("INSERT INTO anggota_grup (id_grup, id_pengguna, peran) VALUES (?, ?, 'Anggota')");
                $stmt->execute([$id_grup, $id_user]);
                $_SESSION['grup_id'] = $id_grup;
                flash('success', 'Berhasil bergabung dengan grup.');
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
    <span class="eyebrow">Grup</span>
    <h1>Grup Kas</h1>
    <p>Buat grup baru, gabung memakai kode, atau pilih grup yang ingin dikelola.</p>
</div>

<div class="grup-grid">
    <div class="form-card form-card--create">
        <h2>
            <span class="card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M12 5v14M5 12h14" />
                </svg>
            </span>
            Buat Grup Baru
        </h2>
        <p class="form-hint">Belum punya grup? Buat grup baru di sini — kamu otomatis menjadi Admin.</p>
        <form method="post">
            <input type="hidden" name="aksi" value="buat_grup">
            <p><label>Nama Grup</label><input type="text" name="nama_grup" required></p>
            <p><label>Kode Grup</label><input type="text" name="kode_grup" required placeholder="Contoh: KELAS2G25"></p>
            <p><label>Deskripsi</label><textarea name="deskripsi" rows="3"></textarea></p>
            <button class="btn primary full" type="submit">Buat Grup</button>
        </form>
    </div>
    <div class="form-card form-card--join">
        <h2>
            <span class="card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                    <path d="M10 17l5-5-5-5" />
                    <path d="M15 12H3" />
                </svg>
            </span>
            Gabung Grup
        </h2>
        <p class="form-hint">Punya kode dari teman? Masukkan kodenya untuk bergabung ke grup.</p>
        <form method="post">
            <input type="hidden" name="aksi" value="gabung_grup">
            <p><label>Kode Grup</label><input type="text" name="kode_grup" placeholder="Masukkan kode grup" required>
            </p>
            <button class="btn success full" type="submit">Gabung</button>
        </form>
    </div>
</div>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Grup Saya</h2>
            <p><?php echo $grup_saya ? 'Klik kartu grup untuk menjadikannya grup aktif.' : 'Belum memiliki grup.'; ?></p>
        </div>
    </div>
    <div class="grup-list">
        <?php foreach ($grup_saya as $g): $aktif = $id_aktif === (int) $g['id']; ?>
        <form method="post" class="group-item<?php echo $aktif ? ' active' : ''; ?>">
            <input type="hidden" name="aksi" value="pilih_grup">
            <input type="hidden" name="id_grup" value="<?php echo (int) $g['id']; ?>">
            <button type="submit" class="group-item-btn"
                title="<?php echo $aktif ? 'Grup ini sedang aktif' : 'Klik kartu untuk memilih grup ini'; ?>"<?php echo $aktif ? ' disabled aria-current="true"' : ''; ?>>
                <strong><?php echo e($g['nama_grup']); ?></strong>
                <span><?php echo e($g['kode_grup']); ?> · <?php echo e($g['peran']); ?><?php echo $aktif ? ' · Aktif' : ''; ?></span>
            </button>
        </form>
        <?php endforeach; ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
