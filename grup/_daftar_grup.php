<?php
// Partial daftar "Grup saya" untuk grup/index.php. Butuh: $grup_saya, $id_aktif, $id_user.
if (!isset($grup_saya, $id_aktif, $id_user)) {
    http_response_code(404);
    exit;
}

$grup_aktif_data = null;
$grup_lainnya = [];
foreach ($grup_saya as $g) {
    if ((int) $g['id'] === $id_aktif && $grup_aktif_data === null) {
        $grup_aktif_data = $g;
    } else {
        $grup_lainnya[] = $g;
    }
}
if ($grup_aktif_data === null && $grup_lainnya) {
    $grup_aktif_data = array_shift($grup_lainnya);
}
?>
<?php if (!$grup_saya): ?>
<section class="content-card">
    <div class="grup-empty">
        <div class="grup-empty-head">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                stroke-linejoin="round" aria-hidden="true">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
            <h2>Belum tergabung di grup mana pun</h2>
        </div>
        <p>Buat grup kas baru untuk kelas atau organisasi kamu, atau masukkan kode undangan 8 karakter dari pengurus.</p>
        <div class="grup-empty-actions">
            <button type="button" class="btn primary" data-buka-dialog="dialog-buat-grup">Buat grup baru</button>
            <button type="button" class="btn secondary" data-buka-dialog="dialog-gabung-grup">Gabung via kode</button>
        </div>
    </div>
</section>
<?php else: ?>
<?php if ($grup_aktif_data): ?>
<section class="active-group-card" id="active-spotlight" data-active-id="<?php echo (int) $grup_aktif_data['id']; ?>"
    aria-label="Grup aktif saat ini">
    <div class="active-group-info">
        <div class="active-group-title-row">
            <button type="button" class="star-btn" data-favorit="<?php echo (int) $grup_aktif_data['id']; ?>"
                aria-pressed="false" aria-label="Tandai favorit" title="Tandai favorit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" aria-hidden="true">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                </svg>
            </button>
            <h2><?php echo e($grup_aktif_data['nama_grup']); ?></h2>
            <span class="badge success">Aktif</span>
        </div>
        <div class="active-group-meta">
            <span><?php echo e($grup_aktif_data['peran']); ?></span>
            <span>&middot;</span>
            <span><?php echo (int) $grup_aktif_data['jumlah_anggota']; ?> anggota</span>
        </div>
    </div>
    <div class="active-group-actions">
        <button type="button" class="code-btn" data-copy="<?php echo e($grup_aktif_data['kode_grup']); ?>"
            title="Salin kode undangan <?php echo e($grup_aktif_data['kode_grup']); ?>">
            <?php echo e($grup_aktif_data['kode_grup']); ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" aria-hidden="true">
                <rect x="9" y="9" width="11" height="11" rx="2" />
                <path d="M5 15V5a2 2 0 0 1 2-2h10" />
            </svg>
        </button>
        <a class="btn secondary small" href="detail.php?id=<?php echo (int) $grup_aktif_data['id']; ?>">Detail</a>
    </div>
</section>
<?php endif; ?>

<?php if ($grup_lainnya): ?>
<section class="content-card" id="other-groups-card">
    <div class="grup-directory-head">
        <div class="grup-directory-title">
            <h2>Grup lainnya</h2>
            <span class="grup-count" id="grup-count"><?php echo count($grup_lainnya); ?> grup</span>
        </div>
        <div class="grup-search-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8" />
                <path d="m21 21-4.3-4.3" />
            </svg>
            <input type="search" id="cari-grup" class="grup-search" placeholder="Cari nama grup..."
                aria-label="Cari nama grup">
        </div>
    </div>
    <div class="grup-rows" id="grup-rows" data-user-id="<?php echo (int) $id_user; ?>">
        <?php foreach ($grup_lainnya as $g): ?>
        <div class="grup-row" data-row-id="<?php echo (int) $g['id']; ?>" data-fav="0"
            data-nama="<?php echo e($g['nama_grup']); ?>">
            <div class="grup-row-main">
                <button type="button" class="star-btn" data-favorit="<?php echo (int) $g['id']; ?>"
                    aria-pressed="false" aria-label="Tandai favorit" title="Tandai favorit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" aria-hidden="true">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                    </svg>
                </button>
                <div class="grup-row-text">
                    <div class="grup-row-title">
                        <strong><?php echo e($g['nama_grup']); ?></strong>
                    </div>
                    <div class="grup-row-sub">
                        <span><?php echo e($g['peran']); ?></span>
                        <span>&middot;</span>
                        <span><?php echo (int) $g['jumlah_anggota']; ?> anggota</span>
                        <span>&middot;</span>
                        <span class="grup-row-code"><?php echo e($g['kode_grup']); ?></span>
                    </div>
                </div>
            </div>
            <div class="grup-row-actions">
                <form method="post" class="grup-pilih-form">
                    <input type="hidden" name="aksi" value="pilih_grup">
                    <input type="hidden" name="id_grup" value="<?php echo (int) $g['id']; ?>">
                    <button type="submit" class="btn secondary small">Jadikan aktif</button>
                </form>
                <a class="btn secondary small" href="detail.php?id=<?php echo (int) $g['id']; ?>">Detail</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <p class="grup-search-empty" id="grup-search-empty" hidden></p>
</section>
<?php endif; ?>
<?php endif; ?>
