/**
 * verifikasi.js - Interaksi bubble popover penolakan pembayaran di halaman Verifikasi
 */
(function () {
  'use strict';

  // Tutup semua popover tolak yang terbuka
  function tutupSemuaReject(kecuali) {
    document.querySelectorAll('details.reject[open]').forEach(function (d) {
      if (d !== kecuali) {
        d.removeAttribute('open');
      }
    });
  }

  // Tangani saat <details class="reject"> dibuka
  document.querySelectorAll('details.reject').forEach(function (details) {
    details.addEventListener('toggle', function () {
      if (details.open) {
        tutupSemuaReject(details);
        const textarea = details.querySelector('textarea');
        if (textarea) {
          setTimeout(function () {
            textarea.focus();
          }, 60);
        }
      }
    });
  });

  // Tombol Batal & Tombol Tutup (X)
  document.addEventListener('click', function (e) {
    const tombolTutup = e.target.closest('.reject-close, .reject-cancel');
    if (tombolTutup) {
      e.preventDefault();
      const details = tombolTutup.closest('details.reject');
      if (details) {
        details.removeAttribute('open');
      }
      return;
    }

    // Klik di luar popover untuk menutup
    const detailsTerbuka = document.querySelector('details.reject[open]');
    if (detailsTerbuka && !detailsTerbuka.contains(e.target)) {
      detailsTerbuka.removeAttribute('open');
    }
  });

  // Tombol Escape menutup popover
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      const detailsTerbuka = document.querySelector('details.reject[open]');
      if (detailsTerbuka) {
        detailsTerbuka.removeAttribute('open');
      }
    }
  });
})();
