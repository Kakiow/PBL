-- Skema database SIMKAS (PostgreSQL).
-- Jalankan sekali pada database kosong. Aplikasi memakai schema public.
--
-- Alur data utama:
--   pengguna ─ anggota_grup ─ grup
--   grup ─ kategori ─ transaksi
--   grup ─ tagihan ─ tagihan_anggota ─ pembayaran ─ transaksi

-- Tipe enum ------------------------------------------------------------

CREATE TYPE peran_enum AS ENUM ('Admin', 'Bendahara', 'Anggota');
CREATE TYPE jenis_kas_enum AS ENUM ('Pemasukan', 'Pengeluaran');
CREATE TYPE status_tagihan_anggota_enum AS ENUM ('Belum_Bayar', 'Menunggu', 'Lunas', 'Ditolak');
CREATE TYPE metode_pembayaran_enum AS ENUM ('Tunai', 'Transfer', 'E-Wallet', 'QRIS');
CREATE TYPE status_pembayaran_enum AS ENUM ('Menunggu', 'Disetujui', 'Ditolak');

-- Akun & sesi ----------------------------------------------------------

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

-- Pembatas percobaan login: 5 kegagalan per email + IP dalam 10 menit.
CREATE TABLE percobaan_login (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Grup & anggota -------------------------------------------------------

CREATE TABLE grup (
    id BIGSERIAL PRIMARY KEY,
    nama_grup VARCHAR(255) NOT NULL,
    kode_grup VARCHAR(50) NOT NULL UNIQUE,
    deskripsi TEXT,
    info_pembayaran TEXT,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
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

-- Kas ------------------------------------------------------------------

CREATE TABLE kategori (
    id BIGSERIAL PRIMARY KEY,
    id_grup BIGINT NOT NULL,
    nama_kategori VARCHAR(100) NOT NULL,
    jenis jenis_kas_enum NOT NULL,
    CONSTRAINT fk_kategori_grup FOREIGN KEY (id_grup) REFERENCES grup(id) ON DELETE CASCADE
);

-- Tagihan & pembayaran -------------------------------------------------

CREATE TABLE tagihan (
    id BIGSERIAL PRIMARY KEY,
    id_grup BIGINT NOT NULL,
    judul VARCHAR(255) NOT NULL,
    jumlah DECIMAL(12,2) NOT NULL CHECK (jumlah >= 0),
    tenggat_waktu DATE NOT NULL,
    deskripsi TEXT,
    sasaran_semua BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_tagihan_grup FOREIGN KEY (id_grup) REFERENCES grup(id) ON DELETE CASCADE
);

CREATE TABLE tagihan_anggota (
    id BIGSERIAL PRIMARY KEY,
    id_tagihan BIGINT NOT NULL,
    id_pengguna BIGINT NOT NULL,
    status status_tagihan_anggota_enum NOT NULL DEFAULT 'Belum_Bayar',
    CONSTRAINT fk_tagihan_anggota_tagihan FOREIGN KEY (id_tagihan) REFERENCES tagihan(id) ON DELETE CASCADE,
    CONSTRAINT fk_tagihan_anggota_pengguna FOREIGN KEY (id_pengguna) REFERENCES pengguna(id) ON DELETE CASCADE,
    CONSTRAINT uq_tagihan_anggota UNIQUE (id_tagihan, id_pengguna)
);

CREATE TABLE pembayaran (
    id BIGSERIAL PRIMARY KEY,
    id_tagihan_anggota BIGINT NOT NULL,
    jumlah DECIMAL(12,2) NOT NULL CHECK (jumlah >= 0),
    metode_pembayaran metode_pembayaran_enum NOT NULL,
    tanggal_pembayaran TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    berkas_bukti VARCHAR(255) NOT NULL,
    status status_pembayaran_enum NOT NULL DEFAULT 'Menunggu',
    diverifikasi_oleh BIGINT,
    diverifikasi_pada TIMESTAMP,
    catatan TEXT,
    CONSTRAINT fk_pembayaran_tagihan_anggota FOREIGN KEY (id_tagihan_anggota) REFERENCES tagihan_anggota(id) ON DELETE CASCADE,
    CONSTRAINT fk_pembayaran_diverifikasi_oleh FOREIGN KEY (diverifikasi_oleh) REFERENCES pengguna(id) ON DELETE SET NULL
);

CREATE TABLE transaksi (
    id BIGSERIAL PRIMARY KEY,
    id_grup BIGINT NOT NULL,
    id_pengguna BIGINT NOT NULL,
    id_kategori BIGINT NOT NULL,
    id_pembayaran BIGINT UNIQUE,
    jenis jenis_kas_enum NOT NULL,
    jumlah DECIMAL(12,2) NOT NULL CHECK (jumlah >= 0),
    tanggal_transaksi DATE NOT NULL,
    deskripsi TEXT,
    CONSTRAINT fk_transaksi_grup FOREIGN KEY (id_grup) REFERENCES grup(id) ON DELETE CASCADE,
    CONSTRAINT fk_transaksi_pengguna FOREIGN KEY (id_pengguna) REFERENCES pengguna(id) ON DELETE RESTRICT,
    CONSTRAINT fk_transaksi_kategori FOREIGN KEY (id_kategori) REFERENCES kategori(id) ON DELETE RESTRICT,
    CONSTRAINT fk_transaksi_pembayaran FOREIGN KEY (id_pembayaran) REFERENCES pembayaran(id) ON DELETE SET NULL
);

-- Indeks ---------------------------------------------------------------
-- Unique constraint sudah membuat indeks untuk kolom awalnya; indeks di
-- bawah hanya menambah yang belum tercakup.

CREATE INDEX idx_anggota_grup_pengguna ON anggota_grup(id_pengguna);
CREATE INDEX idx_kategori_grup ON kategori(id_grup);
CREATE INDEX idx_tagihan_grup ON tagihan(id_grup);
CREATE INDEX idx_tagihan_anggota_pengguna ON tagihan_anggota(id_pengguna);
CREATE INDEX idx_pembayaran_tagihan_anggota ON pembayaran(id_tagihan_anggota);
CREATE INDEX idx_pembayaran_status ON pembayaran(status);
CREATE INDEX idx_transaksi_grup_tanggal ON transaksi(id_grup, tanggal_transaksi);
CREATE INDEX idx_reset_password_pengguna ON reset_password(id_pengguna);
CREATE INDEX idx_reset_password_kedaluwarsa ON reset_password(kedaluwarsa_pada);
CREATE INDEX idx_percobaan_login_email_ip ON percobaan_login(email, ip, dibuat_pada);
CREATE INDEX idx_percobaan_login_dibuat ON percobaan_login(dibuat_pada);
