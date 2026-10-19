// Interaksi form transaksi (tambah & edit): ringkasan live, chip nominal/tanggal, validasi. Butuh form.js.
document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector(".form-transaksi");

  if (!form || !window.FormKas) return;

  const F = window.FormKas;
  const inputJumlah = form.querySelector("#jumlah");
  const previewJumlah = form.querySelector("#jumlah-preview");
  const inputTanggal = form.querySelector("#tanggal_transaksi");
  const previewTanggal = form.querySelector("#tanggal-preview");
  const inputKategori = form.querySelector("#kategori");
  const selectAkun = form.querySelector("#id_akun_dana");
  const tombolKirim = form.querySelector("button[type='submit']");
  const statusForm = form.querySelector(".form-status");
  const panel = form.querySelector(".trx-preview");
  const teksJenis = form.querySelector("#trx-preview-jenis");
  const teksKategori = form.querySelector("#trx-preview-kategori");
  const teksAkun = form.querySelector("#trx-preview-akun");
  const teksTanggal = form.querySelector("#trx-preview-tanggal");
  const teksTotal = form.querySelector("#trx-preview-amount");
  const radiosJenis = Array.from(form.querySelectorAll("input[name='jenis']"));

  function jenisTerpilih() {
    const radio = radiosJenis.find(function (item) {
      return item.checked;
    });
    return radio ? radio.value : "";
  }

  function labelTerpilih(select) {
    if (!select || !select.value) return "";
    const opsi = select.options[select.selectedIndex];
    return opsi ? opsi.textContent.trim() : "";
  }

  function nilaiKategori() {
    return inputKategori ? inputKategori.value.trim() : "";
  }

  function setTeks(el, nilai) {
    if (!el) return;
    el.textContent = nilai || "—";
    el.classList.toggle("is-empty", !nilai);
  }

  function nilaiJumlah() {
    return parseFloat(inputJumlah ? inputJumlah.value : "") || 0;
  }

  function perbaruiJenis() {
    const jenis = jenisTerpilih();
    form.dataset.jenis = jenis ? jenis.toLowerCase() : "";
    setTeks(teksJenis, jenis);
  }

  function perbaruiJumlah() {
    const nilai = nilaiJumlah();

    if (previewJumlah) {
      if (nilai > 0) {
        previewJumlah.textContent = "Nilai tercatat: " + F.formatRupiah(nilai);
        previewJumlah.classList.add("is-filled");
      } else {
        previewJumlah.textContent = "Pilih nominal cepat atau isi manual.";
        previewJumlah.classList.remove("is-filled");
      }
    }

    if (teksTotal) teksTotal.textContent = F.formatRupiah(nilai);

    const aktif = F.chipAktif(inputJumlah);
    if (aktif && parseFloat(aktif.dataset.nominal) !== nilai) aktif.classList.remove("is-active");
  }

  function perbaruiTanggal() {
    const panjang = F.tanggalPanjang(inputTanggal ? inputTanggal.value : "");

    if (previewTanggal) {
      previewTanggal.textContent = panjang || "Tanggal pencatatan transaksi.";
      previewTanggal.classList.toggle("is-filled", Boolean(panjang));
    }

    setTeks(teksTanggal, panjang);
  }

  function perbaruiRingkasan() {
    setTeks(teksKategori, nilaiKategori());
    setTeks(teksAkun, labelTerpilih(selectAkun));

    const lengkap = Boolean(
      jenisTerpilih() && nilaiKategori() && selectAkun && selectAkun.value && nilaiJumlah() > 0
    );
    if (panel) panel.classList.toggle("is-ready", lengkap);
  }

  function sinkronChip() {
    form.querySelectorAll(".chip-btn[data-nominal]").forEach(function (chip) {
      chip.classList.toggle("is-active", parseFloat(chip.dataset.nominal) === nilaiJumlah());
    });
    form.querySelectorAll(".chip-btn[data-tanggal]").forEach(function (chip) {
      chip.classList.toggle("is-active", Boolean(inputTanggal) && F.tanggalPintasan(chip.dataset.tanggal) === inputTanggal.value);
    });
  }

  F.pasangValidasi(form);
  F.pasangCounter(form.querySelector("#deskripsi"), form.querySelector("#deskripsi-counter"));

  radiosJenis.forEach(function (radio) {
    radio.addEventListener("change", function () {
      F.aturPesanError(radio, null);
      perbaruiJenis();
      perbaruiJumlah();
      perbaruiRingkasan();
    });
  });

  form.querySelectorAll(".chip-btn[data-nominal]").forEach(function (chip) {
    chip.addEventListener("click", function () {
      if (!inputJumlah) return;
      inputJumlah.value = chip.dataset.nominal;
      F.tandaiChip(chip);
      perbaruiJumlah();
      perbaruiRingkasan();
      F.aturPesanError(inputJumlah, null);
    });
  });

  form.querySelectorAll(".chip-btn[data-tanggal]").forEach(function (chip) {
    chip.addEventListener("click", function () {
      if (!inputTanggal) return;

      inputTanggal.value = F.tanggalPintasan(chip.dataset.tanggal);
      F.tandaiChip(chip);
      perbaruiTanggal();
      F.aturPesanError(inputTanggal, null);
    });
  });

  if (inputJumlah) {
    inputJumlah.addEventListener("input", function () {
      perbaruiJumlah();
      perbaruiRingkasan();
    });
  }

  if (inputTanggal) {
    inputTanggal.addEventListener("input", function () {
      const aktif = F.chipAktif(inputTanggal);
      if (aktif) aktif.classList.remove("is-active");
      perbaruiTanggal();
    });
  }

  if (inputKategori) {
    inputKategori.addEventListener("input", perbaruiRingkasan);
  }

  if (selectAkun) {
    selectAkun.addEventListener("change", perbaruiRingkasan);
  }

  form.addEventListener("submit", function (event) {
    if (!jenisTerpilih() && radiosJenis.length) {
      F.aturPesanError(radiosJenis[0], radiosJenis[0].dataset.error);
    }

    if (!form.checkValidity()) {
      form.querySelectorAll("[data-error]").forEach(function (input) {
        if (input.type !== "radio" && !input.checkValidity()) F.aturPesanError(input, input.dataset.error);
      });
      event.preventDefault();
      return;
    }

    F.tandaiMenyimpan(tombolKirim, statusForm, "Menyimpan transaksi…");
  });

  perbaruiJenis();
  sinkronChip();
  perbaruiJumlah();
  perbaruiTanggal();
  perbaruiRingkasan();
});
