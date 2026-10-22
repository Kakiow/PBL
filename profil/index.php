<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Profil';
$page_css = ['profil.css'];

$id_user = (int) $_SESSION['user']['id'];

$stmt = $pdo->prepare("SELECT id, nama, email, apakah_aktif, dibuat_pada FROM pengguna WHERE id = ?");
$stmt->execute([$id_user]);
$akun = $stmt->fetch();

// Sesi menunjuk akun yang sudah tidak ada: bersihkan dan minta masuk lagi.
if (!$akun) {
    session_unset();
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

// Kembali ke profil berarti sesi edit ditutup; edit berikutnya minta password lagi.
unset($_SESSION['profil_edit_ok']);

$stmt = $pdo->prepare("
    SELECT g.id, g.nama_grup, g.kode_grup, ag.peran, ag.bergabung_pada
    FROM anggota_grup ag
    JOIN grup g ON g.id = ag.id_grup
    WHERE ag.id_pengguna = ?
    ORDER BY ag.bergabung_pada, g.nama_grup
");
$stmt->execute([$id_user]);
$grup_saya = $stmt->fetchAll();

$grup_aktif = grup_aktif($pdo);
$id_grup_aktif = $grup_aktif ? (int) $grup_aktif['id'] : 0;

$nama_user = (string) $akun['nama'];
$inisial = strtoupper(function_exists('mb_substr') ? mb_substr($nama_user, 0, 1) : substr($nama_user, 0, 1));
$aktif = in_array($akun['apakah_aktif'], [true, 't', '1', 1], true);

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <h1>Profil</h1>
    <p>Data akun dan grup yang kamu ikuti di SIMKAS.</p>
</div>

<section class="profile-hero" aria-labelledby="profil-nama">
    <span class="profile-avatar" aria-hidden="true"><?php echo e($inisial); ?></span>
    <div class="profile-ident">
        <h2 id="profil-nama"><?php echo e($akun['nama']); ?></h2>
        <p><?php echo e($akun['email']); ?></p>
        <div class="profile-tags">
            <span class="badge <?php echo $aktif ? 'success' : 'danger'; ?>">
                <?php echo $aktif ? 'Akun aktif' : 'Akun nonaktif'; ?>
            </span>
            <span class="chip"><?php echo count($grup_saya); ?> grup</span>
            <span class="chip">Bergabung <?php echo e(tanggal_id($akun['dibuat_pada'])); ?></span>
        </div>
    </div>
    <a class="btn primary profile-edit-btn" href="edit.php">Edit profil</a>
</section>

<section class="content-card" aria-labelledby="judul-grup-saya">
    <div class="card-head">
        <div>
            <h2 id="judul-grup-saya">Grup saya</h2>
            <p>Grup yang kamu ikuti beserta peranmu di dalamnya.</p>
        </div>
        <a class="text-link" href="../grup/index.php">Kelola grup →</a>
    </div>

    <?php if (!$grup_saya): ?>
    <p class="muted">Belum ada grup. <a href="../grup/index.php">Buat atau gabung grup</a> untuk mulai mencatat kas.</p>
    <?php else: ?>
    <ul class="profile-groups">
        <?php foreach ($grup_saya as $g): $aktif_grup = (int) $g['id'] === $id_grup_aktif; ?>
        <li>
            <div class="profile-group-main">
                <b><?php echo e($g['nama_grup']); ?></b>
                <span>Kode <?php echo e($g['kode_grup']); ?></span>
            </div>
            <span class="badge role-<?php echo strtolower($g['peran']); ?>"><?php echo e($g['peran']); ?></span>
            <span class="profile-group-date">Sejak <?php echo e(tanggal_id($g['bergabung_pada'])); ?></span>
            <?php if ($aktif_grup): ?>
            <span class="badge success">Grup aktif</span>
            <?php else: ?>
            <a class="text-link" href="../grup/index.php">Pilih</a>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
