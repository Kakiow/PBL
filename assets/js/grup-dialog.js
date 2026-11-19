/**
 * grup-dialog.js - Mengendalikan modal pop-up Buat & Gabung Grup
 */
(function () {
  'use strict';

  // Buka dialog saat tombol ber-atribut data-buka-dialog ditekan
  document.querySelectorAll('[data-buka-dialog]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const targetId = btn.getAttribute('data-buka-dialog');
      const dialog = document.getElementById(targetId);
      if (dialog && typeof dialog.showModal === 'function') {
        dialog.showModal();
        const firstInput = dialog.querySelector('input:not([type="hidden"]), textarea');
        if (firstInput) {
          setTimeout(function () {
            firstInput.focus();
          }, 50);
        }
      }
    });
  });

  // Tutup dialog saat tombol data-tutup-dialog ditekan
  document.querySelectorAll('[data-tutup-dialog]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const dialog = btn.closest('dialog');
      if (dialog && typeof dialog.close === 'function') {
        dialog.close();
      }
    });
  });

  // Tutup dialog saat klik backdrop di luar card
  document.querySelectorAll('.grup-dialog').forEach(function (dialog) {
    dialog.addEventListener('click', function (e) {
      const card = dialog.querySelector('.grup-dialog-card');
      if (card && !card.contains(e.target) && typeof dialog.close === 'function') {
        dialog.close();
      }
    });
  });
})();
