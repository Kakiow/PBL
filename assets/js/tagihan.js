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
  const fieldSasaran = form.querySelector("[data-sasaran-field]");
  const pickerSasaran = form.querySelector("[data-sasaran-picker]");
  const radioMode = form.querySelectorAll("[data-mode-sasaran]");
  const kotakSasaran = form.querySelectorAll('input[name="sasaran[]"]');
  const tombolSasaranSemua = form.querySelectorAll("[data-sasaran-all]");
  const tombolSasaranKosong = form.querySelectorAll("[data-sasaran-none]");
  const hitungSasaran = form.querySelector("[data-sasaran-count]");
  const errorSasaran = form.querySelector("[data-sasaran-error]");

  function modeSasaranAktif() {
    const radio = form.querySelector("[data-mode-sasaran]:checked");
    return radio ? radio.value : "semua";
  }

  function jumlahSasaranDipilih() {
    return Array.from(kotakSasaran).filter(function (kotak) {
      return kotak.checked;
    }).length;
  }

  function perbaruiSasaran() {
    if (!fieldSasaran) return;

    const pilih = modeSasaranAktif() === "pilih";
    const jumlah = jumlahSasaranDipilih();

    if (pickerSasaran) pickerSasaran.classList.toggle("is-disabled", !pilih);
    kotakSasaran.forEach(function (kotak) {
      kotak.disabled = !pilih;
    });
    tombolSasaranSemua.forEach(function (tombol) {
      tombol.disabled = !pilih;
    });
    tombolSasaranKosong.forEach(function (tombol) {
      tombol.disabled = !pilih;
    });

    if (hitungSasaran) {
      hitungSasaran.textContent = pilih
        ? jumlah + " dari " + kotakSasaran.length + " anggota dipilih"
        : "Semua " + kotakSasaran.length + " anggota akan ditagih";
    }

    if (errorSasaran && (!pilih || jumlah > 0)) errorSasaran.hidden = true;
  }

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

  radioMode.forEach(function (radio) {
    radio.addEventListener("change", perbaruiSasaran);
  });

  tombolSasaranSemua.forEach(function (tombol) {
    tombol.addEventListener("click", function () {
      kotakSasaran.forEach(function (kotak) {
        kotak.checked = true;
      });
      perbaruiSasaran();
    });
  });

  tombolSasaranKosong.forEach(function (tombol) {
    tombol.addEventListener("click", function () {
      kotakSasaran.forEach(function (kotak) {
        kotak.checked = false;
      });
      perbaruiSasaran();
    });
  });

  kotakSasaran.forEach(function (kotak) {
    kotak.addEventListener("change", perbaruiSasaran);
  });

  if (fieldSasaran) perbaruiSasaran();

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

  form.addEventListener("submit", function (event) {
    if (fieldSasaran && modeSasaranAktif() === "pilih" && jumlahSasaranDipilih() === 0) {
      event.preventDefault();
      if (errorSasaran) {
        errorSasaran.hidden = false;
        if (kotakSasaran.length) kotakSasaran[0].focus();
      }
      return;
    }

    if (!form.checkValidity()) return;
    F.tandaiMenyimpan(tombolKirim, statusForm, "Menyimpan tagihan…");
  });
});
