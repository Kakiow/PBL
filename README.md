# SIMKAS

Aplikasi pengelolaan kas kelas menggunakan **PHP Native, PostgreSQL, CSS Native, dan JavaScript Native**.

Project ini dibuat dengan gaya coding sederhana seperti jobsheet: setiap fitur menggunakan halaman PHP sendiri, query menggunakan PDO, session digunakan untuk login, dan JavaScript hanya dipakai untuk interaksi kecil di frontend.

## Struktur

```text
SIMKAS/
├── auth/            login.php, register.php, lupa_password.php,
│                    reset_password.php, logout.php
├── config/          koneksi.php, mail.php, mail.local.example.php
├── database/        01_schema.sql, 02_seed_kategori.sql, 03_reset_password.sql
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
2. Jalankan `database/01_schema.sql` pada database tersebut. Jika database sudah ada, jalankan juga `database/03_reset_password.sql`.
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
- **Lupa password**: mengirim link reset sekali pakai melalui email Brevo.
- **Dashboard**: menampilkan saldo, pemasukan, pengeluaran, jumlah anggota, dan transaksi terbaru.
- **Laporan**: menampilkan transaksi berdasarkan rentang tanggal.

## Konfigurasi email reset password

Fitur lupa password memakai API transaksional Brevo. Tanpa konfigurasi email, token reset tetap dibuat tetapi email tidak terkirim dan kegagalannya dicatat ke log PHP.

1. Salin `config/mail.local.example.php` menjadi `config/mail.local.php`.
2. Isi `brevo_api_key` dari dashboard Brevo (SMTP & API → API Keys).
3. Isi `from_email` dengan alamat pengirim yang sudah diverifikasi di Brevo.
4. Sesuaikan `app_url`, misalnya `https://domainkamu.com` tanpa trailing slash.
5. Pastikan API key memiliki akses mengirim email transaksional.

`config/mail.local.php` diabaikan Git melalui pola `*.local.php`, jadi API key tidak ikut ter-push.

## Penyimpanan bukti pembayaran

- Bukti Transfer, E-Wallet, dan QRIS disimpan di `storage/uploads/` pada VPS, bukan di GitHub.
- Pastikan folder tersebut dapat ditulis oleh user web server, misalnya `sudo chown -R www-data:www-data storage/uploads` dan `chmod 775`.
- Ukuran maksimal bukti 5 MB. Batas PHP diatur lewat `.user.ini`; nginx juga perlu `client_max_body_size 8m;` pada blok server.
- Bukti otomatis dihapus saat tagihan sudah lewat 5 hari dari tenggat. Pembersihan berjalan sekali sehari saat aplikasi diakses, dan bisa juga dijadwalkan lewat cron:

```bash
php /path/ke/SIMKAS/cron/bersihkan_bukti.php
```

Contoh jadwal cron harian (pukul 01.00):

```text
0 1 * * * php /path/ke/SIMKAS/cron/bersihkan_bukti.php >> /path/ke/SIMKAS/storage/bersihkan-bukti.log 2>&1
```

## Catatan

Implementasi sengaja tidak menggunakan framework, REST API, JWT, token authentication, library UI, atau arsitektur frontend yang kompleks. Tujuannya agar struktur program mudah dipahami dan sesuai level jobsheet.
