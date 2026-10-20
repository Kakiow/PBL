CREATE TYPE peran_enum AS ENUM ('Admin', 'Bendahara', 'Anggota');
CREATE TYPE jenis_kategori_enum AS ENUM ('Pemasukan', 'Pengeluaran');
CREATE TYPE status_anggota_kewajiban_enum AS ENUM ('Belum_Bayar', 'Menunggu', 'Lunas', 'Ditolak');
CREATE TYPE metode_pembayaran_enum AS ENUM ('Tunai', 'Transfer', 'E-Wallet', 'QRIS');
CREATE TYPE status_pembayaran_enum AS ENUM ('Menunggu', 'Disetujui', 'Ditolak');
CREATE TYPE jenis_akun_enum AS ENUM ('Kas_Tunai', 'Rekening_Bank', 'E-Wallet');
CREATE TYPE jenis_transaksi_enum AS ENUM ('Pemasukan', 'Pengeluaran');

CREATE TABLE pengguna (
    id BIGSERIAL PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    hash_kata_sandi VARCHAR(255) NOT NULL,
    apakah_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reset_password (
    id BIGSERIAL PRIMARY KEY,
    id_pengguna BIGINT NOT NULL,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    kedaluwarsa_pada TIMESTAMP NOT NULL,
    digunakan_pada TIMESTAMP,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reset_password_pengguna FOREIGN KEY (id_pengguna) REFERENCES pengguna(id) ON DELETE CASCADE
);

CREATE TABLE grup (
    id BIGSERIAL PRIMARY KEY,
    nama_grup VARCHAR(255) NOT NULL,
    kode_grup VARCHAR(50) NOT NULL UNIQUE,
    deskripsi TEXT,
    info_pembayaran TEXT,
    dibuat_oleh BIGINT NOT NULL,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_grup_dibuat_oleh FOREIGN KEY (dibuat_oleh) REFERENCES pengguna(id) ON DELETE RESTRICT
);

CREATE TABLE anggota_grup (
    id BIGSERIAL PRIMARY KEY,
    id_grup BIGINT NOT NULL,
    id_pengguna BIGINT NOT NULL,
    peran peran_enum NOT NULL DEFAULT 'Anggota',
    bergabung_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_anggota_grup_grup FOREIGN KEY (id_grup) REFERENCES grup(id) ON DELETE CASCADE,
    CONSTRAINT fk_anggota_grup_pengguna FOREIGN KEY (id_pengguna) REFERENCES pengguna(id) ON DELETE CASCADE,
    CONSTRAINT uq_anggota_grup UNIQUE (id_grup, id_pengguna)
);

CREATE TABLE kategori (
    id BIGSERIAL PRIMARY KEY,
    id_grup BIGINT NOT NULL,
    nama_kategori VARCHAR(100) NOT NULL,
    jenis jenis_kategori_enum NOT NULL,
    deskripsi TEXT,
    CONSTRAINT fk_kategori_grup FOREIGN KEY (id_grup) REFERENCES grup(id) ON DELETE CASCADE
);

CREATE TABLE kewajiban_pembayaran (
    id BIGSERIAL PRIMARY KEY,
    id_grup BIGINT NOT NULL,
    judul VARCHAR(255) NOT NULL,
    jumlah DECIMAL(12,2) NOT NULL CHECK (jumlah >= 0),
    tenggat_waktu DATE NOT NULL,
    deskripsi TEXT,
    dibuat_oleh BIGINT NOT NULL,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_kewajiban_grup FOREIGN KEY (id_grup) REFERENCES grup(id) ON DELETE CASCADE,
    CONSTRAINT fk_kewajiban_dibuat_oleh FOREIGN KEY (dibuat_oleh) REFERENCES pengguna(id) ON DELETE RESTRICT
);

CREATE TABLE anggota_kewajiban (
    id BIGSERIAL PRIMARY KEY,
    id_kewajiban BIGINT NOT NULL,
    id_pengguna BIGINT NOT NULL,
    status status_anggota_kewajiban_enum NOT NULL DEFAULT 'Belum_Bayar',
    CONSTRAINT fk_anggota_kewajiban_kewajiban FOREIGN KEY (id_kewajiban) REFERENCES kewajiban_pembayaran(id) ON DELETE CASCADE,
    CONSTRAINT fk_anggota_kewajiban_pengguna FOREIGN KEY (id_pengguna) REFERENCES pengguna(id) ON DELETE CASCADE,
    CONSTRAINT uq_anggota_kewajiban UNIQUE (id_kewajiban, id_pengguna)
);

CREATE TABLE pembayaran (
    id BIGSERIAL PRIMARY KEY,
    id_anggota_kewajiban BIGINT NOT NULL,
    jumlah DECIMAL(12,2) NOT NULL CHECK (jumlah >= 0),
    metode_pembayaran metode_pembayaran_enum NOT NULL,
    tanggal_pembayaran TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    berkas_bukti VARCHAR(255) NOT NULL,
    status status_pembayaran_enum NOT NULL DEFAULT 'Menunggu',
    diverifikasi_oleh BIGINT,
    diverifikasi_pada TIMESTAMP,
    catatan TEXT,
    CONSTRAINT fk_pembayaran_anggota_kewajiban FOREIGN KEY (id_anggota_kewajiban) REFERENCES anggota_kewajiban(id) ON DELETE CASCADE,
    CONSTRAINT fk_pembayaran_diverifikasi_oleh FOREIGN KEY (diverifikasi_oleh) REFERENCES pengguna(id) ON DELETE SET NULL
);

CREATE TABLE akun_dana (
    id BIGSERIAL PRIMARY KEY,
    id_grup BIGINT NOT NULL,
    nama_akun VARCHAR(100) NOT NULL,
    jenis_akun jenis_akun_enum NOT NULL,
    nomor_identifikasi VARCHAR(100),
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_akun_dana_grup FOREIGN KEY (id_grup) REFERENCES grup(id) ON DELETE CASCADE
);

CREATE TABLE transaksi (
    id BIGSERIAL PRIMARY KEY,
    id_grup BIGINT NOT NULL,
    id_pengguna BIGINT NOT NULL,
    id_kategori BIGINT NOT NULL,
    id_akun_dana BIGINT NOT NULL,
    id_pembayaran BIGINT UNIQUE,
    jenis jenis_transaksi_enum NOT NULL,
    jumlah DECIMAL(12,2) NOT NULL CHECK (jumlah >= 0),
    tanggal_transaksi DATE NOT NULL,
    deskripsi TEXT,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transaksi_grup FOREIGN KEY (id_grup) REFERENCES grup(id) ON DELETE CASCADE,
    CONSTRAINT fk_transaksi_pengguna FOREIGN KEY (id_pengguna) REFERENCES pengguna(id) ON DELETE RESTRICT,
    CONSTRAINT fk_transaksi_kategori FOREIGN KEY (id_kategori) REFERENCES kategori(id) ON DELETE RESTRICT,
    CONSTRAINT fk_transaksi_akun_dana FOREIGN KEY (id_akun_dana) REFERENCES akun_dana(id) ON DELETE RESTRICT,
    CONSTRAINT fk_transaksi_pembayaran FOREIGN KEY (id_pembayaran) REFERENCES pembayaran(id) ON DELETE SET NULL
);

CREATE INDEX idx_anggota_grup_grup ON anggota_grup(id_grup);
CREATE INDEX idx_anggota_grup_pengguna ON anggota_grup(id_pengguna);
CREATE INDEX idx_kewajiban_grup ON kewajiban_pembayaran(id_grup);
CREATE INDEX idx_transaksi_grup ON transaksi(id_grup);
CREATE INDEX idx_transaksi_tanggal ON transaksi(tanggal_transaksi);
CREATE INDEX idx_reset_password_pengguna ON reset_password(id_pengguna);
CREATE INDEX idx_reset_password_kedaluwarsa ON reset_password(kedaluwarsa_pada);
CREATE INDEX idx_pembayaran_anggota_kewajiban ON pembayaran(id_anggota_kewajiban);
CREATE INDEX idx_pembayaran_status ON pembayaran(status);
CREATE INDEX idx_anggota_kewajiban_pengguna ON anggota_kewajiban(id_pengguna);
CREATE INDEX idx_kategori_grup ON kategori(id_grup);
CREATE INDEX idx_akun_dana_grup ON akun_dana(id_grup);
