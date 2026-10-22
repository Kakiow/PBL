<?php
// Section 4 — peran dan izin sebagai matriks ceklis.
$izin = [
    ['Buat dan ubah tagihan', true, true, false],
    ['Verifikasi pembayaran', true, true, false],
    ['Catat transaksi', true, true, false],
    ['Bayar tagihan sendiri', true, true, true],
    ['Lihat laporan kas', true, true, false],
    ['Ubah peran anggota', true, false, false],
    ['Hapus grup', true, false, false],
];
$peran = ['Admin', 'Bendahara', 'Anggota'];
?>
<section class="lp-section lp-peran" id="peran">
    <div class="lp-wrap">
        <div class="lp-sec-head" data-reveal>
            <h2>Peran menentukan tombol yang muncul.</h2>
            <p>Admin mengatur orang, Bendahara mengatur uang, Anggota memantau dan membayar. Tanda centang berarti kontrol itu muncul untuk peran tersebut.</p>
        </div>

        <div class="lp-table-wrap" data-reveal>
            <table class="lp-table">
                <caption class="sr-only">Kontrol yang muncul untuk tiap peran di SIMKAS</caption>
                <thead>
                    <tr>
                        <th scope="col">Tindakan</th>
                        <?php foreach ($peran as $nama): ?>
                        <th scope="col" class="c"><?php echo e($nama); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($izin as $baris): ?>
                    <tr>
                        <th scope="row"><?php echo e($baris[0]); ?></th>
                        <?php foreach ($peran as $i => $nama): ?>
                        <td class="c" data-label="<?php echo e($nama); ?>">
                            <?php if ($baris[$i + 1]): ?>
                            <span class="lp-mark" aria-hidden="true"><svg viewBox="0 0 16 16"><path d="m3.5 8.5 3.2 3.2L12.8 4.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span class="sr-only">Muncul</span>
                            <?php else: ?>
                            <span class="lp-dash" aria-hidden="true">&mdash;</span><span class="sr-only">Tidak muncul</span>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="lp-table-note" data-reveal>Pembuat grup otomatis jadi Admin. Promosi Admin baru menurunkan Admin lama, jadi grup selalu punya tepat satu Admin.</p>
    </div>
</section>
