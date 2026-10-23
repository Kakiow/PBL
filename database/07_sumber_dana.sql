-- Tiga pilihan sumber dana untuk setiap grup: Kas Tunai, Rekening Bank, E-Wallet.
-- Hanya menambah akun yang belum ada; akun dan transaksi lama tidak diubah.
-- Aman dijalankan berulang.

INSERT INTO akun_dana (id_grup, nama_akun, jenis_akun)
SELECT g.id, v.nama_akun, v.jenis_akun
FROM grup g
CROSS JOIN (
    VALUES
        ('Kas Tunai', 'Kas_Tunai'::jenis_akun_enum),
        ('Rekening Bank', 'Rekening_Bank'::jenis_akun_enum),
        ('E-Wallet', 'E-Wallet'::jenis_akun_enum)
) AS v(nama_akun, jenis_akun)
WHERE NOT EXISTS (
    SELECT 1
    FROM akun_dana a
    WHERE a.id_grup = g.id AND a.jenis_akun = v.jenis_akun
);
