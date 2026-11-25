// Interaksi daftar grup: favorit (bintang), pencarian nama, dan drag-and-drop per zona.
(function () {
  "use strict";

  const rowsEl = document.getElementById("grup-rows");
  if (!rowsEl) return;

  const uid = rowsEl.dataset.userId || "0";
  const FAV_KEY = "simkas-grup-fav-" + uid;
  const ORDER_KEY = "simkas-grup-order-" + uid;
  const PREV_KEY = "simkas-grup-prev-" + uid;

  const countEl = document.getElementById("grup-count");
  const cariEl = document.getElementById("cari-grup");
  const emptySearchEl = document.getElementById("grup-search-empty");
  const spotlightEl = document.getElementById("active-spotlight");
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function bacaJson(key, fallback) {
    try {
      const raw = localStorage.getItem(key);
      return raw ? JSON.parse(raw) : fallback;
    } catch (e) {
      return fallback;
    }
  }

  function simpanJson(key, val) {
    try {
      localStorage.setItem(key, JSON.stringify(val));
    } catch (e) {
      /* mode privat */
    }
  }

  let favSet = new Set((bacaJson(FAV_KEY, []) || []).map(Number));
  let orderList = (bacaJson(ORDER_KEY, []) || []).map(Number);

  function perbaruiTombolBintang(btn, aktif) {
    if (!btn) return;
    btn.classList.toggle("is-fav", aktif);
    btn.setAttribute("aria-pressed", aktif ? "true" : "false");
    btn.setAttribute("aria-label", aktif ? "Hapus dari favorit" : "Tandai favorit");
    btn.title = aktif ? "Hapus dari favorit" : "Tandai favorit";
  }

  function simpanUrutanDariDOM() {
    const ids = Array.from(rowsEl.querySelectorAll(".grup-row[data-row-id]")).map(function (r) {
      return Number(r.dataset.rowId);
    });
    const aktifId = spotlightEl ? Number(spotlightEl.dataset.activeId || 0) : 0;
    if (aktifId && ids.indexOf(aktifId) === -1) ids.unshift(aktifId);
    orderList = ids;
    simpanJson(ORDER_KEY, orderList);
  }

  function urutkanBaris(prevId) {
    const rows = Array.from(rowsEl.querySelectorAll(".grup-row[data-row-id]"));
    rows.forEach(function (row) {
      const id = Number(row.dataset.rowId);
      const fav = favSet.has(id);
      row.dataset.fav = fav ? "1" : "0";
      perbaruiTombolBintang(row.querySelector("[data-favorit]"), fav);
    });

    rows.sort(function (a, b) {
      const idA = Number(a.dataset.rowId);
      const idB = Number(b.dataset.rowId);
      const favDiff = (favSet.has(idB) ? 1 : 0) - (favSet.has(idA) ? 1 : 0);
      if (favDiff !== 0) return favDiff;

      if (prevId && idA === prevId) return -1;
      if (prevId && idB === prevId) return 1;

      const idxA = orderList.indexOf(idA);
      const idxB = orderList.indexOf(idB);
      if (idxA !== -1 && idxB !== -1) return idxA - idxB;
      if (idxA !== -1) return -1;
      if (idxB !== -1) return 1;
      return 0;
    });

    rows.forEach(function (row) {
      rowsEl.appendChild(row);
    });
  }

  function saringBaris() {
    const rows = Array.from(rowsEl.querySelectorAll(".grup-row[data-row-id]"));
    const q = cariEl ? cariEl.value.trim().toLowerCase() : "";
    let tampil = 0;

    rows.forEach(function (row) {
      const nama = (row.dataset.nama || "").toLowerCase();
      const cocok = !q || nama.includes(q);
      row.hidden = !cocok;
      if (cocok) tampil += 1;
    });

    if (countEl) {
      countEl.textContent = q
        ? tampil + " dari " + rows.length + " grup"
        : rows.length + " grup";
    }
    if (emptySearchEl) {
      emptySearchEl.hidden = !(q && tampil === 0);
      if (!emptySearchEl.hidden) {
        emptySearchEl.textContent = 'Tidak ada grup bernama "' + cariEl.value.trim() + '".';
      }
    }
  }

  // Sinkronkan bintang di kartu grup aktif
  if (spotlightEl) {
    const aktifId = Number(spotlightEl.dataset.activeId || 0);
    perbaruiTombolBintang(spotlightEl.querySelector("[data-favorit]"), favSet.has(aktifId));
  }

  // Cek apakah baru saja pindah grup aktif
  let prevId = 0;
  try {
    prevId = Number(sessionStorage.getItem(PREV_KEY) || 0);
    sessionStorage.removeItem(PREV_KEY);
  } catch (e) {
    prevId = 0;
  }

  urutkanBaris(prevId);
  simpanUrutanDariDOM();
  saringBaris();

  if (prevId && !reducedMotion) {
    if (spotlightEl) spotlightEl.classList.add("is-swapping");
    const prevRow = rowsEl.querySelector('.grup-row[data-row-id="' + prevId + '"]');
    if (prevRow) prevRow.classList.add("is-swapping-in");
  }

  if (cariEl) {
    cariEl.addEventListener("input", saringBaris);
  }

  // Simpan id aktif sebelum submit form Jadikan aktif
  rowsEl.addEventListener("submit", function (e) {
    if (!e.target.closest(".grup-pilih-form") || !spotlightEl) return;
    try {
      sessionStorage.setItem(PREV_KEY, String(spotlightEl.dataset.activeId || 0));
    } catch (err) {
      /* mode privat */
    }
  });

  // Toggle bintang favorit dengan animasi FLIP
  document.addEventListener("click", function (e) {
    const btn = e.target.closest("[data-favorit]");
    if (!btn) return;
    const id = Number(btn.dataset.favorit);
    if (!id) return;

    const posisiLama = {};
    rowsEl.querySelectorAll(".grup-row[data-row-id]").forEach(function (row) {
      if (!row.hidden) posisiLama[row.dataset.rowId] = row.getBoundingClientRect().top;
    });

    if (favSet.has(id)) {
      favSet.delete(id);
    } else {
      favSet.add(id);
    }
    simpanJson(FAV_KEY, Array.from(favSet));

    if (spotlightEl && Number(spotlightEl.dataset.activeId) === id) {
      perbaruiTombolBintang(spotlightEl.querySelector("[data-favorit]"), favSet.has(id));
    }

    urutkanBaris(0);
    simpanUrutanDariDOM();
    saringBaris();

    if (!reducedMotion) {
      rowsEl.querySelectorAll(".grup-row[data-row-id]").forEach(function (row) {
        if (row.hidden) return;
        const rid = row.dataset.rowId;
        if (posisiLama[rid] === undefined) return;
        const deltaY = posisiLama[rid] - row.getBoundingClientRect().top;
        if (deltaY !== 0) {
          row.style.transition = "none";
          row.style.transform = "translateY(" + deltaY + "px)";
          void row.offsetWidth;
          row.style.transition = "transform 0.28s cubic-bezier(0.22, 1, 0.36, 1)";
          row.style.transform = "";
        }
      });
    }

    if (window.tampilToast) {
      window.tampilToast(favSet.has(id) ? "Grup ditandai favorit." : "Grup dihapus dari favorit.", "success");
    }
  });

  // Pointer drag & drop per zona (sesama favorit / sesama biasa)
  let dragEl = null;
  let dragFav = null;
  let startY = 0;
  let lastY = 0;
  let offsetY = 0;
  let mulaiDrag = false;
  let pointerIdAktif = null;

  function selesaikanDrag() {
    if (!dragEl) return;
    if (mulaiDrag) {
      dragEl.classList.remove("is-dragging");
      dragEl.style.transform = "";
      simpanUrutanDariDOM();
    }
    dragEl = null;
    dragFav = null;
    mulaiDrag = false;
    pointerIdAktif = null;
  }

  rowsEl.addEventListener("pointerdown", function (e) {
    if (e.button !== 0) return;
    if (e.target.closest("button, a, input, form")) return;
    const row = e.target.closest(".grup-row[data-row-id]");
    if (!row) return;

    dragEl = row;
    dragFav = row.dataset.fav;
    startY = e.clientY;
    lastY = e.clientY;
    offsetY = 0;
    mulaiDrag = false;
    pointerIdAktif = e.pointerId;
  });

  window.addEventListener("pointermove", function (e) {
    if (!dragEl || e.pointerId !== pointerIdAktif) return;

    if (!mulaiDrag) {
      if (Math.abs(e.clientY - startY) < 5) return;
      mulaiDrag = true;
      dragEl.classList.add("is-dragging");
    }

    const dy = e.clientY - lastY;
    lastY = e.clientY;
    offsetY += dy;
    dragEl.style.transform = "translateY(" + offsetY + "px)";

    const dragRect = dragEl.getBoundingClientRect();
    const dragMid = dragRect.top + dragRect.height / 2;
    const saudara = Array.from(rowsEl.querySelectorAll(".grup-row[data-row-id]:not([hidden])"));

    for (let i = 0; i < saudara.length; i++) {
      const target = saudara[i];
      if (target === dragEl || target.dataset.fav !== dragFav) continue;

      const rect = target.getBoundingClientRect();
      const targetMid = rect.top + rect.height / 2;
      const dragIdx = saudara.indexOf(dragEl);
      const pindahKeBawah = dragIdx < i && dragMid > targetMid;
      const pindahKeAtas = dragIdx > i && dragMid < targetMid;
      if (!pindahKeBawah && !pindahKeAtas) continue;

      const oldTopTarget = rect.top;
      const oldTopDrag = dragEl.getBoundingClientRect().top;

      if (pindahKeBawah) {
        rowsEl.insertBefore(dragEl, target.nextSibling);
      } else {
        rowsEl.insertBefore(dragEl, target);
      }

      const newTopDrag = dragEl.getBoundingClientRect().top;
      offsetY += (oldTopDrag - newTopDrag);
      dragEl.style.transform = "translateY(" + offsetY + "px)";

      if (!reducedMotion) {
        const deltaTarget = oldTopTarget - target.getBoundingClientRect().top;
        if (deltaTarget !== 0) {
          target.style.transition = "none";
          target.style.transform = "translateY(" + deltaTarget + "px)";
          void target.offsetWidth;
          target.style.transition = "transform 0.22s cubic-bezier(0.22, 1, 0.36, 1)";
          target.style.transform = "";
        }
      }
      break;
    }
  });

  window.addEventListener("pointerup", selesaikanDrag);
  window.addEventListener("pointercancel", selesaikanDrag);
})();
