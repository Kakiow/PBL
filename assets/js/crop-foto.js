/**
 * crop-foto.js - Pengelolaan dan Crop Lingkaran Foto Profil Ala WhatsApp (Native Vanilla)
 */
(function () {
  'use strict';

  const btnPilihFoto = document.getElementById('btn-pilih-foto');
  const inputFoto = document.getElementById('input-foto-profil');
  const dialogCrop = document.getElementById('dialog-crop-foto');
  const btnBatal = document.getElementById('btn-batal-crop');
  const btnTutup = document.getElementById('btn-tutup-crop');
  const btnSimpan = document.getElementById('btn-simpan-crop');
  const sliderZoom = document.getElementById('crop-zoom');
  const viewport = document.getElementById('crop-viewport');
  const canvas = document.getElementById('crop-canvas');
  const btnHapusFoto = document.getElementById('btn-hapus-foto');
  const avatarBox = document.getElementById('profile-avatar-box');

  if (!btnPilihFoto || !inputFoto || !dialogCrop || !canvas) {
    return;
  }

  const ctx = canvas.getContext('2d');
  const VIEW_SIZE = 280; // Ukuran kotak viewport & canvas interaktif
  const OUTPUT_SIZE = 400; // Ukuran berkas keluaran tersimpan
  const MAKS_BYTES = 5 * 1024 * 1024; // 5 MB

  let img = new Image();
  let baseScale = 1;
  let zoom = 1;
  let offsetX = 0;
  let offsetY = 0;
  let isDragging = false;
  let startX = 0;
  let startY = 0;
  let initialOffsetX = 0;
  let initialOffsetY = 0;
  let csrfToken = '';

  // Ambil token CSRF dari meta atau input yang ada di halaman
  const csrfInput = document.querySelector('input[name="csrf_token"]');
  if (csrfInput) {
    csrfToken = csrfInput.value;
  }

  btnPilihFoto.addEventListener('click', function () {
    inputFoto.value = '';
    inputFoto.click();
  });

  inputFoto.addEventListener('change', function () {
    const file = this.files && this.files[0];
    if (!file) return;

    if (file.size > MAKS_BYTES) {
      if (typeof window.tampilToast === 'function') {
        window.tampilToast('Ukuran foto profil maksimal 5 MB.', 'error');
      } else {
        alert('Ukuran foto profil maksimal 5 MB.');
      }
      inputFoto.value = '';
      return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
      img = new Image();
      img.onload = function () {
        bukaDialogCrop();
      };
      img.onerror = function () {
        if (typeof window.tampilToast === 'function') {
          window.tampilToast('Berkas gambar tidak dapat dibuka.', 'error');
        }
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  });

  function bukaDialogCrop() {
    // Hitung skala dasar agar gambar selalu menutupi area 280x280
    baseScale = Math.max(VIEW_SIZE / img.width, VIEW_SIZE / img.height);
    zoom = 1;
    sliderZoom.value = '1';
    offsetX = 0;
    offsetY = 0;

    canvas.width = VIEW_SIZE;
    canvas.height = VIEW_SIZE;

    gambarCanvas();

    if (typeof dialogCrop.showModal === 'function') {
      dialogCrop.showModal();
    } else {
      dialogCrop.setAttribute('open', '');
    }
  }

  function tutupDialogCrop() {
    if (typeof dialogCrop.close === 'function') {
      dialogCrop.close();
    } else {
      dialogCrop.removeAttribute('open');
    }
    inputFoto.value = '';
  }

  if (btnBatal) btnBatal.addEventListener('click', tutupDialogCrop);
  if (btnTutup) btnTutup.addEventListener('click', tutupDialogCrop);

  // Batasi agar gambar tidak tergeser keluar dari batas viewport
  function batasiOffset() {
    const currentScale = baseScale * zoom;
    const currentW = img.width * currentScale;
    const currentH = img.height * currentScale;

    const maxOffsetX = (currentW - VIEW_SIZE) / 2;
    const maxOffsetY = (currentH - VIEW_SIZE) / 2;

    offsetX = Math.max(-maxOffsetX, Math.min(maxOffsetX, offsetX));
    offsetY = Math.max(-maxOffsetY, Math.min(maxOffsetY, offsetY));
  }

  function gambarCanvas() {
    batasiOffset();
    ctx.clearRect(0, 0, VIEW_SIZE, VIEW_SIZE);

    const currentScale = baseScale * zoom;
    const drawW = img.width * currentScale;
    const drawH = img.height * currentScale;

    const drawX = (VIEW_SIZE - drawW) / 2 + offsetX;
    const drawY = (VIEW_SIZE - drawH) / 2 + offsetY;

    ctx.drawImage(img, drawX, drawY, drawW, drawH);
  }

  // Kontrol zoom slider
  sliderZoom.addEventListener('input', function () {
    zoom = parseFloat(this.value);
    gambarCanvas();
  });

  // Kontrol mouse wheel zoom pada viewport
  viewport.addEventListener('wheel', function (e) {
    e.preventDefault();
    const delta = e.deltaY > 0 ? -0.1 : 0.1;
    zoom = Math.max(1, Math.min(3, zoom + delta));
    sliderZoom.value = zoom.toString();
    gambarCanvas();
  }, { passive: false });

  // Kontrol pointer (drag / pan) untuk mouse & touch
  viewport.addEventListener('pointerdown', function (e) {
    isDragging = true;
    startX = e.clientX;
    startY = e.clientY;
    initialOffsetX = offsetX;
    initialOffsetY = offsetY;
    viewport.setPointerCapture(e.pointerId);
  });

  viewport.addEventListener('pointermove', function (e) {
    if (!isDragging) return;
    const dx = e.clientX - startX;
    const dy = e.clientY - startY;
    offsetX = initialOffsetX + dx;
    offsetY = initialOffsetY + dy;
    gambarCanvas();
  });

  function stopDrag(e) {
    if (!isDragging) return;
    isDragging = false;
    try {
      viewport.releasePointerCapture(e.pointerId);
    } catch (_) {}
  }

  viewport.addEventListener('pointerup', stopDrag);
  viewport.addEventListener('pointercancel', stopDrag);

  // Simpan hasil crop
  btnSimpan.addEventListener('click', function () {
    btnSimpan.disabled = true;
    const btnText = btnSimpan.querySelector('.btn-text');
    const btnSpinner = btnSimpan.querySelector('.btn-spinner');
    if (btnText) btnText.style.display = 'none';
    if (btnSpinner) btnSpinner.style.display = 'inline';

    // Buat canvas hasil ekspor 400x400 px
    const outCanvas = document.createElement('canvas');
    outCanvas.width = OUTPUT_SIZE;
    outCanvas.height = OUTPUT_SIZE;
    const outCtx = outCanvas.getContext('2d');

    const rasioEkspor = OUTPUT_SIZE / VIEW_SIZE;
    const currentScale = baseScale * zoom * rasioEkspor;
    const drawW = img.width * currentScale;
    const drawH = img.height * currentScale;
    const drawX = (OUTPUT_SIZE - drawW) / 2 + offsetX * rasioEkspor;
    const drawY = (OUTPUT_SIZE - drawH) / 2 + offsetY * rasioEkspor;

    outCtx.drawImage(img, drawX, drawY, drawW, drawH);

    outCanvas.toBlob(function (blob) {
      if (!blob) {
        kembalikanTombol();
        if (typeof window.tampilToast === 'function') {
          window.tampilToast('Gagal memproses gambar.', 'error');
        }
        return;
      }

      const formData = new FormData();
      formData.append('aksi', 'simpan_foto');
      formData.append('foto', blob, 'profil.jpg');
      formData.append('csrf_token', csrfToken);

      fetch('foto.php', {
        method: 'POST',
        body: formData,
      })
        .then(function (res) {
          return res.json();
        })
        .then(function (data) {
          kembalikanTombol();
          if (data && data.sukses) {
            tutupDialogCrop();
            perbaruiTampilanAvatar(data.url);
            if (typeof window.tampilToast === 'function') {
              window.tampilToast(data.pesan || 'Foto profil berhasil diperbarui.', 'success');
            }
          } else {
            const err = (data && data.pesan) || 'Gagal menyimpan foto profil.';
            if (typeof window.tampilToast === 'function') {
              window.tampilToast(err, 'error');
            } else {
              alert(err);
            }
          }
        })
        .catch(function () {
          kembalikanTombol();
          if (typeof window.tampilToast === 'function') {
            window.tampilToast('Terjadi kesalahan jaringan.', 'error');
          }
        });
    }, 'image/jpeg', 0.92);
  });

  function kembalikanTombol() {
    btnSimpan.disabled = false;
    const btnText = btnSimpan.querySelector('.btn-text');
    const btnSpinner = btnSimpan.querySelector('.btn-spinner');
    if (btnText) btnText.style.display = 'inline';
    if (btnSpinner) btnSpinner.style.display = 'none';
  }

  function perbaruiTampilanAvatar(urlFoto) {
    if (avatarBox) {
      avatarBox.innerHTML = '<img class="avatar-img profile-avatar" src="' + urlFoto + '" alt="Foto profil">';
    }
    if (btnHapusFoto) {
      btnHapusFoto.style.display = 'inline-flex';
    }

    // Perbarui juga avatar di sidebar
    const sidebarAvatar = document.querySelector('.sidebar-foot .avatar, .sidebar-foot .avatar-img');
    if (sidebarAvatar) {
      if (sidebarAvatar.tagName.toLowerCase() === 'img') {
        sidebarAvatar.src = urlFoto;
      } else {
        const imgEl = document.createElement('img');
        imgEl.className = 'avatar-img avatar';
        imgEl.src = urlFoto;
        imgEl.alt = 'Foto profil';
        sidebarAvatar.parentNode.replaceChild(imgEl, sidebarAvatar);
      }
    }
  }

  // Tombol Hapus Foto
  if (btnHapusFoto) {
    btnHapusFoto.addEventListener('click', function () {
      if (!confirm('Yakin ingin menghapus foto profil dan kembali menggunakan inisial nama?')) {
        return;
      }

      btnHapusFoto.disabled = true;

      const formData = new FormData();
      formData.append('aksi', 'hapus_foto');
      formData.append('csrf_token', csrfToken);

      fetch('foto.php', {
        method: 'POST',
        body: formData,
      })
        .then(function (res) {
          return res.json();
        })
        .then(function (data) {
          btnHapusFoto.disabled = false;
          if (data && data.sukses) {
            btnHapusFoto.style.display = 'none';

            // Ambil inisial nama dari teks judul profil jika ada
            const namaEl = document.getElementById('profil-nama');
            const nama = namaEl ? namaEl.textContent.trim() : 'U';
            const inisial = (nama.charAt(0) || 'U').toUpperCase();

            if (avatarBox) {
              avatarBox.innerHTML = '<span class="avatar profile-avatar" aria-hidden="true">' + inisial + '</span>';
            }

            const sidebarAvatar = document.querySelector('.sidebar-foot .avatar, .sidebar-foot .avatar-img');
            if (sidebarAvatar) {
              const spanEl = document.createElement('span');
              spanEl.className = 'avatar';
              spanEl.setAttribute('aria-hidden', 'true');
              spanEl.textContent = inisial;
              sidebarAvatar.parentNode.replaceChild(spanEl, sidebarAvatar);
            }

            if (typeof window.tampilToast === 'function') {
              window.tampilToast(data.pesan || 'Foto profil berhasil dihapus.', 'success');
            }
          } else {
            const err = (data && data.pesan) || 'Gagal menghapus foto profil.';
            if (typeof window.tampilToast === 'function') {
              window.tampilToast(err, 'error');
            } else {
              alert(err);
            }
          }
        })
        .catch(function () {
          btnHapusFoto.disabled = false;
          if (typeof window.tampilToast === 'function') {
            window.tampilToast('Terjadi kesalahan jaringan.', 'error');
          }
        });
    });
  }
})();
