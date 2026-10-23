-- Pembatas percobaan login: 5 kegagalan per email + IP dalam 10 menit.
-- Aman dijalankan berulang pada database yang sudah ada.
-- Untuk instalasi baru, tabel ini juga sudah ada di 01_schema.sql.

CREATE TABLE IF NOT EXISTS percobaan_login (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_percobaan_login_email_ip ON percobaan_login(email, ip, dibuat_pada);
CREATE INDEX IF NOT EXISTS idx_percobaan_login_dibuat ON percobaan_login(dibuat_pada);
