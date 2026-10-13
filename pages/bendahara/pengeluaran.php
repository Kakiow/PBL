
<?php
$role = 'bendahara';
$active = 'pengeluaran';
$pageTitle = 'Catat Pengeluaran';
$pageDescription = 'Simpan transaksi pengeluaran dan perbarui saldo grup.';
$content = <<<'HTML'
<section class="content-card">
    <div class="content-card-header">
        <div>
            <h3>Data Pengeluaran</h3>
            <p>Grup aktif: Angkatan 2025.</p>
        </div>
    </div>

    <form>
        <div class="form-grid">
            <div class="field">
                <label>Tanggal</label>
                <input type="date" value="2026-10-12" required>
            </div>
            <div class="field">
                <label>Nominal</label>
                <input type="number" min="1" placeholder="0" required>
            </div>
            <div class="field">
                <label>Kategori</label>
                <select required>
                    <option value="">Pilih kategori</option>
                    <option>Kegiatan</option>
                    <option>Operasional</option>
                    <option>Administrasi</option>
                    <option>Lainnya</option>
                </select>
            </div>
            <div class="field">
                <label>Sumber Dana</label>
                <select required>
                    <option value="">Pilih sumber dana</option>
                    <option>Kas Bulanan</option>
                    <option>Iuran Kegiatan</option>
                    <option>Saldo Grup</option>
                </select>
            </div>
            <div class="field full">
                <label>Deskripsi Transaksi</label>
                <textarea placeholder="Contoh: Pembelian konsumsi rapat" required></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="reset" class="btn btn-outline">Reset</button>
            <button type="button" class="btn btn-primary" data-demo-action="Transaksi pengeluaran belum tersimpan karena backend belum terhubung.">Simpan Transaksi</button>
        </div>
    </form>
</section>

<section class="content-card">
    <div class="content-card-header">
        <div>
            <h3>Pengeluaran Terbaru</h3>
            <p>Riwayat transaksi contoh.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Keterangan</th>
                    <th>Kategori</th>
                    <th>Nominal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>10 Okt 2026</td>
                    <td>Konsumsi rapat</td>
                    <td>Kegiatan</td>
                    <td class="negative">- Rp 350.000</td>
                </tr>
                <tr>
                    <td>08 Okt 2026</td>
                    <td>Perlengkapan acara</td>
                    <td>Operasional</td>
                    <td class="negative">- Rp 125.000</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>
HTML;

require '../../components/layout.php';