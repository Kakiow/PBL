// Interaksi form Buat Tagihan (tagihan/tambah.php). Butuh form.js.
document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector(".form-tagihan");
  if (!form || !window.FormKas) return;

  const F = window.FormKas;
  const inputJumlah = form.querySelector("#jumlah");
  const previewJumlah = form.querySelector("#jumlah-preview");
  const inputTenggat = form.querySelector("#tenggat_waktu");
  const previewTenggat = form.querySelector("#tenggat-preview");
  const tombolKirim = form.querySelector("button[type='submit']");
  const statusForm = form.querySelector(".form-status");

  function perbaruiJumlah() {
    if (!inputJumlah || !previewJumlah) return;

    const nilai = parseFloat(inputJumlah.value);

    if (nilai > 0) {
      previewJumlah.textContent = "Tagihan per anggota: " + F.formatRupiah(nilai);
      previewJumlah.classList.add("is-filled");
    } else {
      previewJumlah.textContent = "Pilih nominal cepat atau isi manual.";
      previewJumlah.classList.remove("is-filled");
    }

    const aktif = F.chipAktif(inputJumlah);
    if (aktif && parseFloat(aktif.dataset.nominal) !== nilai) aktif.classList.remove("is-active");
  }

  function perbaruiTenggat() {
    if (!inputTenggat || !previewTenggat) return;

    const panjang = F.tanggalPanjang(inputTenggat.value);
    previewTenggat.textContent = panjang || "Tanggal terakhir pembayaran.";
    previewTenggat.classList.toggle("is-filled", Boolean(panjang));
  }

  F.pasangValidasi(form);
  F.pasangCounter(form.querySelector("#deskripsi"), form.querySelector("#deskripsi-counter"));

  form.querySelectorAll(".chip-btn[data-nominal]").forEach(function (chip) {
    chip.addEventListener("click", function () {
      if (!inputJumlah) return;
      inputJumlah.value = chip.dataset.nominal;
      F.tandaiChip(chip);
      perbaruiJumlah();
      F.aturPesanError(inputJumlah, null);
    });
  });

  form.querySelectorAll(".chip-btn[data-tenggat]").forEach(function (chip) {
    chip.addEventListener("click", function () {
      if (!inputTenggat) return;
      inputTenggat.value = F.tanggalPintasan(chip.dataset.tenggat);
      F.tandaiChip(chip);
      perbaruiTenggat();
      F.aturPesanError(inputTenggat, null);
    });
  });

  if (inputJumlah) {
    inputJumlah.addEventListener("input", perbaruiJumlah);
    perbaruiJumlah();
  }

  if (inputTenggat) {
    inputTenggat.addEventListener("input", function () {
      const aktif = F.chipAktif(inputTenggat);
      if (aktif) aktif.classList.remove("is-active");
      perbaruiTenggat();
    });
    perbaruiTenggat();
  }

  form.addEventListener("submit", function () {
    if (!form.checkValidity()) return;
    F.tandaiMenyimpan(tombolKirim, statusForm, "Menyimpan tagihan…");
  });
});
