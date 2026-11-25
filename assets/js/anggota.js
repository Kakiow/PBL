// Interaksi halaman Anggota: filter tab peran, pencarian nama/email, dan bar simpan perubahan peran.
(function () {
  "use strict";

  const rowsEl = document.getElementById("anggota-rows");
  if (!rowsEl) return;

  const tabsEl = document.getElementById("role-tabs");
  const cariEl = document.getElementById("cari-anggota");
  const emptyEl = document.getElementById("anggota-empty");
  const saveBarEl = document.getElementById("save-bar");
  const saveBarTextEl = document.getElementById("save-bar-text");
  const resetBtn = document.getElementById("btn-reset-role");

  let filterPeran = "Semua";

  function saring() {
    const q = cariEl ? cariEl.value.trim().toLowerCase() : "";
    const rows = Array.from(rowsEl.querySelectorAll(".anggota-row"));
    let tampil = 0;

    rows.forEach(function (row) {
      const peran = row.dataset.role || "";
      const nama = (row.dataset.nama || "").toLowerCase();
      const email = (row.dataset.email || "").toLowerCase();

      const cocokPeran = filterPeran === "Semua" || peran === filterPeran;
      const cocokCari = !q || nama.includes(q) || email.includes(q);
      const visible = cocokPeran && cocokCari;

      row.hidden = !visible;
      if (visible) tampil += 1;
    });

    if (emptyEl) {
      emptyEl.hidden = tampil > 0;
      if (tampil === 0) {
        emptyEl.textContent = q
          ? 'Tidak ada anggota yang cocok dengan "' + cariEl.value.trim() + '".'
          : "Belum ada anggota dengan peran " + filterPeran + ".";
      }
    }
  }

  function perbaruiStatusUbah() {
    const selects = Array.from(rowsEl.querySelectorAll(".role-select"));
    let jumlahUbah = 0;
    let namaAdminBaru = "";

    selects.forEach(function (sel) {
      const row = sel.closest(".anggota-row");
      const awal = sel.dataset.awal || "";
      const berubah = sel.value !== awal;
      if (row) row.classList.toggle("is-dirty", berubah);
      if (berubah) {
        jumlahUbah += 1;
        if (sel.value === "Admin" && row) {
          namaAdminBaru = row.dataset.nama || "";
        }
      }
    });

    if (!saveBarEl || !saveBarTextEl) return;
    if (jumlahUbah === 0) {
      saveBarEl.hidden = true;
      return;
    }

    if (namaAdminBaru) {
      saveBarTextEl.textContent =
        jumlahUbah + " perubahan peran · Peran Admin akan dipindahkan ke " + namaAdminBaru + ".";
    } else {
      saveBarTextEl.textContent = jumlahUbah + " perubahan peran belum disimpan.";
    }
    saveBarEl.hidden = false;
  }

  if (tabsEl) {
    tabsEl.addEventListener("click", function (e) {
      const btn = e.target.closest("[data-tab]");
      if (!btn) return;
      filterPeran = btn.dataset.tab || "Semua";
      tabsEl.querySelectorAll("[data-tab]").forEach(function (t) {
        const aktif = t === btn;
        t.classList.toggle("is-active", aktif);
        t.setAttribute("aria-selected", aktif ? "true" : "false");
      });
      saring();
    });
  }

  if (cariEl) {
    cariEl.addEventListener("input", saring);
  }

  rowsEl.addEventListener("change", function (e) {
    const sel = e.target.closest(".role-select");
    if (!sel) return;

    if (sel.value === "Admin") {
      rowsEl.querySelectorAll(".role-select").forEach(function (lain) {
        if (lain !== sel && lain.value === "Admin") {
          lain.value = lain.dataset.awal || "Anggota";
          if (window.tampilToast) {
            window.tampilToast("Admin hanya satu orang. Pilihan Admin sebelumnya dibatalkan.", "error");
          }
        }
      });
    }
    perbaruiStatusUbah();
  });

  if (resetBtn) {
    resetBtn.addEventListener("click", function () {
      rowsEl.querySelectorAll(".role-select").forEach(function (sel) {
        sel.value = sel.dataset.awal || "Anggota";
      });
      perbaruiStatusUbah();
    });
  }

  saring();
  perbaruiStatusUbah();
})();
