# Pratinjau Modal Bukti Pembayaran Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

Mengubah kolom bukti pada tabel verifikasi pembayaran (`pembayaran/index.php`) agar tidak lagi menampilkan teks "Lihat bukti", menjadikan thumbnail gambar/dokumen dapat diklik langsung, dan menampilkan pratinjau bukti dalam modal pop-up (`<dialog>`) di halaman yang sama tanpa membuka tab baru.

---

### File Structure Map
- **Modifikasi `pembayaran/index.php`**:
  - Hapus teks "Lihat bukti" dari fungsi `$tampil_bukti`.
  - Pasang atribut data pratinjau (`data-proof-url`, `data-proof-type`, `data-proof-title`) dan class `proof-trigger`.
  - Tambahkan markup `<dialog id="modal-bukti" class="proof-dialog">` di bagian bawah sebelum include footer.
- **Modifikasi `assets/css/verifikasi.css`**:
  - Perbarui style `.proof-trigger` (cursor zoom/pointer, hover effect, transisi halus).
  - Tambahkan aturan styling untuk `.proof-dialog`, `.proof-dialog-card`, `.proof-dialog-head`, `.proof-dialog-body`, `.proof-dialog-img`, dan `.proof-dialog-pdf`.
- **Modifikasi `assets/js/verifikasi.js`**:
  - Tambahkan logika event listener untuk membuka modal pratinjau bukti saat `.proof-trigger` diklik.
  - Set konten gambar atau iframe PDF secara dinamis.
  - Tangani penutupan modal via tombol ✕, klik backdrop, atau tombol ESC, serta reset sumber media saat modal ditutup.
- **Pembaruan `AGENTS.md`**:
  - Catat perubahan fungsionalitas pratinjau bukti di halaman Verifikasi.

---

## Tasks

### Task 1: Perbarui Render Kolom Bukti & Markup Modal di `pembayaran/index.php`
- [x] Buka `pembayaran/index.php`.
- [x] Ubah closure `$tampil_bukti` agar tidak merender teks `<span>Lihat bukti</span>`.
- [x] Sematkan atribut `data-proof-url`, `data-proof-type`, `data-proof-title`, serta elemen `<span class="sr-only">` pada link bukti.
- [x] Tambahkan elemen `<dialog id="modal-bukti" class="proof-dialog">` sebelum include footer.
- [x] Verifikasi sintaks PHP dengan `php -l pembayaran/index.php`.

### Task 2: Tambahkan Gaya Modal Pratinjau di `assets/css/verifikasi.css`
- [x] Buka `assets/css/verifikasi.css`.
- [x] Perbaiki selector thumbnail bukti agar interaktif dengan efek kursor pointer/zoom-in dan ring fokus aksesibel.
- [x] Tulis styling modal dialog `.proof-dialog` lengkap dengan backdrop blur, card terpusat, header, tombol tutup, dan tampilan gambar/PDF responsif.
- [x] Pastikan responsif pada layar kecil (mobile ≤640px).

### Task 3: Tambahkan Logika Pratinjau Interaktif di `assets/js/verifikasi.js`
- [x] Buka `assets/js/verifikasi.js`.
- [x] Tambahkan pengendali interaksi klik thumbnail bukti untuk membuka dialog pratinjau.
- [x] Tangani pemuatan gambar atau PDF dinamis ke dalam modal.
- [x] Tangani penutupan modal (tombol close, klik backdrop luar, tombol ESC) dan pembersihan sumber media (`src = ''`).
- [x] Verifikasi sintaks JavaScript dengan `node --check assets/js/verifikasi.js`.

### Task 4: Verifikasi & Dokumentasi
- [x] Jalankan verifikasi sintaks dan uji fungsionalitas komponen.
- [x] Perbarui `AGENTS.md` sesuai aturan Mandatory AGENTS.md Lifecycle.
- [x] Konfirmasi bahwa working tree bersih dan tidak ada commit/push otomatis sesuai aturan Git Safety.
