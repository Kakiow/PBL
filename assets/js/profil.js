// Hitung mundur sesi edit. Patokan waktunya sisa detik dari server saat halaman
// dimuat, supaya jam perangkat yang meleset tidak menggeser batas verifikasi.
(function () {
  const box = document.querySelector("[data-edit-session]");
  if (!box) return;

  const countdown = box.querySelector("[data-edit-countdown]");
  const warning = box.querySelector("[data-edit-warning]");
  const sisa = parseInt(box.dataset.sisa, 10);
  if (!countdown || !warning || !Number.isFinite(sisa)) return;

  const berakhir = Date.now() + Math.max(0, sisa) * 1000;
  let timer = null;

  const duaAngka = (n) => String(n).padStart(2, "0");

  function perbarui() {
    const sisaDetik = Math.max(0, Math.ceil((berakhir - Date.now()) / 1000));
    countdown.textContent =
      duaAngka(Math.floor(sisaDetik / 60)) + ":" + duaAngka(sisaDetik % 60);

    if (sisaDetik === 0) {
      box.classList.add("is-expired");
      warning.hidden = false;
      if (timer !== null) window.clearInterval(timer);
    }
  }

  perbarui();
  if (!box.classList.contains("is-expired")) {
    timer = window.setInterval(perbarui, 1000);
  }
})();
