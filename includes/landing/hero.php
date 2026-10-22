<?php
// Section 1 — hero. Komposisi "Tiga meja, satu kas": bidang navy + jendela demo
// yang menimpa dan keluar tepi kanan. Data di panel demo sintetis (diberi label).
?>
<section class="lp-hero">
    <div class="lp-hero-band" aria-hidden="true"></div>
    <div class="lp-hero-in">
        <div class="lp-hero-copy">
            <h1>Kas kelas, tanpa perlu tanya <em>“udah bayar belum?”</em></h1>
            <p class="lp-hero-lede">Setiap tagihan punya status, setiap pembayaran punya bukti, dan setiap rupiah berakhir di satu laporan yang sama.</p>
            <div class="lp-hero-actions">
                <a class="lp-btn lp-btn-light" href="auth/register.php">Buat grup kelas
                    <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M2 8h11M9.5 4.5 13 8l-3.5 3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
                <a class="lp-btn lp-btn-outline" href="#alur">Lihat cara kerjanya</a>
            </div>

            <div class="lp-hero-roles" role="tablist" aria-label="Tampilkan demo sebagai">
                <button class="lp-role" type="button" role="tab" id="tab-admin" aria-controls="panel-admin" aria-selected="false" tabindex="-1" data-role="admin">
                    <b>Admin</b><span>Atur anggota dan peran grup.</span>
                </button>
                <button class="lp-role" type="button" role="tab" id="tab-bendahara" aria-controls="panel-bendahara" aria-selected="true" tabindex="0" data-role="bendahara">
                    <b>Bendahara</b><span>Tagih, verifikasi, catat.</span>
                </button>
                <button class="lp-role" type="button" role="tab" id="tab-anggota" aria-controls="panel-anggota" aria-selected="false" tabindex="-1" data-role="anggota">
                    <b>Anggota</b><span>Bayar dan pantau status.</span>
                </button>
            </div>
        </div>

        <div class="lp-hero-app">
            <div class="lp-frame">
                <aside class="lp-rail" aria-hidden="true">
                    <div class="lp-rail-brand">SIMKAS</div>
                    <p class="lp-rail-cap">Menu</p>
                    <span class="lp-rail-item" data-nav="dashboard">Dashboard</span>
                    <span class="lp-rail-item" data-nav="grup">Grup</span>
                    <span class="lp-rail-item" data-nav="anggota">Anggota</span>
                    <p class="lp-rail-cap">Kas</p>
                    <span class="lp-rail-item" data-nav="tagihan">Tagihan <span class="lp-badge">3</span></span>
                    <span class="lp-rail-item" data-nav="verifikasi">Verifikasi <span class="lp-badge warn">2</span></span>
                    <span class="lp-rail-item" data-nav="transaksi">Transaksi</span>
                    <span class="lp-rail-item" data-nav="laporan">Laporan</span>
                    <div class="lp-rail-user"><b>Rochman</b><span data-user-role>Bendahara · XI RPL 1</span></div>
                </aside>

                <div class="lp-app">
                    <div class="lp-app-top">
                        <span class="lp-app-chip">XI RPL 1</span>
                        <span class="lp-app-hint" data-hint>Bendahara</span>
                    </div>

                    <div class="lp-panels">
                        <div class="lp-panel" id="panel-admin" role="tabpanel" aria-labelledby="tab-admin" hidden>
                            <div class="lp-panel-head">
                                <h2>Anggota grup</h2>
                                <p>32 anggota · satu Admin, satu Bendahara.</p>
                            </div>
                            <ul class="lp-people">
                                <li><span class="lp-av">R</span><div><b>Muhammad Nur Rochman</b><span>rochman@kampus.ac.id</span></div><span class="lp-tag admin">Admin</span></li>
                                <li><span class="lp-av amber">O</span><div><b>Oktavian Kusuma A.</b><span>oktavian@kampus.ac.id</span></div><span class="lp-tag warn">Bendahara</span></li>
                                <li><span class="lp-av slate">F</span><div><b>Muhammad Ferdi A.</b><span>ferdi@kampus.ac.id</span></div><span class="lp-tag">Anggota</span></li>
                                <li><span class="lp-av slate">I</span><div><b>Ibni Andarta</b><span>ibni@kampus.ac.id</span></div><span class="lp-tag">Anggota</span></li>
                            </ul>
                            <p class="lp-panel-foot">Peran diubah dari halaman ini. Grup selalu punya tepat satu Admin.</p>
                        </div>

                        <div class="lp-panel" id="panel-bendahara" role="tabpanel" aria-labelledby="tab-bendahara">
                            <div class="lp-panel-head">
                                <h2>Verifikasi pembayaran</h2>
                                <p><span data-pending>2</span> pembayaran menunggu diperiksa.</p>
                            </div>
                            <ul class="lp-queue">
                                <li class="lp-qrow" data-amount="15000">
                                    <span class="lp-proof">Bukti<br>BCA</span>
                                    <div class="lp-qmain"><b>Iuran bulan Oktober</b><span>Oktavian K. A. · Transfer BCA · 24 Okt</span></div>
                                    <span class="lp-amt">Rp 15.000</span>
                                    <div class="lp-qacts">
                                        <button type="button" class="lp-yes" data-approve>Setujui</button>
                                        <button type="button" class="lp-no" data-reject>Tolak</button>
                                    </div>
                                    <span class="lp-tag ok" data-result hidden>Disetujui</span>
                                </li>
                                <li class="lp-qrow" data-amount="5000">
                                    <span class="lp-proof">Bukti<br>QRIS</span>
                                    <div class="lp-qmain"><b>Kas kebersihan</b><span>Muhammad Ferdi A. · QRIS · 23 Okt</span></div>
                                    <span class="lp-amt">Rp 5.000</span>
                                    <div class="lp-qacts">
                                        <button type="button" class="lp-yes" data-approve>Setujui</button>
                                        <button type="button" class="lp-no" data-reject>Tolak</button>
                                    </div>
                                    <span class="lp-tag ok" data-result hidden>Disetujui</span>
                                </li>
                                <li class="lp-qrow is-done">
                                    <span class="lp-proof ok">Tunai</span>
                                    <div class="lp-qmain"><b>Iuran bulan September</b><span>Ibni Andarta · Tunai · 21 Okt</span></div>
                                    <span class="lp-amt">Rp 15.000</span>
                                    <span class="lp-tag ok">Disetujui</span>
                                </li>
                                <li class="lp-qrow is-done">
                                    <span class="lp-proof ok">QRIS</span>
                                    <div class="lp-qmain"><b>Kas kebersihan</b><span>Muhammad Nur R. · QRIS · 20 Okt</span></div>
                                    <span class="lp-amt">Rp 5.000</span>
                                    <span class="lp-tag ok">Disetujui</span>
                                </li>
                            </ul>
                            <div class="lp-stats">
                                <div><span>Saldo kas</span><b>Rp <span data-saldo>2.410.000</span></b></div>
                                <div><span>Masuk bulan ini</span><b class="in">+ Rp <span data-masuk>480.000</span></b></div>
                                <div><span>Belum bayar</span><b>7 anggota</b></div>
                            </div>
                        </div>

                        <div class="lp-panel" id="panel-anggota" role="tabpanel" aria-labelledby="tab-anggota" hidden>
                            <div class="lp-panel-head">
                                <h2>Tagihan kamu</h2>
                                <p>Rp 20.000 belum dibayar dari 2 tagihan.</p>
                            </div>
                            <ul class="lp-bills">
                                <li>
                                    <div class="lp-qmain"><b>Iuran bulan Oktober</b><span>Tenggat 24 Okt 2026 · 2 hari lagi</span></div>
                                    <span class="lp-amt">Rp 15.000</span>
                                    <button type="button" class="lp-bayar" data-bayar>Bayar</button>
                                </li>
                                <li>
                                    <div class="lp-qmain"><b>Kas kebersihan</b><span>Tenggat 28 Okt 2026 · 6 hari lagi</span></div>
                                    <span class="lp-amt">Rp 5.000</span>
                                    <button type="button" class="lp-bayar" data-bayar>Bayar</button>
                                </li>
                                <li class="is-paid">
                                    <div class="lp-qmain"><b>Iuran bulan September</b><span>Dibayar 21 Okt 2026</span></div>
                                    <span class="lp-amt">Rp 15.000</span>
                                    <span class="lp-tag ok">Lunas</span>
                                </li>
                            </ul>
                            <p class="lp-panel-foot">Status berubah setelah bendahara memverifikasi buktimu. Bukti tersimpan, bukan hilang di chat.</p>
                        </div>
                    </div>
                </div>
            </div>
            <p class="lp-demo-note">Demo interaktif · data contoh, bukan data kelasmu.</p>
        </div>
    </div>
</section>
