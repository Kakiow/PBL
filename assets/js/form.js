// Helper form bersama untuk tagihan.js & transaksi.js: tanggal, rupiah, chip pintasan,
// pesan error per field, counter karakter, dan status "Menyimpan…".
window.FormKas = (function () {
  function duaAngka(angka) {
    return angka < 10 ? "0" + angka : String(angka);
  }

  function tanggalKeInput(tanggal) {
    return tanggal.getFullYear() + "-" + duaAngka(tanggal.getMonth() + 1) + "-" + duaAngka(tanggal.getDate());
  }

  // Nilai chip tanggal: "akhir-bulan" atau selisih hari dari hari ini (mis. "0", "-1", "7").
  function tanggalPintasan(pintasan) {
    const sekarang = new Date();
    const tanggal =
      pintasan === "akhir-bulan"
        ? new Date(sekarang.getFullYear(), sekarang.getMonth() + 1, 0)
        : new Date(sekarang.getFullYear(), sekarang.getMonth(), sekarang.getDate() + parseInt(pintasan, 10));
    return tanggalKeInput(tanggal);
  }

  // "2026-10-19" -> "Senin, 19 Oktober 2026"; string kosong bila input kosong.
  function tanggalPanjang(nilai) {
    if (!nilai) return "";
    const bagian = nilai.split("-");
    return new Date(Number(bagian[0]), Number(bagian[1]) - 1, Number(bagian[2])).toLocaleDateString("id-ID", {
      weekday: "long",
      day: "numeric",
      month: "long",
      year: "numeric"
    });
  }

  function formatRupiah(nilai) {
    return "Rp " + Math.round(nilai).toLocaleString("id-ID");
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

  // Validasi live untuk setiap field ber-[data-error] (radio ditangani masing-masing form).
  function pasangValidasi(form) {
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
  }

  function pasangCounter(input, counter) {
    if (!input || !counter) return;
    const maks = parseInt(input.getAttribute("maxlength"), 10) || 200;
    const perbarui = function () {
      counter.textContent = input.value.length + "/" + maks;
    };
    input.addEventListener("input", perbarui);
    perbarui();
  }

  function tandaiMenyimpan(tombol, status, pesan) {
    if (tombol) {
      tombol.classList.add("is-loading");
      tombol.setAttribute("aria-busy", "true");
      tombol.textContent = tombol.dataset.loadingText || "Menyimpan…";
    }
    if (status) status.textContent = pesan;
  }

  return {
    tanggalKeInput: tanggalKeInput,
    tanggalPintasan: tanggalPintasan,
    tanggalPanjang: tanggalPanjang,
    formatRupiah: formatRupiah,
    tandaiChip: tandaiChip,
    chipAktif: chipAktif,
    aturPesanError: aturPesanError,
    pasangValidasi: pasangValidasi,
    pasangCounter: pasangCounter,
    tandaiMenyimpan: tandaiMenyimpan
  };
})();
