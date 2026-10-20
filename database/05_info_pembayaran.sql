-- Info tujuan pembayaran grup (nomor rekening, e-wallet, atau keterangan QRIS)
-- yang ditampilkan ke anggota di halaman Bayar. Aman dijalankan berulang.
-- Untuk instalasi baru, kolom ini juga sudah ada di 01_schema.sql.

ALTER TABLE grup ADD COLUMN IF NOT EXISTS info_pembayaran TEXT;
