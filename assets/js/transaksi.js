document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector(".form-transaksi");

  if (!form) return;

  const inputJumlah = form.querySelector("#jumlah");
  const previewJumlah = form.querySelector("#jumlah-preview");
  const inputTanggal = form.querySelector("#tanggal_transaksi");
  const previewTanggal = form.querySelector("#tanggal-preview");
  const inputDeskripsi = form.querySelector("#deskripsi");
  const counterDeskripsi = form.querySelector("#deskripsi-counter");
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

  function duaAngka(angka) {
    return angka < 10 ? "0" + angka : String(angka);
  }

  function tanggalKeInput(tanggal) {
    return tanggal.getFullYear() + "-" + duaAngka(tanggal.getMonth() + 1) + "-" + duaAngka(tanggal.getDate());
  }

  function formatRupiah(nilai) {
    return "Rp " + Math.round(nilai).toLocaleString("id-ID");
  }

  function nilaiChipTanggal(pintasan) {
    const sekarang = new Date();
    const dasar =
      pintasan === "akhir-bulan"
        ? new Date(sekarang.getFullYear(), sekarang.getMonth() + 1, 0)
        : new Date(sekarang.getFullYear(), sekarang.getMonth(), sekarang.getDate() + parseInt(pintasan, 10));
    return tanggalKeInput(dasar);
  }

  function jenisTerpilih() {
    const radio = radiosJenis.find(function (item) {
      return item.checked;
    });
    return radio ? radio.value : "";
  }

  function tandaiChip(chip) {
    const baris = chip.closest(".chip-row");
    if (!baris) return;
    baris.querySelectorAll(".chip-btn").forEach(function (lain) {
      lain.classList.toggle("is-active", lain === chip);
    });
  }

  function chipAktif(input) {
    const field = input.closest(".field");
    return field ? field.querySelector(".chip-btn.is-active") : null;
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
        previewJumlah.textContent = "Nilai tercatat: " + formatRupiah(nilai);
        previewJumlah.classList.add("is-filled");
      } else {
        previewJumlah.textContent = "Pilih nominal cepat atau isi manual.";
        previewJumlah.classList.remove("is-filled");
      }
    }

    if (teksTotal) teksTotal.textContent = formatRupiah(nilai);

    const aktif = chipAktif(inputJumlah);
    if (aktif && parseFloat(aktif.dataset.nominal) !== nilai) aktif.classList.remove("is-active");
  }

  function perbaruiTanggal() {
    if (!previewTanggal) return;

    const nilai = inputTanggal ? inputTanggal.value : "";
    let panjang = "";

    if (nilai) {
      const bagian = nilai.split("-");
      panjang = new Date(Number(bagian[0]), Number(bagian[1]) - 1, Number(bagian[2])).toLocaleDateString("id-ID", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric"
      });
      previewTanggal.textContent = panjang;
      previewTanggal.classList.add("is-filled");
    } else {
      previewTanggal.textContent = "Tanggal pencatatan transaksi.";
      previewTanggal.classList.remove("is-filled");
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

  function perbaruiCounter() {
    if (!inputDeskripsi || !counterDeskripsi) return;
    const maks = parseInt(inputDeskripsi.getAttribute("maxlength"), 10) || 200;
    counterDeskripsi.textContent = inputDeskripsi.value.length + "/" + maks;
  }

  function sinkronChip() {
    form.querySelectorAll(".chip-btn[data-nominal]").forEach(function (chip) {
      chip.classList.toggle("is-active", parseFloat(chip.dataset.nominal) === nilaiJumlah());
    });
    form.querySelectorAll(".chip-btn[data-tanggal]").forEach(function (chip) {
      chip.classList.toggle("is-active", Boolean(inputTanggal) && nilaiChipTanggal(chip.dataset.tanggal) === inputTanggal.value);
    });
  }

  function aturPesanError(input, pesan) {
    const field = input.closest(".field");
    if (!field) return;

    let pesanError = field.querySelector(".field-error");

    if (pesan) {
      if (!pesanError) {
        pesanError = document.createElement("span");
        pesanError.className = "field-error";
        pesanError.setAttribute("role", "alert");
        field.appendChild(pesanError);
      }
      pesanError.textContent = pesan;
      field.classList.add("is-invalid");
      input.setAttribute("aria-invalid", "true");
    } else {
      if (pesanError) pesanError.remove();
      field.classList.remove("is-invalid");
      input.removeAttribute("aria-invalid");
    }
  }

  form.querySelectorAll("[data-error]").forEach(function (input) {
    if (input.type === "radio") return;

    input.addEventListener("blur", function () {
      aturPesanError(input, input.checkValidity() ? null : input.dataset.error);
    });
    input.addEventListener("input", function () {
      const field = input.closest(".field");
      if (input.checkValidity()) {
        aturPesanError(input, null);
      } else if (field && field.classList.contains("is-invalid")) {
        aturPesanError(input, input.dataset.error);
      }
    });
    input.addEventListener("change", function () {
      if (input.checkValidity()) aturPesanError(input, null);
    });
  });

  radiosJenis.forEach(function (radio) {
    radio.addEventListener("change", function () {
      aturPesanError(radio, null);
      perbaruiJenis();
      perbaruiJumlah();
      perbaruiRingkasan();
    });
  });

  form.querySelectorAll(".chip-btn[data-nominal]").forEach(function (chip) {
    chip.addEventListener("click", function () {
      if (!inputJumlah) return;
      inputJumlah.value = chip.dataset.nominal;
      tandaiChip(chip);
      perbaruiJumlah();
      perbaruiRingkasan();
      aturPesanError(inputJumlah, null);
    });
  });

  form.querySelectorAll(".chip-btn[data-tanggal]").forEach(function (chip) {
    chip.addEventListener("click", function () {
      if (!inputTanggal) return;

      inputTanggal.value = nilaiChipTanggal(chip.dataset.tanggal);
      tandaiChip(chip);
      perbaruiTanggal();
      aturPesanError(inputTanggal, null);
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
      const aktif = chipAktif(inputTanggal);
      if (aktif) aktif.classList.remove("is-active");
      perbaruiTanggal();
    });
  }

  if (inputDeskripsi) {
    inputDeskripsi.addEventListener("input", perbaruiCounter);
  }

  if (inputKategori) {
    inputKategori.addEventListener("input", perbaruiRingkasan);
  }

  if (selectAkun) {
    selectAkun.addEventListener("change", perbaruiRingkasan);
  }

  form.addEventListener("submit", function (event) {
    if (!jenisTerpilih() && radiosJenis.length) {
      aturPesanError(radiosJenis[0], radiosJenis[0].dataset.error);
    }

    if (!form.checkValidity()) {
      form.querySelectorAll("[data-error]").forEach(function (input) {
        if (input.type !== "radio" && !input.checkValidity()) aturPesanError(input, input.dataset.error);
      });
      event.preventDefault();
      return;
    }

    if (tombolKirim) {
      tombolKirim.classList.add("is-loading");
      tombolKirim.setAttribute("aria-busy", "true");
      tombolKirim.textContent = tombolKirim.dataset.loadingText || "Menyimpan…";
    }

    if (statusForm) statusForm.textContent = "Menyimpan transaksi…";
  });

  perbaruiJenis();
  sinkronChip();
  perbaruiJumlah();
  perbaruiTanggal();
  perbaruiCounter();
  perbaruiRingkasan();
});
