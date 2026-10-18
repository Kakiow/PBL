# SIMKAS

Aplikasi pengelolaan kas kelas menggunakan **PHP Native, PostgreSQL, CSS Native, dan JavaScript Native**.

Project ini dibuat dengan gaya coding sederhana seperti jobsheet: setiap fitur menggunakan halaman PHP sendiri, query menggunakan PDO, session digunakan untuk login, dan JavaScript hanya dipakai untuk interaksi kecil di frontend.

## Struktur

```text
SIMKAS/
├── auth/            login.php, register.php, logout.php
├── config/          koneksi.php
├── database/        01_schema.sql, 02_seed_kategori.sql
├── includes/        auth.php, footer.php, header.php, helpers.php
├── grup/            index.php            (buat / gabung / pilih grup)
├── anggota/         index.php            (daftar anggota + ubah role)
├── kewajiban/       index.php, detail.php  (Tagihan)
├── pembayaran/      index.php            (Verifikasi)
├── transaksi/       index.php, _form.php, tambah.php, edit.php
├── laporan/         index.php
├── assets/
│   ├── css/         style, sidebar, form, dashboard, table, grup,
│   │                anggota, kewajiban, transaksi*
│   └── js/          script.js, transaksi.js
├── index.php        (Dashboard)
└── storage/uploads/
```

## Cara menjalankan

1. Buat database PostgreSQL, misalnya `simkas`.
2. Jalankan `database/01_schema.sql` pada database tersebut.
3. Buka `config/koneksi.php`, lalu sesuaikan username dan password PostgreSQL.
4. Jalankan project dari folder `SIMKAS` dengan Apache/XAMPP/Laragon atau PHP built-in server.
5. Buka `auth/register.php` untuk membuat akun.
6. Login melalui `auth/login.php`.
7. Buat grup baru. Pembuat grup otomatis menjadi Admin.
8. Grup otomatis memiliki kategori dasar dan akun dana `Kas Tunai`.

## Alur fitur

- **Admin**: membuat grup dan mengatur role anggota.
- **Bendahara**: membuat kewajiban, memverifikasi pembayaran, dan mencatat transaksi.
- **Anggota**: melihat kewajiban dan mengirim pembayaran.
- **Dashboard**: menampilkan saldo, pemasukan, pengeluaran, jumlah anggota, dan transaksi terbaru.
- **Laporan**: menampilkan transaksi berdasarkan rentang tanggal.

## Catatan

Implementasi sengaja tidak menggunakan framework, REST API, JWT, token authentication, library UI, atau arsitektur frontend yang kompleks. Tujuannya agar struktur program mudah dipahami dan sesuai level jobsheet.
