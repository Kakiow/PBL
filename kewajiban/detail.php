<?php

session_start();

require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Detail Kewajiban';

$id_grup = (int) ($_SESSION['grup_id'] ?? 0);

$role = cek_role(
    $pdo,
    $id_grup,
    ['Admin', 'Bendahara', 'Anggota']
);

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT *
    FROM kewajiban_pembayaran
    WHERE id = ? AND id_grup = ?
");

$stmt->execute([
    $id,
    $id_grup
]);

$k = $stmt->fetch();

if (!$k) {
    flash('error', 'Kewajiban tidak ditemukan.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'Anggota') {

    $metode = $_POST['metode_pembayaran'] ?? '';
    $jumlah = (float) ($_POST['jumlah'] ?? 0);

    $stmt = $pdo->prepare("
        SELECT id
        FROM anggota_kewajiban
        WHERE id_kewajiban = ? AND id_pengguna = ?
    ");

    $stmt->execute([
        $id,
        $_SESSION['user']['id']
    ]);

    $ak = $stmt->fetch();

    if (!$ak) {

        flash(
            'error',
            'Data kewajiban anggota tidak ditemukan.'
        );

        redirect('detail.php?id=' . $id);
    }

    if ($jumlah <= 0) {

        flash(
            'error',
            'Jumlah pembayaran tidak valid.'
        );

        redirect('detail.php?id=' . $id);
    }

    if (!in_array($metode, ['Tunai', 'Transfer', 'E-Wallet', 'QRIS'])) {

        flash(
            'error',
            'Metode pembayaran tidak valid.'
        );

        redirect('detail.php?id=' . $id);
    }

    if (
        !isset($_FILES['berkas_bukti']) ||
        $_FILES['berkas_bukti']['error'] !== UPLOAD_ERR_OK
    ) {

        flash(
            'error',
            'Bukti pembayaran belum dipilih.'
        );

        redirect('detail.php?id=' . $id);
    }

    $nama_file = basename(
        $_FILES['berkas_bukti']['name']
    );

    $folder = __DIR__ . '/../storage/uploads/';

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $nama_file = time() . '_' . $nama_file;

    $tujuan = $folder . $nama_file;

    if (
        !move_uploaded_file(
            $_FILES['berkas_bukti']['tmp_name'],
            $tujuan
        )
    ) {

        flash(
            'error',
            'Bukti pembayaran gagal diupload.'
        );

        redirect('detail.php?id=' . $id);
    }

    $stmt = $pdo->prepare("
        INSERT INTO pembayaran
        (
            id_anggota_kewajiban,
            jumlah,
            metode_pembayaran,
            berkas_bukti
        )
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $ak['id'],
        $jumlah,
        $metode,
        $nama_file
    ]);

    $stmt = $pdo->prepare("
        UPDATE anggota_kewajiban
        SET status = 'Menunggu'
        WHERE id = ?
    ");

    $stmt->execute([
        $ak['id']
    ]);

    flash(
        'success',
        'Pembayaran berhasil dikirim.'
    );

    redirect('detail.php?id=' . $id);
}

$stmt = $pdo->prepare("
    SELECT
        ak.*,
        p.nama,
        p.email
    FROM anggota_kewajiban ak
    JOIN pengguna p
        ON p.id = ak.id_pengguna
    WHERE ak.id_kewajiban = ?
    ORDER BY p.nama
");

$stmt->execute([
    $id
]);

$anggota = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';

?>

<section>

    <a href="index.php">← Kembali</a>

    <h1>
        <?php echo e($k['judul']); ?>
    </h1>

    <p>
        <?php echo e($k['deskripsi']); ?>
    </p>

    <div class="stats">

        <article class="stat-card">

            <span>Jumlah</span>

            <strong>
                <?php echo rupiah($k['jumlah']); ?>
            </strong>

        </article>

        <article class="stat-card">

            <span>Tenggat</span>

            <strong>
                <?php echo e($k['tenggat_waktu']); ?>
            </strong>

        </article>

    </div>

</section>

<?php if ($role === 'Anggota'): ?>

<section class="form-card">

    <h2>Kirim Pembayaran</h2>

    <form method="post" enctype="multipart/form-data">

        <div class="form-grid">

            <p>

                <label>Jumlah</label>

                <input type="number" name="jumlah" min="1" value="<?php echo e($k['jumlah']); ?>" required>

            </p>

            <p>

                <label>Metode</label>

                <select name="metode_pembayaran" required>

                    <option value="">
                        Pilih
                    </option>

                    <option value="Tunai">
                        Tunai
                    </option>

                    <option value="Transfer">
                        Transfer
                    </option>

                    <option value="E-Wallet">
                        E-Wallet
                    </option>

                    <option value="QRIS">
                        QRIS
                    </option>

                </select>

            </p>

        </div>

        <p>

            <label>Bukti Pembayaran</label>

            <input type="file" name="berkas_bukti" accept=".jpg,.jpeg,.png,.pdf" required>

        </p>

        <button class="btn primary" type="submit">
            Kirim Pembayaran
        </button>

    </form>

</section>

<?php endif; ?>

<section>

    <h2>Status Anggota</h2>

    <div class="table-responsive">

        <table>

            <thead>

                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Terakhir Bayar</th>
                </tr>

            </thead>

            <tbody>

                <?php if (!$anggota): ?>

                <tr>

                    <td colspan="4">
                        Belum ada anggota.
                    </td>

                </tr>

                <?php endif; ?>

                <?php foreach ($anggota as $a): ?>

                <tr>

                    <td>
                        <?php echo e($a['nama']); ?>
                    </td>

                    <td>
                        <?php echo e($a['email']); ?>
                    </td>

                    <td>

                        <span class="badge">
                            <?php echo e($a['status']); ?>
                        </span>

                    </td>

                    <td>

                        <?php

                        $s = $pdo->prepare("
                            SELECT
                                tanggal_pembayaran,
                                status
                            FROM pembayaran
                            WHERE id_anggota_kewajiban = ?
                            ORDER BY id DESC
                            LIMIT 1
                        ");

                    $s->execute([
                        $a['id']
                    ]);

                    $bayar = $s->fetch();

                    if ($bayar) {

                        echo e(
                            $bayar['tanggal_pembayaran']
                        );

                        echo ' (';

                        echo e(
                            $bayar['status']
                        );

                        echo ')';

                    } else {

                        echo '-';

                    }

                    ?>

                    </td>

                </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>