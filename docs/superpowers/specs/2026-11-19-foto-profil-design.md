# Design Specification: Fitur Foto Profil Pengguna (SIMKAS)

- **Tanggal:** 2026-11-19
- **Status:** Menunggu Persetujuan
- **Topik:** Unggah, Crop Lingkaran Ala WhatsApp, dan Tampilan Foto Profil Pengguna

---

## 1. Latar Belakang & Tujuan (The Why)
Saat ini seluruh representasi visual pengguna di aplikasi SIMKAS hanya berupa **satu inisial huruf nama** (contoh: "I" untuk Ibni). Penambahan foto profil memberikan identitas visual nyata bagi pengguna, memperkuat rasa kepemilikan akun, dan memudahkan anggota kelas mengenali satu sama lain pada daftar anggota grup.

## 2. Tujuan (Goals), Non-Goals & Anti-Goals
- **Goals:**
  - Pengguna dapat mengunggah foto profil langsung dari kartu profil di `profil/index.php` **tanpa perlu memasukkan password**.
  - Pengguna dapat menyesuaikan posisi dan perbesaran foto menggunakan modal **crop lingkaran interaktif (ala WhatsApp)** sebelum disimpan.
  - Batas maksimal ukuran berkas foto yang dipilih adalah **5 MB**.
  - Foto profil ditampilkan secara konsisten di:
    - Kartu Profil ([profil/index.php](file:///Users/ibennn/Developer/Project/PBL/profil/index.php))
    - Menu Navigasi Bawah / User Card Sidebar ([includes/sidebar.php](file:///Users/ibennn/Developer/Project/PBL/includes/sidebar.php))
    - Daftar Anggota Kelas ([anggota/index.php](file:///Users/ibennn/Developer/Project/PBL/anggota/index.php))
  - Pengguna dapat **menghapus foto profil** kapan saja untuk kembali ke inisial nama.
  - Bila foto profil belum diatur atau gagal dimuat, sistem menampilkan inisial nama secara anggun (*graceful fallback*).
- **Non-Goals:**
  - Crop foto di halaman registrasi akun (registrasi tetap cepat dengan nama & email saja).
  - Filter warna atau stiker/efek foto berlebihan.
- **Anti-Goals:**
  - Memaksa pengguna memasukkan kata sandi saat hanya ingin mengubah foto profil.
  - Bergantung pada library pihak ketiga (CDN) yang dapat membuat aplikasi lambat atau bermasalah saat offline/koneksi lambat.
  - Menyimpan foto mentah berukuran besar (>5MB) langsung di disk server tanpa kompresi/crop.

---

## 3. Pendekatan yang Dipertimbangkan (Approaches)
1. **Opsi 1 (Dipilih): Vanilla JS Native Canvas Modal**
   - Menggunakan `<dialog>` HTML5 dan native `<canvas>` di browser. Gambar dipotong menjadi persegi 1:1 (400×400 px) di client-side lalu dikirim via AJAX (`FormData`).
   - *Alasan Dipilih:* Sesuai filosofi SIMKAS yang native vanilla, bebas dependensi CDN eksternal, hemat bandwidth karena berkas yang dikirim ke server sudah terpotong rapi, dan responsif.
2. **Opsi 2: Cropper.js via CDN**
   - *Ditolak:* Bergantung pada koneksi internet untuk mengunduh script vendor eksternal, dan bentuk crop default berbentuk kotak sehingga butuh styling rumit untuk lingkaran.
3. **Opsi 3: Server-side Image Processing (PHP GD/Imagick)**
   - *Ditolak:* Memboroskan bandwidth (mengunggah file mentah besar), dan preview lingkaran tetap butuh interaksi frontend di browser.

---

## 4. Arsitektur & Spesifikasi Teknis

### A. Database Migration
Tambahkan kolom baru `foto_profil` pada tabel `pengguna`:
```sql
ALTER TABLE pengguna ADD COLUMN IF NOT EXISTS foto_profil VARCHAR(255) NULL;
```
- Tipe data: `VARCHAR(255)`, bernilai nama berkas foto (contoh: `avatar_7_a1b2c3d4e5.jpg`) atau `NULL` jika tidak memiliki foto.

### B. Penyimpanan Berkas & Direktori
- Folder penyimpanan: `storage/avatars/`
- Konfigurasi `.gitignore`: Tambahkan `storage/avatars/*` dengan pengecualian `!storage/avatars/.gitkeep`.
- Nginx pada server/OrbStack sudah memiliki konfigurasi:
  ```nginx
  location ^~ /storage/ {
      autoindex off;
      location ~ \.php$ { return 403; }
  }
  ```
  Sehingga berkas di `storage/avatars/` langsung disajikan secara statis dan aman.

### C. Helper Tampilan Global (`includes/helpers.php`)
Tambahkan fungsi reusable:
```php
function avatar_pengguna($nama, $foto = null, $kelas = '', $opsi = [])
```
- Menghasilkan markup `<img class="avatar-img {$kelas}" src="..." alt="...">` jika `$foto` ada dan berkasnya tersedia, atau `<span class="avatar {$kelas}">{$inisial}</span>` jika tidak ada foto.
- Digunakan secara seragam di:
  1. `profil/index.php` (avatar hero ukuran 84px)
  2. `includes/sidebar.php` (avatar user-card ukuran 40px)
  3. `anggota/index.php` (avatar baris tabel ukuran 32px)

### D. Endpoint Pengelolaan Foto (`profil/foto.php`)
Endpoint AJAX khusus untuk aksi foto profil:
1. **POST `aksi=simpan_foto`**:
   - Memvalidasi sesi login & token CSRF.
   - Menerima berkas upload via `$_FILES['foto']`.
   - Validasi ukuran berkas (maksimal 5 MB).
   - Validasi MIME type asli menggunakan `finfo` (hanya `image/jpeg`, `image/png`, `image/webp`).
   - Buat nama berkas acak aman: `avatar_<id>_<random>.jpg`.
   - Pindahkan berkas ke `storage/avatars/`.
   - Hapus berkas foto lama milik user jika ada.
   - Update tabel `pengguna` (`foto_profil = :foto`) dan perbarui `$_SESSION['user']['foto_profil']`.
   - Kembalikan respon JSON: `{"sukses": true, "pesan": "Foto profil berhasil diperbarui.", "foto_url": "..."}`.
2. **POST `aksi=hapus_foto`**:
   - Memvalidasi sesi login & token CSRF.
   - Hapus berkas foto fisik dari `storage/avatars/`.
   - Update tabel `pengguna` (`foto_profil = NULL`) dan set `$_SESSION['user']['foto_profil'] = null`.
   - Kembalikan respon JSON: `{"sukses": true, "pesan": "Foto profil berhasil dihapus."}`.

### E. Frontend Crop Lingkaran Ala WhatsApp
1. **Komponen Markup (`<dialog id="dialog-crop-foto">`)**:
   - Judul: "Sesuaikan Foto Profil" + tombol tutup `[✕]`.
   - Area Pratinjau (*Viewport*):
     - Wadah rasio 1:1 dengan lingkaran transparan di tengah dan latar redup transparan di luar lingkaran.
     - Gambar dapat digeser (*drag / pan*) menggunakan mouse atau sentuhan (*touch*).
   - Kontrol Zoom:
     - Slider perbesaran (1× hingga 3×) dengan ikon kaca pembesar / zoom.
   - Aksi:
     - Tombol "Batal" (menutup dialog tanpa menyimpan).
     - Tombol "Gunakan Foto" (mengeksekusi pemotongan dan upload).
2. **Skrip Interaksi (`assets/js/crop-foto.js`)**:
   - Menangani `change` pada `<input type="file" id="input-foto" accept="image/jpeg,image/png,image/webp">`.
   - Mengecek ukuran berkas awal (jika > 5 MB, tampilkan peringatan error).
   - Membaca berkas gambar menggunakan `FileReader`.
   - Menghitung koordinat transformasi dan menggambar hasil crop ke elemen `<canvas width="400" height="400">`.
   - Mengonversi canvas ke `Blob` (kualitas JPEG 0.90) dan mengirimkannya via `fetch()` ke `profil/foto.php`.
   - Menampilkan animasi loading pada tombol selama proses simpan berlangsung.
   - Memperbarui avatar di halaman seketika setelah berhasil.

### F. Integrasi Daftar Anggota (`anggota/index.php`)
- Ubah query SQL pengambilan anggota grup untuk menyertakan `p.foto_profil`:
  ```sql
  SELECT ag.id, ag.id_pengguna, ag.peran, ag.bergabung_pada, p.nama, p.email, p.foto_profil
  FROM anggota_grup ag
  JOIN pengguna p ON p.id = ag.id_pengguna
  WHERE ag.id_grup = ?
  ORDER BY p.nama
  ```
- Di kolom `cell-title`, tampilkan avatar kecil di samping nama anggota:
  ```html
  <div class="user-cell">
      <?php echo avatar_pengguna($a['nama'], $a['foto_profil'], 'avatar-sm'); ?>
      <span class="user-cell-name"><?php echo e($a['nama']); ?></span>
  </div>
  ```

---

## 5. Keamanan & Penanganan Kasus Khusus (*Edge Cases*)
- **Validasi Keamanan File:**
  - Hanya ekstensi yang diizinkan (`jpg`, `jpeg`, `png`, `webp`).
  - Verifikasi konten MIME biner via `finfo_file` (bukan hanya percaya nama ekstensi).
  - Hak akses file di server: direktori `0775`, file `0664`.
- **Pembersihan Berkas Lawas:**
  - Saat pengguna mengunggah foto baru, berkas foto lamanya langsung dihapus dari disk agar penyimpanan server tidak menumpuk.
- **CSRF Protection:**
  - Request AJAX upload maupun hapus wajib menyertakan token CSRF yang valid.
- **Batal atau File Rusak:**
  - Jika file gambar tidak dapat didekode oleh browser, tampilkan pesan error dan bersihkan input file.
