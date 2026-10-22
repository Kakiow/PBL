/* ==========================================================================
   SIMKAS — landing page: demo peran, verifikasi, reveal, status navigasi
   ========================================================================== */
(function () {
  'use strict';

  var NAV_BY_ROLE = { admin: 'anggota', bendahara: 'verifikasi', anggota: 'tagihan' };
  var LABEL_BY_ROLE = { admin: 'Admin', bendahara: 'Bendahara', anggota: 'Anggota' };

  var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-role]'));
  var panels = Array.prototype.slice.call(document.querySelectorAll('.lp-panel'));
  var navItems = Array.prototype.slice.call(document.querySelectorAll('[data-nav]'));
  var hint = document.querySelector('[data-hint]');
  var userRole = document.querySelector('[data-user-role]');

  function pilihPeran(role, fokus) {
    tabs.forEach(function (tab) {
      var aktif = tab.getAttribute('data-role') === role;
      tab.setAttribute('aria-selected', aktif ? 'true' : 'false');
      tab.tabIndex = aktif ? 0 : -1;
      if (aktif && fokus) {
        tab.focus();
      }
    });

    panels.forEach(function (panel) {
      panel.hidden = panel.id !== 'panel-' + role;
    });

    navItems.forEach(function (item) {
      item.classList.toggle('is-active', item.getAttribute('data-nav') === NAV_BY_ROLE[role]);
    });

    if (hint) {
      hint.textContent = LABEL_BY_ROLE[role];
    }
    if (userRole) {
      userRole.textContent = LABEL_BY_ROLE[role] + ' · XI RPL 1';
    }
  }

  tabs.forEach(function (tab, index) {
    tab.addEventListener('click', function () {
      pilihPeran(tab.getAttribute('data-role'));
    });
    tab.addEventListener('keydown', function (event) {
      if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') {
        return;
      }
      event.preventDefault();
      var arah = event.key === 'ArrowRight' ? 1 : -1;
      var berikut = tabs[(index + arah + tabs.length) % tabs.length];
      pilihPeran(berikut.getAttribute('data-role'), true);
    });
  });

  if (tabs.length) {
    pilihPeran('bendahara');
  }

  /* Demo verifikasi: setujui atau tolak satu pembayaran. */
  function angka(nilai) {
    return nilai.toLocaleString('id-ID');
  }

  function selesaikanBaris(baris, teks, kelas) {
    var aksi = baris.querySelector('.lp-qacts');
    var hasil = baris.querySelector('[data-result]');
    if (!aksi || !hasil) {
      return;
    }
    aksi.remove();
    hasil.hidden = false;
    hasil.textContent = teks;
    hasil.className = 'lp-tag ' + kelas;
    baris.classList.add(kelas === 'bad' ? 'is-rejected' : 'is-approved');
    baris.classList.toggle('is-done', true);
  }

  document.querySelectorAll('[data-approve]').forEach(function (tombol) {
    tombol.addEventListener('click', function () {
      var baris = tombol.closest('.lp-qrow');
      if (!baris) {
        return;
      }
      var jumlah = Number(baris.getAttribute('data-amount') || 0);
      selesaikanBaris(baris, 'Disetujui', 'ok');

      var saldo = document.querySelector('[data-saldo]');
      var masuk = document.querySelector('[data-masuk]');
      if (saldo) {
        saldo.textContent = angka(Number(saldo.textContent.replace(/\./g, '')) + jumlah);
      }
      if (masuk) {
        masuk.textContent = angka(Number(masuk.textContent.replace(/\./g, '')) + jumlah);
      }
      hitungUlangAntrean();
    });
  });

  document.querySelectorAll('[data-reject]').forEach(function (tombol) {
    tombol.addEventListener('click', function () {
      var baris = tombol.closest('.lp-qrow');
      if (!baris) {
        return;
      }
      selesaikanBaris(baris, 'Ditolak', 'bad');
      hitungUlangAntrean();
    });
  });

  function hitungUlangAntrean() {
    var sisa = document.querySelectorAll('.lp-qrow [data-result][hidden]').length;
    var label = document.querySelector('[data-pending]');
    if (label) {
      label.textContent = String(sisa);
    }
  }

  /* Demo anggota: tombol bayar berubah jadi status menunggu. */
  document.querySelectorAll('[data-bayar]').forEach(function (tombol) {
    tombol.addEventListener('click', function () {
      var status = document.createElement('span');
      status.className = 'lp-tag warn';
      status.textContent = 'Menunggu';
      tombol.replaceWith(status);
    });
  });

  /* Reveal section saat masuk viewport. Konten tidak pernah disembunyikan,
     jadi capture, print, dan browser tanpa IntersectionObserver tetap utuh. */
  var kurangiGerak = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var elemen = Array.prototype.slice.call(document.querySelectorAll('[data-reveal]'));

  if (!kurangiGerak && 'IntersectionObserver' in window) {
    var pengamat = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-in');
          pengamat.unobserve(entry.target);
        }
      });
    }, { threshold: 0.08, rootMargin: '0px 0px -8% 0px' });

    elemen.forEach(function (el) {
      pengamat.observe(el);
    });
  }

  /* Bayangan navigasi hanya saat halaman bergeser. */
  var nav = document.getElementById('lp-nav');
  if (nav) {
    var perbarui = function () {
      nav.classList.toggle('is-scrolled', window.scrollY > 10);
    };
    perbarui();
    window.addEventListener('scroll', perbarui, { passive: true });
  }
})();
