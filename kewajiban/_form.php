<?php
// Partial form tagihan: hanya dipakai lewat tambah.php / edit.php.
// Parameter: $mode ('tambah'|'edit'), $row (data tagihan, kosong saat tambah),
//   $nominal_terkunci (bool), $tenggat_min (Y-m-d), $judul, $deskripsi_halaman.
if (!isset($mode, $row, $tenggat_min)) {
    http_response_code(404);
    exit;
}

$is_edit = $mode === 'edit';
$nominal_terkunci = !empty($nominal_terkunci);
$val_jumlah = isset($row['jumlah']) && (float) $row['jumlah'] > 0 ? (string) (float) $row['jumlah'] : '';
?>
<div class="page-head">
    <?php tautan_kembali($is_edit ? 'detail.php?id=' . (int) $row['id'] : 'index.php'); ?>
    <h1><?php echo e($judul); ?></h1>
    <p><?php echo e($deskripsi_halaman); ?></p>
</div>

<section class="form-card form-card--create">
    <h2>
        <span class="card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <?php if ($is_edit): ?>
                <path d="M12 20h9" />
                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z" />
                <?php else: ?>
                <path d="M12 5v14M5 12h14" />
                <?php endif; ?>
            </svg>
        </span>
        Detail tagihan
    </h2>
    <p class="form-hint">Isi judul, nominal, dan tenggat pembayaran. Status lunas tiap anggota dapat dipantau dari
        daftar tagihan.</p>
    <form method="post" class="form-kewajiban">
        <?php if ($is_edit): ?>
        <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
        <?php endif; ?>
        <div class="form-grid">
            <p class="field field--full">
                <label for="judul">Judul</label>
                <input id="judul" name="judul" type="text" maxlength="120" required
                    value="<?php echo e($row['judul'] ?? ''); ?>"
                    data-error="Judul kewajiban wajib diisi." placeholder="Contoh: Kas mingguan Oktober">
                <span class="field-hint">Nama singkat tagihan agar mudah dikenali anggota.</span>
            </p>
            <p class="field">
                <label for="jumlah">Jumlah</label>
                <span class="input-affix">
                    <span class="input-affix-label" aria-hidden="true">Rp</span>
                    <input id="jumlah" name="jumlah" type="number" min="1" step="any" inputmode="numeric" required
                        value="<?php echo e($val_jumlah); ?>"<?php echo $nominal_terkunci ? ' readonly' : ''; ?>
                        data-error="Jumlah harus lebih dari 0." placeholder="25000">
                </span>
                <?php if ($nominal_terkunci): ?>
                <span class="field-hint">Nominal terkunci karena sudah ada pembayaran yang dikirim atau disetujui.</span>
                <?php else: ?>
                <span class="chip-row">
                    <button type="button" class="chip-btn" data-nominal="10000">10.000</button>
                    <button type="button" class="chip-btn" data-nominal="25000">25.000</button>
                    <button type="button" class="chip-btn" data-nominal="50000">50.000</button>
                    <button type="button" class="chip-btn" data-nominal="100000">100.000</button>
                </span>
                <?php endif; ?>
                <span class="field-hint" id="jumlah-preview">Pilih nominal cepat atau isi manual.</span>
            </p>
            <p class="field">
                <label for="tenggat_waktu">Tenggat</label>
                <input id="tenggat_waktu" name="tenggat_waktu" type="date" min="<?php echo e($tenggat_min); ?>" required
                    value="<?php echo e($row['tenggat_waktu'] ?? ''); ?>"
                    data-error="Tenggat tidak boleh sebelum hari ini.">
                <span class="chip-row">
                    <button type="button" class="chip-btn" data-tenggat="0">Hari ini</button>
                    <button type="button" class="chip-btn" data-tenggat="7">+7 hari</button>
                    <button type="button" class="chip-btn" data-tenggat="akhir-bulan">Akhir bulan</button>
                </span>
                <span class="field-hint" id="tenggat-preview">Tanggal terakhir pembayaran.</span>
            </p>
            <p class="field field--full">
                <label for="deskripsi">Deskripsi <span class="label-optional">opsional</span></label>
                <textarea id="deskripsi" name="deskripsi" rows="3" maxlength="200"
                    placeholder="Contoh: Iuran kas untuk kegiatan bakti sosial."><?php echo e($row['deskripsi'] ?? ''); ?></textarea>
                <span class="field-hint field-hint--counter" id="deskripsi-counter">0/200</span>
            </p>
        </div>
        <div class="form-actions">
            <button class="btn primary" type="submit" data-loading-text="Menyimpan…"><?php echo $is_edit ? 'Simpan perubahan' : 'Buat tagihan'; ?></button>
            <?php if ($is_edit): ?>
            <a class="btn secondary" href="detail.php?id=<?php echo (int) $row['id']; ?>">Batal</a>
            <?php endif; ?>
            <span class="form-status" role="status" aria-live="polite"></span>
        </div>
    </form>
</section>
