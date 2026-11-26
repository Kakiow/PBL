<?php
// Partial modal form buat & gabung grup untuk grup/index.php.
if (!isset($grup_saya)) {
    http_response_code(404);
    exit;
}
?>
<dialog class="grup-dialog" id="dialog-buat-grup" aria-labelledby="judul-buat-grup">
    <div class="form-card grup-dialog-card">
        <button type="button" class="grup-dialog-close" data-tutup-dialog aria-label="Tutup dialog">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="m6 6 12 12M18 6 6 18" />
            </svg>
        </button>
        <h2 id="judul-buat-grup">
            <span class="card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M12 5v14M5 12h14" />
                </svg>
            </span>
            Buat grup baru
        </h2>
        <p class="form-hint">Buat grup baru dan kamu otomatis menjadi Admin. Kode grup dibuat
            otomatis dan dapat dibagikan dari halaman Detail grup.</p>
        <form method="post" action="index.php">
            <?php csrf_field(); ?>
            <input type="hidden" name="aksi" value="buat_grup">
            <p>
                <label for="nama_grup">Nama grup</label>
                <input id="nama_grup" type="text" name="nama_grup" maxlength="120"
                    placeholder="Contoh: Kelas TI 2B" required>
            </p>
            <p>
                <label for="deskripsi_grup">Deskripsi <span class="label-optional">opsional</span></label>
                <textarea id="deskripsi_grup" name="deskripsi" rows="3" maxlength="200"
                    placeholder="Tuliskan deskripsi singkat grup kas"></textarea>
            </p>
            <div class="form-actions">
                <button class="btn primary full" type="submit">Buat grup</button>
            </div>
        </form>
    </div>
</dialog>

<dialog class="grup-dialog" id="dialog-gabung-grup" aria-labelledby="judul-gabung-grup">
    <div class="form-card grup-dialog-card">
        <button type="button" class="grup-dialog-close" data-tutup-dialog aria-label="Tutup dialog">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="m6 6 12 12M18 6 6 18" />
            </svg>
        </button>
        <h2 id="judul-gabung-grup">
            <span class="card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                    <path d="M10 17l5-5-5-5" />
                    <path d="M15 12H3" />
                </svg>
            </span>
            Gabung grup
        </h2>
        <p class="form-hint">Punya kode dari teman? Masukkan kodenya untuk bergabung ke grup kas.</p>
        <form method="post" action="index.php">
            <?php csrf_field(); ?>
            <input type="hidden" name="aksi" value="gabung_grup">
            <p>
                <label for="kode_grup">Kode grup</label>
                <input id="kode_grup" type="text" name="kode_grup"
                    placeholder="Contoh: K7XM4QPA" autocomplete="off" autocapitalize="characters" required>
            </p>
            <div class="form-actions">
                <button class="btn success full" type="submit">Gabung grup</button>
            </div>
        </form>
    </div>
</dialog>
