document.addEventListener("DOMContentLoaded", function () {
  const topbar = document.getElementById("topbar");
  const toggle = document.getElementById("nav-toggle-btn");
  const nav = document.getElementById("main-nav");
  const userMenu = document.getElementById("nav-user");
  const userBtn = document.getElementById("user-menu-btn");

  if (toggle && nav) {
    toggle.addEventListener("click", function () {
      const terbuka = nav.classList.toggle("show");
      toggle.setAttribute("aria-expanded", terbuka ? "true" : "false");
      toggle.setAttribute("aria-label", terbuka ? "Tutup menu navigasi" : "Buka menu navigasi");
    });
  }

  if (nav) {
    nav.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", function () {
        nav.classList.remove("show");
        if (toggle) {
          toggle.setAttribute("aria-expanded", "false");
          toggle.setAttribute("aria-label", "Buka menu navigasi");
        }
      });
    });
  }

  function tutupMenuPengguna() {
    if (userMenu) userMenu.classList.remove("open");
    if (userBtn) userBtn.setAttribute("aria-expanded", "false");
  }

  if (userMenu && userBtn) {
    userBtn.addEventListener("click", function (event) {
      event.stopPropagation();
      const terbuka = userMenu.classList.toggle("open");
      userBtn.setAttribute("aria-expanded", terbuka ? "true" : "false");
    });

    document.addEventListener("click", function (event) {
      if (!userMenu.contains(event.target)) tutupMenuPengguna();
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") tutupMenuPengguna();
    });
  }

  if (topbar) {
    const saatGulir = function () {
      topbar.classList.toggle("is-scrolled", window.scrollY > 4);
    };
    saatGulir();
    window.addEventListener("scroll", saatGulir, { passive: true });
  }

  const formRole = document.getElementById("form-role");
  const tombolRole = document.getElementById("btn-simpan-role");
  if (formRole && tombolRole) {
    const selects = Array.from(formRole.querySelectorAll(".role-select"));
    const awal = selects.map(function (s) {
      return s.value;
    });
    const perbarui = function () {
      const berubah = selects.some(function (s, i) {
        return s.value !== awal[i];
      });
      tombolRole.classList.toggle("is-visible", berubah);
    };
    selects.forEach(function (s) {
      s.addEventListener("change", perbarui);
    });
    perbarui();
  }

  const formKewajiban = document.querySelector(".form-kewajiban");

  if (formKewajiban) {
    const inputJumlah = formKewajiban.querySelector("#jumlah");
    const previewJumlah = formKewajiban.querySelector("#jumlah-preview");
    const inputTenggat = formKewajiban.querySelector("#tenggat_waktu");
    const previewTenggat = formKewajiban.querySelector("#tenggat-preview");
    const inputDeskripsi = formKewajiban.querySelector("#deskripsi");
    const counterDeskripsi = formKewajiban.querySelector("#deskripsi-counter");
    const tombolKirim = formKewajiban.querySelector("button[type='submit']");
    const statusForm = formKewajiban.querySelector(".form-status");

    function duaAngka(angka) {
      return angka < 10 ? "0" + angka : String(angka);
    }

    function tanggalKeInput(tanggal) {
      return tanggal.getFullYear() + "-" + duaAngka(tanggal.getMonth() + 1) + "-" + duaAngka(tanggal.getDate());
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

    function perbaruiJumlah() {
      if (!inputJumlah || !previewJumlah) return;

      const nilai = parseFloat(inputJumlah.value);

      if (nilai > 0) {
        previewJumlah.textContent = "Tagihan per anggota: Rp " + Math.round(nilai).toLocaleString("id-ID");
        previewJumlah.classList.add("is-filled");
      } else {
        previewJumlah.textContent = "Pilih nominal cepat atau isi manual.";
        previewJumlah.classList.remove("is-filled");
      }

      const aktif = chipAktif(inputJumlah);
      if (aktif && parseFloat(aktif.dataset.nominal) !== nilai) {
        aktif.classList.remove("is-active");
      }
    }

    function perbaruiTenggat() {
      if (!inputTenggat || !previewTenggat) return;

      if (!inputTenggat.value) {
        previewTenggat.textContent = "Tanggal terakhir pembayaran.";
        previewTenggat.classList.remove("is-filled");
        return;
      }

      const bagian = inputTenggat.value.split("-");
      const tanggal = new Date(Number(bagian[0]), Number(bagian[1]) - 1, Number(bagian[2]));

      previewTenggat.textContent = tanggal.toLocaleDateString("id-ID", {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric"
      });
      previewTenggat.classList.add("is-filled");
    }

    function perbaruiCounter() {
      if (!inputDeskripsi || !counterDeskripsi) return;
      const maks = parseInt(inputDeskripsi.getAttribute("maxlength"), 10) || 200;
      counterDeskripsi.textContent = inputDeskripsi.value.length + "/" + maks;
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

    formKewajiban.querySelectorAll("[data-error]").forEach(function (input) {
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
    });

    formKewajiban.querySelectorAll(".chip-btn[data-nominal]").forEach(function (chip) {
      chip.addEventListener("click", function () {
        if (!inputJumlah) return;
        inputJumlah.value = chip.dataset.nominal;
        tandaiChip(chip);
        perbaruiJumlah();
        aturPesanError(inputJumlah, null);
      });
    });

    formKewajiban.querySelectorAll(".chip-btn[data-tenggat]").forEach(function (chip) {
      chip.addEventListener("click", function () {
        if (!inputTenggat) return;

        const pintasan = chip.dataset.tenggat;
        const sekarang = new Date();
        let tanggal;

        if (pintasan === "akhir-bulan") {
          tanggal = new Date(sekarang.getFullYear(), sekarang.getMonth() + 1, 0);
        } else {
          tanggal = new Date(
            sekarang.getFullYear(),
            sekarang.getMonth(),
            sekarang.getDate() + parseInt(pintasan, 10)
          );
        }

        inputTenggat.value = tanggalKeInput(tanggal);
        tandaiChip(chip);
        perbaruiTenggat();
        aturPesanError(inputTenggat, null);
      });
    });

    if (inputJumlah) {
      inputJumlah.addEventListener("input", perbaruiJumlah);
      perbaruiJumlah();
    }

    if (inputTenggat) {
      inputTenggat.addEventListener("input", function () {
        const aktif = chipAktif(inputTenggat);
        if (aktif) aktif.classList.remove("is-active");
        perbaruiTenggat();
      });
      perbaruiTenggat();
    }

    if (inputDeskripsi) {
      inputDeskripsi.addEventListener("input", perbaruiCounter);
      perbaruiCounter();
    }

    formKewajiban.addEventListener("submit", function () {
      if (!formKewajiban.checkValidity()) return;

      if (tombolKirim) {
        tombolKirim.classList.add("is-loading");
        tombolKirim.setAttribute("aria-busy", "true");
        tombolKirim.textContent = tombolKirim.dataset.loadingText || "Menyimpan…";
      }

      if (statusForm) statusForm.textContent = "Menyimpan kewajiban…";
    });
  }
});

function konfirmasiHapus() {
  return confirm("Apakah Anda yakin ingin menghapus data ini?");
}
