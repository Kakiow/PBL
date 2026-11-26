<?php
// Partial form tagihan: hanya dipakai lewat tambah.php / edit.php.
// Parameter: $mode ('tambah'|'edit'), $row (data tagihan, kosong saat tambah),
//   $nominal_terkunci (bool), $tenggat_min (Y-m-d), $judul, $deskripsi_halaman.
if (!isset($mode, $row, $tenggat_min)) {
    http_response_code(404);
    exit;
}

/** @var string $mode */
/** @var array $row */
/** @var string $tenggat_min */
/** @var string $judul */
/** @var string $deskripsi_halaman */
/** @var bool|null $nominal_terkunci */
/** @var array|null $anggota_grup */
/** @var string|null $mode_sasaran */
/** @var array|null $sasaran_terpilih */

$judul = $judul ?? 'Tagihan';
$deskripsi_halaman = $deskripsi_halaman ?? '';
$is_edit = $mode === 'edit';
$nominal_terkunci = !empty($nominal_terkunci);
$val_jumlah = isset($row['jumlah']) && (float) $row['jumlah'] > 0 ? (string) (float) $row['jumlah'] : '';
$anggota_grup = $anggota_grup ?? [];
$mode_sasaran = $mode_sasaran ?? 'semua';
$sasaran_terpilih = $sasaran_terpilih ?? [];
$tampil_sasaran = !$is_edit && !empty($anggota_grup);
$total_anggota = count($anggota_grup);
?>
<div class="page-head">
    <?php tautan_kembali($is_edit ? 'detail.php?id=' . (int) ($row['id'] ?? 0) : 'index.php'); ?>
    <h1><?php echo e($judul); ?></h1>
    <p><?php echo e($deskripsi_halaman); ?></p>
</div>

