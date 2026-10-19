-- Jalankan sekali pada database yang sudah ada.
-- Untuk instalasi baru, tabel ini juga sudah ada di 01_schema.sql.

CREATE TABLE IF NOT EXISTS reset_password (
    id BIGSERIAL PRIMARY KEY,
    id_pengguna BIGINT NOT NULL,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    kedaluwarsa_pada TIMESTAMP NOT NULL,
    digunakan_pada TIMESTAMP,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reset_password_pengguna FOREIGN KEY (id_pengguna) REFERENCES pengguna(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_reset_password_pengguna ON reset_password(id_pengguna);
CREATE INDEX IF NOT EXISTS idx_reset_password_kedaluwarsa ON reset_password(kedaluwarsa_pada);
