<?php
require_once __DIR__ . '/../includes/auth.php';

$page_title = 'Verifikasi';
$page_css = ['table.css', 'verifikasi.css'];

$id_grup = (int) ($_SESSION['grup_id'] ?? 0);
$role = cek_role($pdo, $id_grup, akses_menu()['pembayaran']);
$boleh_verifikasi = peran_pengelola($role);

// Pastikan bukti kedaluwarsa tidak tampil sebagai link yang sudah rusak.
bersihkan_bukti_otomatis($pdo);

$tab = ($_GET['tab'] ?? '') === 'riwayat' ? 'riwayat' : 'menunggu';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $boleh_verifikasi) {
    $id = (int) ($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $catatan = trim((string) ($_POST['catatan'] ?? ''));
    $catatan = function_exists('mb_substr') ? mb_substr($catatan, 0, 200) : substr($catatan, 0, 200);

    if ($status === 'Ditolak' && $catatan === '') {
        flash('error', 'Tulis alasan penolakan supaya anggota tahu apa yang harus diperbaiki.');
    } elseif (in_array($status, ['Disetujui', 'Ditolak'], true)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                SELECT p.*, ak.id_kewajiban, ak.id_pengguna, ak.status AS status_kewajiban, k.judul, u.nama
                FROM pembayaran p
                JOIN anggota_kewajiban ak ON ak.id = p.id_anggota_kewajiban
                JOIN kewajiban_pembayaran k ON k.id = ak.id_kewajiban
                JOIN pengguna u ON u.id = ak.id_pengguna
                WHERE p.id = ? AND k.id_grup = ?
                FOR UPDATE OF p, ak
            ");
            $stmt->execute([$id, $id_grup]);
            $bayar = $stmt->fetch();

            if (!$bayar) {
                throw new Exception('Pembayaran tidak ditemukan.');
            }

            if ($bayar['status'] !== 'Menunggu') {
                throw new Exception('Pembayaran ini sudah diproses.');
            }

            if ($bayar['status_kewajiban'] === 'Lunas') {
                throw new Exception('Kewajiban ini sudah lunas.');
            }

            $stmt = $pdo->prepare("UPDATE pembayaran SET status = ?, diverifikasi_oleh = ?, diverifikasi_pada = CURRENT_TIMESTAMP, catatan = ? WHERE id = ?");
            $stmt->execute([$status, $_SESSION['user']['id'], $status === 'Ditolak' ? $catatan : null, $id]);

            $stmt = $pdo->prepare("UPDATE anggota_kewajiban SET status = ? WHERE id = ?");
            $stmt->execute([$status === 'Disetujui' ? 'Lunas' : 'Ditolak', $bayar['id_anggota_kewajiban']]);

            if ($status === 'Disetujui') {
                $stmt = $pdo->prepare("
                    SELECT
                        (SELECT id FROM kategori WHERE id_grup = ? AND jenis = 'Pemasukan' ORDER BY id LIMIT 1) AS kategori,
                        (SELECT id FROM akun_dana WHERE id_grup = ? ORDER BY id LIMIT 1) AS akun
                ");
                $stmt->execute([$id_grup, $id_grup]);
                $tujuan = $stmt->fetch();
                $kategori = $tujuan['kategori'];
                $akun = $tujuan['akun'];

                if (!$kategori || !$akun) {
                    throw new Exception('Kategori pemasukan dan sumber dana harus tersedia.');
                }

                $stmt = $pdo->prepare("SELECT 1 FROM transaksi WHERE id_pembayaran = ?");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn()) {
                    throw new Exception('Transaksi untuk pembayaran ini sudah tercatat.');
                }

                $stmt = $pdo->prepare("
                    INSERT INTO transaksi
                        (id_grup, id_pengguna, id_kategori, id_akun_dana, id_pembayaran, jenis, jumlah, tanggal_transaksi, deskripsi)
                    VALUES (?, ?, ?, ?, ?, 'Pemasukan', ?, CURRENT_DATE, ?)
                ");
                $stmt->execute([
                    $id_grup,
                    $_SESSION['user']['id'],
                    $kategori,
                    $akun,
                    $id,
                    $bayar['jumlah'],
                    'Iuran: ' . $bayar['judul'] . ' – ' . $bayar['nama'],
                ]);
            }

            $pdo->commit();
            flash('success', $status === 'Disetujui'
                ? 'Pembayaran ' . $bayar['nama'] . ' disetujui dan dicatat sebagai pemasukan ' . rupiah($bayar['jumlah']) . '.'
                : 'Pembayaran ' . $bayar['nama'] . ' ditolak. Alasannya ditampilkan ke anggota.');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('error', $e->getMessage());
        }
    }

    redirect('index.php?tab=' . $tab);
}

// Antrean: yang menunggu paling lama di atas. Riwayat: yang terbaru di atas.
$stmt = $pdo->prepare("
    SELECT p.*, u.nama, k.judul
    FROM pembayaran p
    JOIN anggota_kewajiban ak ON ak.id = p.id_anggota_kewajiban
    JOIN pengguna u ON u.id = ak.id_pengguna
    JOIN kewajiban_pembayaran k ON k.id = ak.id_kewajiban
    WHERE k.id_grup = ?
    ORDER BY p.id DESC
");
$stmt->execute([$id_grup]);
$menunggu = [];
$riwayat = [];
foreach ($stmt->fetchAll() as $row) {
    if ($row['status'] === 'Menunggu') {
        array_unshift($menunggu, $row);
    } else {
        $riwayat[] = $row;
    }
}
$data = $tab === 'menunggu' ? $menunggu : $riwayat;
$kolom_aksi = $tab === 'menunggu' && $boleh_verifikasi;

// Sel bukti: thumbnail gambar, ikon PDF, keterangan tunai, atau peringatan bila bukti non-tunai tidak ada.
// Di Riwayat, bukti yang sudah dibersihkan otomatis bukan masalah, jadi tidak diberi warna peringatan.
$tampil_bukti = function ($row) use ($tab) {
    if ($row['metode_pembayaran'] === 'Tunai') {
        return '<span class="muted">Tidak perlu</span>';
    }

    $nama = (string) $row['berkas_bukti'];
    if ($nama === '') {
        return $tab === 'menunggu'
            ? '<span class="proof-missing">Bukti tidak ada</span>'
            : '<span class="muted">Sudah dihapus</span>';
    }

    $url = '../storage/uploads/' . rawurlencode($nama);
    $pdf = strtolower(pathinfo($nama, PATHINFO_EXTENSION)) === 'pdf';
    $pratinjau = $pdf
        ? '<span class="proof-file" aria-hidden="true">PDF</span>'
        : '<img src="' . e($url) . '" alt="" width="44" height="44" loading="lazy">';

    return '<a class="proof" href="' . e($url) . '" target="_blank" rel="noopener">' . $pratinjau
        . '<span>Lihat bukti<span class="sr-only"> dari ' . e($row['nama']) . '</span></span></a>';
};

include __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
    <h1>Verifikasi pembayaran</h1>
    <p><?php echo $boleh_verifikasi
            ? 'Periksa bukti, lalu setujui atau tolak pembayaran yang dikirim anggota.'
            : 'Status pembayaran yang dikirim anggota grup.'; ?></p>
</div>

<section class="content-card">
    <nav class="tabs" aria-label="Saring pembayaran">
        <a href="index.php?tab=menunggu"<?php echo $tab === 'menunggu' ? ' aria-current="page"' : ''; ?>>Menunggu
            <span class="tab-count"><?php echo count($menunggu); ?></span></a>
        <a href="index.php?tab=riwayat"<?php echo $tab === 'riwayat' ? ' aria-current="page"' : ''; ?>>Riwayat
            <span class="tab-count"><?php echo count($riwayat); ?></span></a>
    </nav>
    <div class="table-fancy-wrap">
        <table class="table-fancy table-stack">
            <thead>
                <tr>
                    <th scope="col">Anggota</th>
                    <th scope="col">Tagihan</th>
                    <th scope="col" class="num">Jumlah</th>
                    <th scope="col">Metode</th>
                    <th scope="col">Dikirim</th>
                    <th scope="col">Bukti</th>
                    <?php if ($tab === 'riwayat'): ?><th scope="col">Status</th><?php endif; ?>
                    <?php if ($kolom_aksi): ?><th scope="col" class="num"><span class="sr-only">Aksi</span></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!$data): ?><tr class="empty">
                    <td colspan="7"><?php echo $tab === 'menunggu'
                            ? 'Tidak ada pembayaran yang menunggu verifikasi.'
                            : 'Belum ada pembayaran yang diproses.'; ?></td>
                </tr><?php endif; ?>
                <?php foreach ($data as $row):
                    $tanpa_bukti = $row['metode_pembayaran'] !== 'Tunai' && (string) $row['berkas_bukti'] === '';
                    $pesan_setuju = 'Setujui pembayaran ' . rupiah($row['jumlah']) . ' dari ' . $row['nama'] . '? Nominal ini dicatat sebagai pemasukan kas.'
                        . ($tanpa_bukti ? ' Perhatian: bukti pembayaran tidak ada.' : '');
                ?>
                <tr>
                    <td class="cell-title"><?php echo e($row['nama']); ?></td>
                    <td class="desc" data-label="Tagihan"><?php echo e($row['judul']); ?></td>
                    <td class="num" data-label="Jumlah"><?php echo rupiah($row['jumlah']); ?></td>
                    <td class="cell-nowrap" data-label="Metode"><?php echo e($row['metode_pembayaran']); ?></td>
                    <td class="nowrap" data-label="Dikirim"><?php echo e(tanggal_id($row['tanggal_pembayaran'], true)); ?></td>
                    <td data-label="Bukti"><?php echo $tampil_bukti($row); ?></td>
                    <?php if ($tab === 'riwayat'): ?>
                    <td data-label="Status"><span class="cell-value">
                        <span class="badge <?php echo kelas_status($row['status']); ?>"><?php echo e(label_status($row['status'])); ?></span>
                        <?php if ($row['status'] === 'Ditolak' && trim((string) $row['catatan']) !== ''): ?>
                        <span class="reason-text"><?php echo e($row['catatan']); ?></span>
                        <?php endif; ?>
                    </span></td>
                    <?php endif; ?>
                    <?php if ($kolom_aksi): ?>
                    <td class="cell-actions">
                        <div class="row-actions">
                            <form method="post" class="inline-form" action="index.php?tab=menunggu"
                                data-konfirmasi="<?php echo e($pesan_setuju); ?>">
                                <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                <button name="status" value="Disetujui" class="btn success small">Setujui</button>
                            </form>
                            <details class="reject">
                                <summary class="btn ghost-danger small">Tolak</summary>
                                <form method="post" class="reject-form" action="index.php?tab=menunggu">
                                    <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                    <input type="hidden" name="status" value="Ditolak">
                                    <label for="catatan-<?php echo (int) $row['id']; ?>">Alasan penolakan</label>
                                    <textarea id="catatan-<?php echo (int) $row['id']; ?>" name="catatan" maxlength="200" required
                                        placeholder="Contoh: nominal di bukti tidak sesuai tagihan"></textarea>
                                    <button class="btn danger small" type="submit">Kirim penolakan</button>
                                </form>
                            </details>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
