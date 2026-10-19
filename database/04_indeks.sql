-- Indeks tambahan untuk kolom FK yang sering di-JOIN/filter (badge sidebar, Verifikasi,
-- detail tagihan, form transaksi). Aman dijalankan berulang pada database yang sudah ada.
-- Untuk instalasi baru, indeks ini juga sudah ada di 01_schema.sql.

CREATE INDEX IF NOT EXISTS idx_pembayaran_anggota_kewajiban ON pembayaran(id_anggota_kewajiban);
CREATE INDEX IF NOT EXISTS idx_pembayaran_status ON pembayaran(status);
CREATE INDEX IF NOT EXISTS idx_anggota_kewajiban_pengguna ON anggota_kewajiban(id_pengguna);
CREATE INDEX IF NOT EXISTS idx_kategori_grup ON kategori(id_grup);
CREATE INDEX IF NOT EXISTS idx_akun_dana_grup ON akun_dana(id_grup);
