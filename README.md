# SIMKAS

Aplikasi pengelolaan kas kelas menggunakan **PHP Native, PostgreSQL, CSS Native, dan JavaScript Native**.

Project ini dibuat dengan gaya coding sederhana seperti jobsheet: setiap fitur menggunakan halaman PHP sendiri, query menggunakan PDO, session digunakan untuk login, dan JavaScript hanya dipakai untuk interaksi kecil di frontend.

## Struktur

```text
SIMKAS/
├── auth/            login.php, register.php, lupa_password.php,
│                    reset_password.php, logout.php
├── config/          koneksi.php, mail.php, mail.local.example.php
├── cron/            bersihkan_bukti.php     (CLI saja)
├── database/        01_schema.sql, 02_seed_kategori.sql,
│                    03_reset_password.sql, 04_indeks.sql, 05_info_pembayaran.sql,
│                    06_batas_login.sql, 07_sumber_dana.sql
├── includes/        auth.php        (bootstrap: sesi + koneksi + helper + guard login)
│                    header.php, sidebar.php, footer.php   (kerangka halaman)
│                    auth_head.php, auth_foot.php          (kerangka halaman auth)
│                    helpers.php     (fungsi umum, peran, grup, menu)
│                    bukti.php       (upload & pembersihan bukti pembayaran)
│                    tagihan.php     (label status, info tenggat, penagihan anggota)
│                    reset_password.php (token reset + email Brevo)
├── grup/            index.php, detail.php, edit.php, hapus.php,
│                    _daftar_grup.php, _form_grup.php
├── anggota/         index.php            (daftar anggota + ubah role)
├── profil/          index.php            (data akun, password, grup saya)
├── kewajiban/       index.php, tambah.php, edit.php, hapus.php,
│                    bayar.php, detail.php, _form.php  (Tagihan)
├── pembayaran/      index.php            (Verifikasi)
├── transaksi/       index.php, tambah.php, edit.php, _form.php, _data.php
├── laporan/         index.php
├── assets/
│   ├── css/         style, layout, sidebar, nav, rail, inputs, form, toast,
│   │                print, auth, dashboard, table, grup, anggota, kewajiban,
│   │                profil, transaksi*
│   └── js/          toast.js, sidebar.js, script.js (semua halaman)
│                    form.js + kewajiban.js / transaksi.js (khusus form)
├── index.php        (Dashboard)
└── storage/uploads/
```

## Cara menjalankan

1. Buat database PostgreSQL, misalnya `simkas`.
2. Jalankan `database/01_schema.sql` pada database tersebut. Jika database sudah ada, jalankan juga `database/03_reset_password.sql`, `database/04_indeks.sql`, `database/05_info_pembayaran.sql`, `database/06_batas_login.sql`, dan `database/07_sumber_dana.sql` (semuanya aman dijalankan ulang).
3. Buka `config/koneksi.php`, lalu sesuaikan username dan password PostgreSQL.
4. Jalankan project dari folder `SIMKAS` dengan Apache/XAMPP/Laragon atau PHP built-in server.
5. Buka `auth/register.php` untuk membuat akun.
6. Login melalui `auth/login.php`.
7. Buat grup baru. Pembuat grup otomatis menjadi Admin.
8. Grup otomatis memiliki kategori dasar dan tiga sumber dana: `Kas Tunai`, `Rekening Bank`, dan `E-Wallet`.

## Alur fitur

- **Admin**: membuat grup dan mengatur role anggota.
- **Bendahara**: membuat kewajiban, memverifikasi pembayaran, dan mencatat transaksi.
- **Anggota**: melihat kewajiban dan mengirim pembayaran. Anggota yang baru gabung otomatis ikut ditagih untuk tagihan yang tenggatnya belum lewat.
- **Edit/hapus tagihan** (Admin/Bendahara): nominal terkunci setelah ada pembayaran masuk; tagihan yang sudah punya pembayaran disetujui tidak bisa dihapus.
- **Lupa password**: mengirim link reset sekali pakai melalui email Brevo.
- **Masuk/Daftar**: field password punya tombol lihat/sembunyikan.
- **Profil**: data akun dan daftar grup; tombol Edit profil & password membuka dialog verifikasi password (maksimal 5 percobaan per 10 menit) sebelum masuk halaman edit. Izin edit berakhir begitu kamu meninggalkan halaman edit dan berlaku maksimal 15 menit selama halaman itu terbuka, jadi setiap klik Edit meminta password lagi. Di halaman edit kamu bisa mengubah nama dan password (minimal 8 karakter, bisa diperiksa lewat tombol lihat). Grup aktif bisa diganti langsung dari daftar grup. Isian nama dipertahankan bila sesi berakhir saat menyimpan, dan sesi lain otomatis berakhir setelah password diganti. Email akun tampil sebagai baris terkunci dan tidak dapat diubah.
- **Dashboard**: menampilkan saldo, pemasukan, pengeluaran, jumlah anggota, dan transaksi terbaru.
- **Laporan**: menampilkan transaksi berdasarkan rentang tanggal.

## Konfigurasi email reset password

Fitur lupa password memakai API transaksional Brevo. Tanpa konfigurasi email, token reset tetap dibuat tetapi email tidak terkirim dan kegagalannya dicatat ke log PHP.

1. Salin `config/mail.local.example.php` menjadi `config/mail.local.php`.
2. Isi `brevo_api_key` dari dashboard Brevo (SMTP & API → API Keys).
3. Isi `from_email` dengan alamat pengirim yang sudah diverifikasi di Brevo.
4. Sesuaikan `app_url`, misalnya `https://domainkamu.com` tanpa trailing slash.
5. Pastikan API key memiliki akses mengirim email transaksional.

Catatan: `.gitignore` sengaja mengecualikan `config/mail.local.php` (`!config/mail.local.php`) supaya ikut ter-deploy, jadi **jangan isi API key asli di file itu sebelum di-commit** — isi langsung di server atau lewat environment variable `BREVO_API_KEY`.

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
