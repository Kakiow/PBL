<?php
// Partial form buat & gabung grup untuk grup/index.php.
if (!isset($grup_saya)) {
    http_response_code(404);
    exit;
}
?>
<div class="grup-grid">
    <div class="form-card form-card--create">
        <h2>
            <span class="card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M12 5v14M5 12h14" />
                </svg>
            </span>
            Buat grup baru
        </h2>
        <p class="form-hint">Buat grup baru dan kamu otomatis menjadi Admin. Kode grup dibuat
            otomatis, lalu bisa kamu bagikan ke anggota dari halaman Detail grup.</p>
        <form method="post">
            <input type="hidden" name="aksi" value="buat_grup">
            <p><label for="nama_grup">Nama grup</label><input id="nama_grup" type="text" name="nama_grup" maxlength="120"
                    placeholder="Contoh: Kelas TI 2B" required></p>
            <p><label for="deskripsi_grup">Deskripsi <span class="label-optional">opsional</span></label><textarea
                    id="deskripsi_grup" name="deskripsi" rows="3" maxlength="200"></textarea></p>
            <button class="btn primary full" type="submit">Buat grup</button>
        </form>
    </div>
    <div class="form-card form-card--join">
        <h2>
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
        <p class="form-hint">Punya kode dari teman? Masukkan kodenya untuk bergabung ke grup.</p>
        <form method="post">
            <input type="hidden" name="aksi" value="gabung_grup">
            <p><label for="kode_grup">Kode grup</label><input id="kode_grup" type="text" name="kode_grup"
                    placeholder="Contoh: K7XM4QPA" autocomplete="off" autocapitalize="characters" required></p>
            <button class="btn success full" type="submit">Gabung grup</button>
        </form>
    </div>
</div>
