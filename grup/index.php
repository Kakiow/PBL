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
        <p class="form-hint">Belum punya grup? Buat grup baru di sini — kamu otomatis menjadi Admin. Kode grup dibuat
            otomatis, lalu bisa kamu bagikan ke anggota dari halaman Detail grup.</p>
        <form method="post">
            <input type="hidden" name="aksi" value="buat_grup">
            <p><label>Nama Grup</label><input type="text" name="nama_grup" required></p>
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
            <p><?php echo $grup_saya
                    ? 'Klik badan kartu untuk menjadikannya grup aktif, atau buka Detail untuk mengelola grup.'
                    : 'Belum memiliki grup.'; ?></p>
        </div>
    </div>
    <div class="grup-list">
        <?php foreach ($grup_saya as $g): $aktif = $id_aktif === (int) $g['id']; ?>
        <div class="group-item<?php echo $aktif ? ' active' : ''; ?>">
            <form method="post" class="group-item-form">
                <input type="hidden" name="aksi" value="pilih_grup">
                <input type="hidden" name="id_grup" value="<?php echo (int) $g['id']; ?>">
                <button type="submit" class="group-item-btn"
                    title="<?php echo $aktif ? 'Grup ini sedang aktif' : 'Klik untuk menjadikan grup ini aktif'; ?>"<?php echo $aktif ? ' disabled aria-current="true"' : ''; ?>>
                    <strong><?php echo e($g['nama_grup']); ?></strong>
                    <span class="group-item-meta"><?php echo e($g['kode_grup']); ?> &middot;
                        <?php echo e($g['peran']); ?></span>
                </button>
            </form>
            <div class="group-item-foot">
                <button type="button" class="btn secondary small" data-copy="<?php echo e($g['kode_grup']); ?>"
                    title="Salin kode grup <?php echo e($g['kode_grup']); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" aria-hidden="true">
                        <rect x="9" y="9" width="11" height="11" rx="2" />
                        <path d="M5 15V5a2 2 0 0 1 2-2h10" />
                    </svg>
                    Salin kode
                </button>
                <a class="btn secondary small" href="detail.php?id=<?php echo (int) $g['id']; ?>">Detail</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