<section class="form-card form-card--create">
    <form method="post" class="form-tagihan">
        <?php csrf_field(); ?>
        <?php if ($is_edit): ?>
        <input type="hidden" name="id" value="<?php echo (int) ($row['id'] ?? 0); ?>">
        <?php endif; ?>

        <div class="form-section">
            <h2 class="form-section-title">
                <span class="card-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <?php if ($is_edit): ?>
                        <path d="M12 20h9" /><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z" />
                        <?php else: ?>
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                        <?php endif; ?>
                    </svg>
                </span>
                Informasi tagihan
            </h2>

            <div class="form-grid">
                <p class="field field--full">
                    <label for="judul">Judul tagihan</label>
                    <input id="judul" name="judul" type="text" maxlength="120" required
                        value="<?php echo e($row['judul'] ?? ''); ?>"
                        data-error="Judul tagihan wajib diisi." placeholder="Contoh: Kas mingguan Oktober">
                </p>

                <p class="field">
                    <label for="jumlah">Jumlah per anggota</label>
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
                        <button type="button" class="chip-btn" data-nominal="10000">10rb</button>
                        <button type="button" class="chip-btn" data-nominal="25000">25rb</button>
                        <button type="button" class="chip-btn" data-nominal="50000">50rb</button>
                        <button type="button" class="chip-btn" data-nominal="100000">100rb</button>
                    </span>
                    <span class="field-hint" id="jumlah-preview" hidden></span>
                    <?php endif; ?>
                </p>

                <p class="field">
                    <label for="tenggat_waktu">Tenggat pembayaran</label>
                    <input id="tenggat_waktu" name="tenggat_waktu" type="date" min="<?php echo e($tenggat_min); ?>" required
                        value="<?php echo e($row['tenggat_waktu'] ?? ''); ?>"
                        data-error="Tenggat tidak boleh sebelum hari ini.">
                    <span class="chip-row">
                        <button type="button" class="chip-btn" data-tenggat="0">Hari ini</button>
                        <button type="button" class="chip-btn" data-tenggat="7">+7 hari</button>
                        <button type="button" class="chip-btn" data-tenggat="akhir-bulan">Akhir bulan</button>
                    </span>
                    <span class="field-hint" id="tenggat-preview" hidden></span>
                </p>

                <p class="field field--full">
                    <label for="deskripsi">Catatan / Deskripsi <span class="label-optional">opsional</span></label>
                    <textarea id="deskripsi" name="deskripsi" rows="2" maxlength="200"
                        placeholder="Contoh: Iuran kas untuk kebutuhan spidol dan perlengkapan kelas."><?php echo e($row['deskripsi'] ?? ''); ?></textarea>
                    <span class="field-hint field-hint--counter" id="deskripsi-counter">0/200</span>
                </p>
            </div>
        </div>

        <?php if ($tampil_sasaran): ?>
        <div class="form-section form-section--sasaran">
            <fieldset class="sasaran-field" data-sasaran-field>
                <legend class="form-section-title">
                    <span class="card-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </span>
                    Sasaran anggota
                </legend>

                <div class="sasaran-mode">
                    <label class="sasaran-mode-item">
                        <input type="radio" name="mode_sasaran" value="semua"
                            <?php echo $mode_sasaran === 'semua' ? 'checked' : ''; ?> data-mode-sasaran>
                        <span class="sasaran-mode-text">
                            <strong>Semua anggota</strong>
                            <small>Seluruh <?php echo (int) $total_anggota; ?> anggota kelas &amp; anggota baru.</small>
                        </span>
                    </label>
                    <label class="sasaran-mode-item">
                        <input type="radio" name="mode_sasaran" value="pilih"
                            <?php echo $mode_sasaran === 'pilih' ? 'checked' : ''; ?> data-mode-sasaran>
                        <span class="sasaran-mode-text">
                            <strong>Pilih anggota tertentu</strong>
                            <small>Hanya anggota yang dicentang yang ditagih.</small>
                        </span>
                    </label>
                </div>

                <div class="sasaran-all-info" data-sasaran-all-info<?php echo $mode_sasaran === 'pilih' ? ' hidden' : ''; ?>>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="18" height="18">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <span>Tagihan akan otomatis dibebankan ke seluruh <strong><?php echo (int) $total_anggota; ?> anggota aktif</strong> grup ini.</span>
                </div>

                <div class="sasaran-picker" data-sasaran-picker<?php echo $mode_sasaran === 'semua' ? ' hidden' : ''; ?>>
                    <div class="sasaran-toolbar">
                        <div class="sasaran-toolbar-btns">
                            <button type="button" class="btn secondary small" data-sasaran-all>Pilih semua</button>
                            <button type="button" class="btn secondary small" data-sasaran-none>Kosongkan</button>
                        </div>
                        <span class="sasaran-count" data-sasaran-count aria-live="polite"></span>
                    </div>
                    <div class="sasaran-list">
                        <?php foreach ($anggota_grup as $a): ?>
                        <label class="sasaran-item">
                            <input type="checkbox" name="sasaran[]" value="<?php echo (int) $a['id']; ?>"
                                <?php echo in_array((int) $a['id'], $sasaran_terpilih, true) ? 'checked' : ''; ?>>
                            <span class="sasaran-nama"><?php echo e($a['nama']); ?></span>
                            <span class="badge role-<?php echo strtolower($a['peran']); ?>"><?php echo e($a['peran']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="field-error" id="sasaran-error" data-sasaran-error role="alert" hidden>Pilih minimal satu anggota.</p>
                </div>
            </fieldset>
        </div>
        <?php endif; ?>

        <div class="form-actions">
            <button class="btn primary" type="submit" data-loading-text="Menyimpan…"><?php echo $is_edit ? 'Simpan perubahan' : 'Buat tagihan'; ?></button>
            <?php if ($is_edit): ?>
            <a class="btn secondary" href="detail.php?id=<?php echo (int) ($row['id'] ?? 0); ?>">Batal</a>
            <?php endif; ?>
            <span class="form-status" role="status" aria-live="polite"></span>
        </div>
    </form>
</section>
