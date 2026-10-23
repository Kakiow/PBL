<?php
require_once __DIR__ . '/../includes/auth.php';
$page_title = 'Tagihan';
$page_css = ['tagihan.css', 'table.css'];
$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
$id_user = (int) $_SESSION['user']['id'];
$role = cek_role($pdo, $id_grup, akses_menu()['tagihan']);
$boleh_buat = peran_pengelola($role);

// Satu kali scan tagihan_anggota per tagihan: hitung lunas/total + ambil status milik saya.
$stmt = $pdo->prepare("
    SELECT k.*,
        COUNT(ak.id) FILTER (WHERE ak.status = 'Lunas') AS lunas,
        COUNT(ak.id) AS total,
        MAX(ak.status::text) FILTER (WHERE ak.id_pengguna = ?) AS status_saya
    FROM tagihan k
    LEFT JOIN tagihan_anggota ak ON ak.id_tagihan = k.id
    WHERE k.id_grup = ?
    GROUP BY k.id
    ORDER BY k.tenggat_waktu DESC
");
$stmt->execute([$id_user, $id_grup]);
$data = $stmt->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <h1>Tagihan kas</h1>
    <p>Pantau daftar tagihan kas dan status pembayaran anggota.</p>
</div>

<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Daftar tagihan</h2>
            <p>Seluruh tagihan yang pernah diterbitkan pada grup ini.</p>
        </div>
        <?php if ($boleh_buat): ?>
        <a class="btn primary" href="tambah.php">Buat tagihan</a>
        <?php endif; ?>
    </div>
    <div class="table-fancy-wrap">
        <table class="table-fancy table-stack">
            <thead>
                <tr>
                    <th scope="col">Tagihan</th>
                    <th scope="col" class="num">Jumlah</th>
                    <th scope="col">Tenggat</th>
                    <th scope="col">Progres</th>
                    <th scope="col">Status saya</th>
                    <th scope="col" class="num"><span class="sr-only">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$data): ?><tr class="empty">
                    <td colspan="6"><?php echo $boleh_buat ? 'Belum ada tagihan. Buat tagihan pertama untuk mulai menagih anggota.' : 'Belum ada tagihan di grup ini.'; ?></td>
                </tr><?php endif; ?>
                <?php foreach ($data as $row):
                    $tenggat = info_tenggat($row['tenggat_waktu']);
                    $persen = $row['total'] > 0 ? round($row['lunas'] / $row['total'] * 100) : 0;
                    $bisa_bayar = in_array($row['status_saya'], ['Belum_Bayar', 'Ditolak'], true);
                ?>
                <tr>
                    <td class="cell-title"><a href="detail.php?id=<?php echo (int) $row['id']; ?>"><?php echo e($row['judul']); ?></a></td>
                    <td class="num" data-label="Jumlah"><?php echo rupiah($row['jumlah']); ?></td>
                    <td class="nowrap" data-label="Tenggat"><span class="cell-value">
                        <?php echo e(tanggal_id($row['tenggat_waktu'])); ?>
                        <?php if ($row['status_saya'] !== 'Lunas' && ($tenggat['lewat'] || $bisa_bayar)): ?>
                        <span class="cell-sub<?php echo $tenggat['lewat'] ? ' is-late' : ''; ?>"><?php echo e($tenggat['teks']); ?></span>
                        <?php endif; ?>
                    </span></td>
                    <td data-label="Progres">
                        <span class="meter">
                            <span class="meter-text"><?php echo (int) $row['lunas'] . ' dari ' . (int) $row['total']; ?> lunas</span>
                            <span class="meter-bar" aria-hidden="true"><span style="width: <?php echo $persen; ?>%"></span></span>
                        </span>
                    </td>
                    <td data-label="Status saya">
                        <?php if ($row['status_saya'] === null): ?>
                        <span class="muted">Tidak ditagih</span>
                        <?php else: ?>
                        <span class="badge <?php echo kelas_status($row['status_saya']); ?>"><?php echo e(label_status($row['status_saya'])); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="cell-actions">
                        <div class="row-actions">
                            <?php if ($bisa_bayar): ?>
                            <a class="btn primary small" href="bayar.php?id=<?php echo (int) $row['id']; ?>"><?php echo $row['status_saya'] === 'Ditolak' ? 'Bayar ulang' : 'Bayar'; ?></a>
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
