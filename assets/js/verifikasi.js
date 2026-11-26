/**
 * verifikasi.js - Interaksi halaman Verifikasi:
 * 1. Bubble popover penolakan pembayaran
 * 2. Pratinjau modal lightbox bukti pembayaran
 */
(function () {
  'use strict';

  // --- 1. Popover Penolakan Pembayaran ---

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

  // Tombol Batal & Tombol Tutup (X) pada popover tolak
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

  // Tombol Escape menutup popover tolak
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      const detailsTerbuka = document.querySelector('details.reject[open]');
      if (detailsTerbuka) {
        detailsTerbuka.removeAttribute('open');
      }
    }
  });

  // --- 2. Modal Pratinjau Bukti Pembayaran ---

  const dialogBukti = document.getElementById('modal-bukti');
  const imgBukti = document.getElementById('modal-bukti-img');
  const pdfBukti = document.getElementById('modal-bukti-pdf');
  const titleBukti = document.getElementById('modal-bukti-title');
  const linkBukti = document.getElementById('modal-bukti-link');

  function bukaPratinjauBukti(trigger) {
    if (!dialogBukti) return;
    const url = trigger.getAttribute('data-proof-url') || trigger.getAttribute('href');
    const tipe = trigger.getAttribute('data-proof-type') || 'image';
    const judul = trigger.getAttribute('data-proof-title') || 'Bukti pembayaran';

    if (titleBukti) titleBukti.textContent = judul;
    if (linkBukti) linkBukti.href = url;

    if (tipe === 'pdf') {
      if (imgBukti) {
        imgBukti.style.display = 'none';
        imgBukti.src = '';
      }
      if (pdfBukti) {
        pdfBukti.style.display = 'block';
        pdfBukti.src = url;
      }
    } else {
      if (pdfBukti) {
        pdfBukti.style.display = 'none';
        pdfBukti.src = '';
      }
      if (imgBukti) {
        imgBukti.style.display = 'block';
        imgBukti.src = url;
      }
    }

    if (typeof dialogBukti.showModal === 'function') {
      dialogBukti.showModal();
    }
  }

  function tutupPratinjauBukti() {
    if (dialogBukti && typeof dialogBukti.close === 'function') {
      dialogBukti.close();
    }
  }

  // Tangani klik thumbnail bukti
  document.addEventListener('click', function (e) {
    const trigger = e.target.closest('.proof-trigger');
    if (trigger) {
      e.preventDefault();
      bukaPratinjauBukti(trigger);
    }
  });

  if (dialogBukti) {
    // Bersihkan resource saat modal ditutup
    dialogBukti.addEventListener('close', function () {
      if (imgBukti) imgBukti.src = '';
      if (pdfBukti) pdfBukti.src = '';
    });

    // Tutup jika klik backdrop di luar card
    dialogBukti.addEventListener('click', function (e) {
      const card = dialogBukti.querySelector('.proof-dialog-card');
      if (card && !card.contains(e.target)) {
        tutupPratinjauBukti();
      }
    });

    // Tombol tutup modal (X)
    const btnTutup = dialogBukti.querySelector('.proof-dialog-close');
    if (btnTutup) {
      btnTutup.addEventListener('click', function (e) {
        e.preventDefault();
        tutupPratinjauBukti();
      });
    }
  }

  // --- 3. Animasi Geser Halus Tab Menunggu <-> Riwayat ---

  const tabsNav = document.querySelector('[data-verif-tabs]');
  const viewport = document.getElementById('verif-viewport');
  const track = document.getElementById('verif-track');

  if (tabsNav && viewport && track) {
    let slideTimer = null;
    let rafId = null;

    function selesaikanSlide(targetTab) {
      if (slideTimer) {
        clearTimeout(slideTimer);
        slideTimer = null;
      }
      if (rafId) {
        cancelAnimationFrame(rafId);
        rafId = null;
      }
      viewport.classList.remove('is-sliding');
      viewport.style.height = '';
      track.style.transition = '';
      track.style.transform = '';
      track.setAttribute('data-active-tab', targetTab);
      track.querySelectorAll('.verif-panel').forEach(function (panel) {
        panel.classList.remove('is-target');
        panel.classList.toggle('is-active', panel.getAttribute('data-panel') === targetTab);
      });
    }

    tabsNav.addEventListener('click', function (e) {
      const link = e.target.closest('a[data-tab]');
      if (!link || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      e.preventDefault();

      const targetTab = link.getAttribute('data-tab');
      const currentTab = track.getAttribute('data-active-tab') || 'menunggu';
      if (targetTab === currentTab) return;

      tutupSemuaReject(null);

      tabsNav.querySelectorAll('a[data-tab]').forEach(function (a) {
        if (a === link) {
          a.setAttribute('aria-current', 'page');
        } else {
          a.removeAttribute('aria-current');
        }
      });
      if (window.history && window.history.replaceState) {
        window.history.replaceState(null, '', link.getAttribute('href'));
      }

      const reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      if (reduced) {
        selesaikanSlide(targetTab);
        return;
      }

      if (slideTimer) {
        clearTimeout(slideTimer);
        slideTimer = null;
      }
      if (rafId) {
        cancelAnimationFrame(rafId);
        rafId = null;
      }

      const startH = viewport.offsetHeight;
      const startX = currentTab === 'riwayat' ? '-50%' : '0%';
      const endX = targetTab === 'riwayat' ? '-50%' : '0%';
      const currentPanel = track.querySelector('.verif-panel[data-panel="' + currentTab + '"]');
      const targetPanel = track.querySelector('.verif-panel[data-panel="' + targetTab + '"]');

      track.style.transition = 'none';
      track.style.transform = 'translate3d(' + startX + ', 0, 0)';
      if (currentPanel) currentPanel.classList.add('is-target');
      if (targetPanel) targetPanel.classList.remove('is-target');

      viewport.style.height = startH + 'px';
      viewport.classList.add('is-sliding');

      const endH = targetPanel ? targetPanel.offsetHeight : startH;
      track.setAttribute('data-active-tab', targetTab);

      // Tunggu 1 frame agar layout panel tujuan selesai sebelum animasi dimulai
      rafId = requestAnimationFrame(function () {
        rafId = requestAnimationFrame(function () {
          track.style.transition = '';
          track.style.transform = 'translate3d(' + endX + ', 0, 0)';
          if (currentPanel) currentPanel.classList.remove('is-target');
          if (targetPanel) targetPanel.classList.add('is-target');
          if (Math.abs(endH - startH) > 2) {
            viewport.style.height = endH + 'px';
          }

          const onEnd = function (ev) {
            if (ev.target !== track || ev.propertyName !== 'transform') return;
            track.removeEventListener('transitionend', onEnd);
            selesaikanSlide(targetTab);
          };
          track.addEventListener('transitionend', onEnd);

          slideTimer = setTimeout(function () {
            track.removeEventListener('transitionend', onEnd);
            selesaikanSlide(targetTab);
          }, 420);
        });
      });
    });
  }
})();
