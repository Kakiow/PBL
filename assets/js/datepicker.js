// Kalender pemilih tanggal untuk input[type=date]: input dikunci (readonly), kalender
// dibuka lewat klik/Enter/Space. Butuh datepicker.css. Tanpa ketergantungan form.js.
(function (root, factory) {
  var api = factory();

  if (typeof module === "object" && module.exports) {
    module.exports = api;
    return;
  }

  root.DatePicker = api;

  function pasang() {
    api.pasangSemua(root.document);
  }

  if (root.document.readyState === "loading") {
    root.document.addEventListener("DOMContentLoaded", pasang);
  } else {
    pasang();
  }
})(typeof globalThis !== "undefined" ? globalThis : this, function () {
  var HARI = ["Sen", "Sel", "Rab", "Kam", "Jum", "Sab", "Min"];
  var BULAN = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli",
    "Agustus", "September", "Oktober", "November", "Desember"];
  var IKON_KIRI = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M10 3 5 8l5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  var IKON_KANAN = '<svg viewBox="0 0 16 16" aria-hidden="true"><path d="m6 3 5 5-5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';

  function duaAngka(angka) {
    return angka < 10 ? "0" + angka : String(angka);
  }

  function tanggalKeInput(tanggal) {
    return tanggal.getFullYear() + "-" + duaAngka(tanggal.getMonth() + 1) + "-" + duaAngka(tanggal.getDate());
  }

  function parseTanggal(nilai) {
    var cocok = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(nilai || ""));
    if (!cocok) return null;

    var tahun = Number(cocok[1]);
    var bulan = Number(cocok[2]) - 1;
    var hari = Number(cocok[3]);
    var tanggal = new Date(tahun, bulan, hari);

    return tanggal.getFullYear() === tahun && tanggal.getMonth() === bulan && tanggal.getDate() === hari
      ? tanggal
      : null;
  }

  function geserBulan(tahun, bulan, langkah) {
    var total = tahun * 12 + bulan + langkah;
    return { tahun: Math.floor(total / 12), bulan: ((total % 12) + 12) % 12 };
  }

  function selBulan(tahun, bulan, min) {
    var sel = [];
    var offset = (new Date(tahun, bulan, 1).getDay() + 6) % 7;
    var jumlah = new Date(tahun, bulan + 1, 0).getDate();
    var hariIni = tanggalKeInput(new Date());

    for (var kosong = 0; kosong < offset; kosong++) sel.push(null);

    for (var hari = 1; hari <= jumlah; hari++) {
      var nilai = tanggalKeInput(new Date(tahun, bulan, hari));
      sel.push({
        hari: hari,
        nilai: nilai,
        nonaktif: Boolean(min && nilai < min),
        iniHariIni: nilai === hariIni
      });
    }

    while (sel.length % 7 !== 0) sel.push(null);

    return sel;
  }

  function formatBulan(tahun, bulan) {
    return BULAN[bulan] + " " + tahun;
  }

  function formatLengkap(nilai) {
    var tanggal = parseTanggal(nilai);
    return tanggal
      ? tanggal.toLocaleDateString("id-ID", { weekday: "long", day: "numeric", month: "long", year: "numeric" })
      : "";
  }

  function geserHari(nilai, langkah) {
    var tanggal = parseTanggal(nilai);
    if (!tanggal) return null;
    tanggal.setDate(tanggal.getDate() + langkah);
    return tanggalKeInput(tanggal);
  }

  function buatElemen(doc, tag, kelas, teks) {
    var el = doc.createElement(tag);
    if (kelas) el.className = kelas;
    if (teks !== undefined) el.textContent = teks;
    return el;
  }

  function pasang(input) {
    if (!input || input.dataset.dpReady === "1") return;

    var doc = input.ownerDocument;
    var min = String(input.min || "");
    var batasMin = parseTanggal(min);
    var sheetQuery = typeof window !== "undefined" && window.matchMedia
      ? window.matchMedia("(max-width: 640px)")
      : null;

    input.dataset.dpReady = "1";
    input.readOnly = true;
    input.classList.add("has-datepicker");
    input.setAttribute("aria-haspopup", "dialog");
    input.setAttribute("aria-expanded", "false");

    var backdrop = buatElemen(doc, "div", "dp-backdrop");
    backdrop.hidden = true;

    var pop = buatElemen(doc, "div", "dp-pop");
    pop.hidden = true;
    pop.setAttribute("role", "dialog");
    pop.setAttribute("aria-label", "Pilih tanggal");
    pop.setAttribute("aria-modal", "false");
    pop.innerHTML =
      '<div class="dp-head">' +
        '<button type="button" class="dp-nav" data-dp-prev aria-label="Bulan sebelumnya">' + IKON_KIRI + "</button>" +
        '<strong class="dp-title" data-dp-title aria-live="polite"></strong>' +
        '<button type="button" class="dp-nav" data-dp-next aria-label="Bulan berikutnya">' + IKON_KANAN + "</button>" +
      "</div>" +
      '<div class="dp-weekdays" aria-hidden="true">' +
        HARI.map(function (hari) { return "<span>" + hari + "</span>"; }).join("") +
      "</div>" +
      '<div class="dp-grid" data-dp-grid role="group" aria-label="Tanggal"></div>' +
      '<div class="dp-foot">' +
        '<button type="button" class="btn secondary small" data-dp-today>Hari ini</button>' +
        '<button type="button" class="btn secondary small" data-dp-close>Tutup</button>' +
      "</div>";

    doc.body.appendChild(backdrop);
    doc.body.appendChild(pop);

    var judul = pop.querySelector("[data-dp-title]");
    var grid = pop.querySelector("[data-dp-grid]");
    var tombolPrev = pop.querySelector("[data-dp-prev]");
    var tombolHariIni = pop.querySelector("[data-dp-today]");
    var terbuka = false;
    var fokusNilai = input.value;
    var tahun = new Date().getFullYear();
    var bulan = new Date().getMonth();
    var arah = 0;

    function adalahSheet() {
      return Boolean(sheetQuery && sheetQuery.matches);
    }

    function bulanSebelumMin(nilaiTahun, nilaiBulan) {
      return Boolean(batasMin && nilaiTahun * 12 + nilaiBulan <= batasMin.getFullYear() * 12 + batasMin.getMonth());
    }

    function render() {
      judul.textContent = formatBulan(tahun, bulan);
      tombolPrev.disabled = bulanSebelumMin(tahun, bulan);
      tombolHariIni.disabled = Boolean(batasMin && tanggalKeInput(new Date()) < min);

      grid.innerHTML = "";
      grid.classList.remove("is-next", "is-prev");
      void grid.offsetWidth;
      if (arah > 0) grid.classList.add("is-next");
      else if (arah < 0) grid.classList.add("is-prev");

      selBulan(tahun, bulan, min).forEach(function (item) {
        if (!item) {
          var kosong = buatElemen(doc, "span", "dp-blank");
          kosong.setAttribute("aria-hidden", "true");
          grid.appendChild(kosong);
          return;
        }

        var tombol = buatElemen(doc, "button", "dp-day", String(item.hari));
        tombol.type = "button";
        tombol.dataset.dpDay = item.nilai;
        tombol.setAttribute("aria-label", formatLengkap(item.nilai));
        tombol.tabIndex = item.nilai === fokusNilai ? 0 : -1;
        tombol.disabled = item.nonaktif;

        if (item.nilai === input.value) {
          tombol.classList.add("is-selected");
          tombol.setAttribute("aria-pressed", "true");
        }
        if (item.iniHariIni) {
          tombol.classList.add("is-today");
          tombol.setAttribute("aria-current", "date");
        }

        grid.appendChild(tombol);
      });

      arah = 0;
    }

    function fokusKe(nilai) {
      var tombol = grid.querySelector('[data-dp-day="' + nilai + '"]');
      if (tombol && !tombol.disabled) {
        tombol.tabIndex = 0;
        tombol.focus({ preventScroll: true });
      }
    }

    function fokusTanggal(nilai) {
      if (min && nilai < min) nilai = min;
      var tanggal = parseTanggal(nilai);
      if (!tanggal) return;

      tahun = tanggal.getFullYear();
      bulan = tanggal.getMonth();
      fokusNilai = nilai;
      render();
      fokusKe(nilai);
    }

    function pindahBulan(langkah) {
      if (langkah < 0 && tombolPrev.disabled) return;

      var hasil = geserBulan(tahun, bulan, langkah);
      if (batasMin && bulanSebelumMin(hasil.tahun, hasil.bulan)) {
        hasil = { tahun: batasMin.getFullYear(), bulan: batasMin.getMonth() };
      }

      var tanggalLama = parseTanggal(fokusNilai) || new Date();
      var jumlahHari = new Date(hasil.tahun, hasil.bulan + 1, 0).getDate();
      var target = tanggalKeInput(new Date(hasil.tahun, hasil.bulan, Math.min(tanggalLama.getDate(), jumlahHari)));
      if (min && target < min) target = min;

      arah = langkah;
      fokusTanggal(target);
    }

    function pilih(nilai) {
      input.value = nilai;
      input.dispatchEvent(new Event("input", { bubbles: true }));
      input.dispatchEvent(new Event("change", { bubbles: true }));
      tutup(true);
    }

    function aturPosisi() {
      if (!terbuka || adalahSheet()) return;

      pop.style.left = "0px";
      pop.style.top = "0px";
      var kotakInput = input.getBoundingClientRect();
      var kotakPop = pop.getBoundingClientRect();
      var jarak = 8;
      var kiri = Math.max(jarak, Math.min(kotakInput.left, window.innerWidth - kotakPop.width - jarak));
      var atas = kotakInput.bottom + jarak;

      if (atas + kotakPop.height > window.innerHeight - jarak && kotakInput.top - kotakPop.height - jarak >= jarak) {
        atas = kotakInput.top - kotakPop.height - jarak;
      }

      pop.style.left = kiri + "px";
      pop.style.top = atas + "px";
    }

    function aturTampilan() {
      var sheet = adalahSheet();
      pop.classList.toggle("dp-sheet", sheet);
      pop.setAttribute("aria-modal", sheet ? "true" : "false");
      backdrop.hidden = !sheet;
      doc.body.classList.toggle("dp-lock", sheet);

      if (sheet) {
        pop.style.left = "";
        pop.style.top = "";
      } else {
        aturPosisi();
      }
    }

    function klikLuar(event) {
      if (pop.contains(event.target) || event.target === input) return;
      tutup(false);
    }

    function tombolGlobal(event) {
      if (event.key === "Escape") {
        event.preventDefault();
        tutup(true);
        return;
      }

      if (event.key !== "Tab" || !adalahSheet()) return;

      var fokusable = Array.from(pop.querySelectorAll("button:not([disabled])"));
      if (!fokusable.length) return;

      var pertama = fokusable[0];
      var terakhir = fokusable[fokusable.length - 1];

      if (event.shiftKey && doc.activeElement === pertama) {
        event.preventDefault();
        terakhir.focus();
      } else if (!event.shiftKey && doc.activeElement === terakhir) {
        event.preventDefault();
        pertama.focus();
      }
    }

    function buka() {
      if (terbuka) return;
      terbuka = true;

      var nilai = parseTanggal(input.value) ? input.value : tanggalKeInput(new Date());
      var tanggal = parseTanggal(nilai) || new Date();
      fokusNilai = nilai;
      tahun = tanggal.getFullYear();
      bulan = tanggal.getMonth();
      render();

      pop.hidden = false;
      input.setAttribute("aria-expanded", "true");
      aturTampilan();

      requestAnimationFrame(function () {
        pop.classList.add("is-open");
        if (!adalahSheet()) aturPosisi();
      });

      var target = grid.querySelector(".is-selected") || grid.querySelector(".is-today") ||
        grid.querySelector(".dp-day:not([disabled])");
      if (target) {
        target.tabIndex = 0;
        target.focus({ preventScroll: true });
      }

      doc.addEventListener("pointerdown", klikLuar, true);
      doc.addEventListener("keydown", tombolGlobal, true);
      window.addEventListener("resize", aturTampilan);
      window.addEventListener("scroll", aturPosisi, true);
    }

    function tutup(kembalikanFokus) {
      if (!terbuka) return;
      terbuka = false;

      pop.classList.remove("is-open");
      pop.hidden = true;
      backdrop.hidden = true;
      input.setAttribute("aria-expanded", "false");
      doc.body.classList.remove("dp-lock");

      doc.removeEventListener("pointerdown", klikLuar, true);
      doc.removeEventListener("keydown", tombolGlobal, true);
      window.removeEventListener("resize", aturTampilan);
      window.removeEventListener("scroll", aturPosisi, true);

      if (kembalikanFokus !== false) input.focus({ preventScroll: true });
    }

    input.addEventListener("pointerdown", function (event) {
      event.preventDefault();
      input.focus({ preventScroll: true });
      buka();
    });

    input.addEventListener("click", function (event) {
      event.preventDefault();
      buka();
    });

    input.addEventListener("keydown", function (event) {
      if (event.key === "Enter" || event.key === " " || event.key === "Spacebar") {
        event.preventDefault();
        buka();
      }
    });

    grid.addEventListener("click", function (event) {
      var tombol = event.target.closest("[data-dp-day]");
      if (tombol && !tombol.disabled) pilih(tombol.dataset.dpDay);
    });

    grid.addEventListener("keydown", function (event) {
      var tombol = event.target.closest("[data-dp-day]");
      if (!tombol) return;

      var nilai = tombol.dataset.dpDay;
      var langkah = event.key === "ArrowLeft" ? -1
        : event.key === "ArrowRight" ? 1
          : event.key === "ArrowUp" ? -7
            : event.key === "ArrowDown" ? 7
              : 0;

      if (langkah) {
        event.preventDefault();
        fokusTanggal(geserHari(nilai, langkah));
        return;
      }

      if (event.key === "Home" || event.key === "End") {
        event.preventDefault();
        var tanggal = parseTanggal(nilai);
        var posisi = (tanggal.getDay() + 6) % 7;
        fokusTanggal(geserHari(nilai, event.key === "Home" ? -posisi : 6 - posisi));
        return;
      }

      if (event.key === "PageUp" || event.key === "PageDown") {
        event.preventDefault();
        pindahBulan(event.key === "PageUp" ? -1 : 1);
      }
    });

    tombolPrev.addEventListener("click", function () {
      pindahBulan(-1);
    });

    pop.querySelector("[data-dp-next]").addEventListener("click", function () {
      pindahBulan(1);
    });

    tombolHariIni.addEventListener("click", function () {
      pilih(tanggalKeInput(new Date()));
    });

    pop.querySelector("[data-dp-close]").addEventListener("click", function () {
      tutup(true);
    });

    backdrop.addEventListener("click", function () {
      tutup(true);
    });
  }

  return {
    HARI: HARI,
    BULAN: BULAN,
    tanggalKeInput: tanggalKeInput,
    parseTanggal: parseTanggal,
    geserBulan: geserBulan,
    selBulan: selBulan,
    formatBulan: formatBulan,
    pasangSemua: function (doc) {
      (doc || document).querySelectorAll('input[type="date"]').forEach(pasang);
    }
  };
});
