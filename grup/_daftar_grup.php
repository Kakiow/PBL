<?php
// Partial daftar "Grup saya" untuk grup/index.php. Butuh: $grup_saya, $id_aktif.
if (!isset($grup_saya, $id_aktif)) {
    http_response_code(404);
    exit;
}
?>
<section class="content-card">
    <div class="card-head">
        <div>
            <h2>Grup saya</h2>
            <p><?php echo $grup_saya
                    ? 'Pilih kartu untuk menjadikannya grup aktif, atau buka Detail untuk mengelola grup.'
                    : 'Kamu belum tergabung di grup mana pun. Buat grup baru atau gabung memakai kode dari temanmu.'; ?></p>
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
                        <?php echo e($g['peran']); ?><?php if ($aktif): ?> <span class="badge success">Aktif</span><?php endif; ?></span>
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
