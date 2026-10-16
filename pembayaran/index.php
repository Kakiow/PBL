<?php

session_start();

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Pembayaran';

$id_grup = (int) ($_SESSION['grup_id'] ?? 0);

$role = cek_role(
    $pdo,
    $id_grup,
    ['Admin', 'Bendahara', 'Anggota']
);

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    in_array($role, ['Admin', 'Bendahara'])
) {

    $id = (int) ($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if (in_array($status, ['Disetujui', 'Ditolak'])) {

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT
                    p.*,
                    ak.id_kewajiban,
                    ak.id_pengguna
                FROM pembayaran p
                JOIN anggota_kewajiban ak
                    ON ak.id = p.id_anggota_kewajiban
                JOIN kewajiban_pembayaran k
                    ON k.id = ak.id_kewajiban
                WHERE p.id = ?
                AND k.id_grup = ?
                FOR UPDATE
            ");

            $stmt->execute([
                $id,
                $id_grup
            ]);

            $bayar = $stmt->fetch();

            if (!$bayar) {
                throw new Exception(
                    'Pembayaran tidak ditemukan.'
                );
            }

            $stmt = $pdo->prepare("
                UPDATE pembayaran
                SET
                    status = ?,
                    diverifikasi_oleh = ?,
                    diverifikasi_pada = CURRENT_TIMESTAMP
                WHERE id = ?
            ");

            $stmt->execute([
                $status,
                $_SESSION['user']['id'],
                $id
            ]);

            $stmt = $pdo->prepare("
                UPDATE anggota_kewajiban
                SET status = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $status === 'Disetujui'
                    ? 'Lunas'
                    : 'Ditolak',
                $bayar['id_anggota_kewajiban']
            ]);

            if ($status === 'Disetujui') {

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM kategori
                    WHERE id_grup = ?
                    AND jenis = 'Pemasukan'
                    ORDER BY id
                    LIMIT 1
                ");

                $stmt->execute([$id_grup]);

                $kategori = $stmt->fetchColumn();

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM akun_dana
                    WHERE id_grup = ?
                    ORDER BY id
                    LIMIT 1
                ");

                $stmt->execute([$id_grup]);

                $akun = $stmt->fetchColumn();

                if (!$kategori || !$akun) {
                    throw new Exception(
                        'Kategori pemasukan dan akun dana harus tersedia.'
                    );
                }

                $stmt = $pdo->prepare("
                    INSERT INTO transaksi
                    (
                        id_grup,
                        id_pengguna,
                        id_kategori,
                        id_akun_dana,
                        id_pembayaran,
                        jenis,
                        jumlah,
                        tanggal_transaksi,
                        deskripsi
                    )
                    VALUES
                    (
                        ?, ?, ?, ?, ?,
                        'Pemasukan',
                        ?,
                        CURRENT_DATE,
                        ?
                    )
                ");

                $stmt->execute([
                    $id_grup,
                    $_SESSION['user']['id'],
                    $kategori,
                    $akun,
                    $id,
                    $bayar['jumlah'],
                    'Pembayaran kewajiban'
                ]);
            }

            $pdo->commit();

            flash(
                'success',
                'Pembayaran berhasil diverifikasi.'
            );

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            flash(
                'error',
                $e->getMessage()
            );
        }
    }

    redirect('index.php');
}

$sql = "
    SELECT
        p.*,
        u.nama,
        k.judul
    FROM pembayaran p
    JOIN anggota_kewajiban ak
        ON ak.id = p.id_anggota_kewajiban
    JOIN pengguna u
        ON u.id = ak.id_pengguna
    JOIN kewajiban_pembayaran k
        ON k.id = ak.id_kewajiban
    WHERE k.id_grup = ?
    ORDER BY p.id DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    $id_grup
]);

$data = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';

?>

<section>

    <div class="section-heading">

        <div>

            <h1>Pembayaran</h1>

            <p class="muted">
                Pantau dan verifikasi pembayaran anggota.
            </p>

        </div>

    </div>

</section>

<section>

    <div class="table-responsive">

        <table>

            <thead>

                <tr>
                    <th>Anggota</th>
                    <th>Kewajiban</th>
                    <th>Jumlah</th>
                    <th>Metode</th>
                    <th>Bukti</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>

            </thead>

            <tbody>

                <?php if (!$data): ?>

                <tr>
                    <td colspan="7">
                        Belum ada pembayaran.
                    </td>
                </tr>

                <?php endif; ?>

                <?php foreach ($data as $row): ?>

                <tr>

                    <td>
                        <?php echo e($row['nama']); ?>
                    </td>

                    <td>
                        <?php echo e($row['judul']); ?>
                    </td>

                    <td>
                        <?php echo rupiah($row['jumlah']); ?>
                    </td>

                    <td>
                        <?php echo e($row['metode_pembayaran']); ?>
                    </td>

                    <td>

                        <?php if (!empty($row['berkas_bukti'])): ?>

                        <a href="../storage/uploads/<?php echo e($row['berkas_bukti']); ?>" target="_blank">
                            Lihat Bukti
                        </a>

                        <?php else: ?>

                        -

                        <?php endif; ?>

                    </td>

                    <td>

                        <span class="badge">
                            <?php echo e($row['status']); ?>
                        </span>

                    </td>

                    <td>

                        <?php if (
                            in_array($role, ['Admin', 'Bendahara']) &&
                            $row['status'] === 'Menunggu'
                        ): ?>

                        <form method="post" class="inline-form">

                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

                            <button name="status" value="Disetujui" class="btn success">
                                Setujui
                            </button>

                            <button name="status" value="Ditolak" class="btn danger">
                                Tolak
                            </button>

                        </form>

                        <?php else: ?>

                        -

                        <?php endif; ?>

                    </td>

                </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>