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
                    : 'Kamu belum tergabung di grup mana pun. Buat grup baru atau gabung memakai kode.'; ?></p>
        </div>
        <div class="card-head-actions">
            <button type="button" class="btn primary small" data-buka-dialog="dialog-buat-grup">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Buat grup
            </button>
            <button type="button" class="btn secondary small" data-buka-dialog="dialog-gabung-grup">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" aria-hidden="true">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                    <path d="M10 17l5-5-5-5" />
                    <path d="M15 12H3" />
                </svg>
                Gabung grup
            </button>
        </div>
    </div>

    <?php if (!$grup_saya): ?>
    <div class="empty-state">
        <div class="empty-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
        </div>
        <h3>Belum ada grup</h3>
        <p class="muted">Kamu belum bergabung di grup mana pun. Buat grup baru untuk mulai mencatat kas atau bergabung dengan kode dari teman.</p>
        <div class="empty-actions">
            <button type="button" class="btn primary" data-buka-dialog="dialog-buat-grup">Buat grup baru</button>
            <button type="button" class="btn secondary" data-buka-dialog="dialog-gabung-grup">Gabung via kode</button>
        </div>
    </div>
    <?php else: ?>
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
    <?php endif; ?>
</section>
