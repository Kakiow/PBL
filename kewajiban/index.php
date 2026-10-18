<?php
session_start();
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
$page_title = 'Tagihan';
$page_css = ['kewajiban.css', 'table.css'];
$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
$id_user = (int) $_SESSION['user']['id'];
$role = cek_role($pdo, $id_grup, akses_menu()['kewajiban']);
$boleh_buat = in_array($role, ['Admin', 'Bendahara'], true);

$stmt = $pdo->prepare("
    SELECT k.*,
        (SELECT COUNT(*) FROM anggota_kewajiban ak WHERE ak.id_kewajiban = k.id AND ak.status = 'Lunas') AS lunas,
        (SELECT COUNT(*) FROM anggota_kewajiban ak WHERE ak.id_kewajiban = k.id) AS total,
        saya.status AS status_saya
    FROM kewajiban_pembayaran k
    LEFT JOIN anggota_kewajiban saya ON saya.id_kewajiban = k.id AND saya.id_pengguna = ?
    WHERE k.id_grup = ?
    ORDER BY k.tenggat_waktu DESC
");
$stmt->execute([$id_user, $id_grup]);
$data = $stmt->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <span class="eyebrow">Tagihan</span>
    <h1>Tagihan Kas</h1>
    <p>Pantau daftar tagihan kas dan status pembayaran anggota.</p>
</div>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Daftar Tagihan</h2>
            <p>Seluruh tagihan yang pernah diterbitkan pada grup ini.</p>
        </div>
        <?php if ($boleh_buat): ?>
        <a class="btn primary" href="tambah.php">+ Buat Tagihan</a>
        <?php endif; ?>
    </div>
    <div class="table-fancy-wrap">
        <table class="table-fancy">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th class="num">Jumlah</th>
                    <th>Tenggat</th>
                    <th>Status</th>
                    <th class="num">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$data): ?><tr class="empty">
                    <td colspan="5">Belum ada tagihan.</td>
                </tr><?php endif; ?>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td><?php echo e($row['judul']); ?></td>
                    <td class="num"><?php echo rupiah($row['jumlah']); ?></td>
                    <td class="nowrap"><?php echo e($row['tenggat_waktu']); ?></td>
                    <td><span class="chip"><?php echo $row['lunas'] . ' / ' . $row['total']; ?> lunas</span></td>
                    <td>
                        <div class="row-actions">
                            <?php if ($row['status_saya'] === null): ?>
                            <span class="muted">—</span>
                            <?php elseif ($row['status_saya'] === 'Lunas'): ?>
                            <button class="btn success small" type="button" disabled>Lunas</button>
                            <?php elseif ($row['status_saya'] === 'Menunggu'): ?>
                            <button class="btn secondary small" type="button" disabled>Menunggu verifikasi</button>
                            <?php else: ?>
                            <a class="btn primary small" href="bayar.php?id=<?php echo (int) $row['id']; ?>">Bayar</a>
                            <?php endif; ?>
                            <a class="btn secondary small" href="detail.php?id=<?php echo (int) $row['id']; ?>">Detail</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
