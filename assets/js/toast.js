// Notifikasi melayang (pojok kanan atas) + tombol salin ke clipboard.
(function () {
  function areaToast() {
    return document.getElementById("toast-area");
  }

  function pasangTutup(el, durasi) {
    const tutup = function () {
      if (el.classList.contains("is-out")) return;
      el.classList.add("is-out");
      window.setTimeout(function () {
        el.remove();
      }, 200);
    };

    const tombol = el.querySelector(".flash-close");
    if (tombol) tombol.addEventListener("click", tutup);

    window.setTimeout(tutup, durasi);
  }

  function tampilToast(pesan, tipe) {
    const area = areaToast();
    if (!area) return;

    const el = document.createElement("div");
    el.className = "flash " + (tipe === "error" ? "error" : "success");
    el.setAttribute("role", tipe === "error" ? "alert" : "status");

    const teks = document.createElement("span");
    teks.className = "flash-text";
    teks.textContent = pesan;

    const tombol = document.createElement("button");
    tombol.type = "button";
    tombol.className = "flash-close";
    tombol.setAttribute("aria-label", "Tutup notifikasi");
    tombol.textContent = "×";

    el.appendChild(teks);
    el.appendChild(tombol);
    area.appendChild(el);
    pasangTutup(el, 4500);
  }

  // Clipboard API hanya jalan di secure context (https / localhost);
  // sedangkan app ini bisa dibuka lewat http://192.168.x.x — perlu fallback.
  function salinKeClipboard(teks) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(teks).then(
        function () {
          return true;
        },
        function () {
          return false;
        }
      );
    }

    const ta = document.createElement("textarea");
    ta.value = teks;
    ta.setAttribute("readonly", "");
    ta.style.position = "fixed";
    ta.style.top = "-1000px";
    document.body.appendChild(ta);
    ta.select();

    let berhasil = false;
    try {
      berhasil = document.execCommand("copy");
    } catch (e) {
      berhasil = false;
    }
    ta.remove();

    return Promise.resolve(berhasil);
  }

  window.tampilToast = tampilToast;

  document.addEventListener("DOMContentLoaded", function () {
    const area = areaToast();

    document.querySelectorAll("[data-flash]").forEach(function (el) {
      if (area) area.appendChild(el);
      pasangTutup(el, 6000);
    });

    document.querySelectorAll("[data-copy]").forEach(function (tombol) {
      tombol.addEventListener("click", function () {
        const teks = tombol.dataset.copy || "";

        salinKeClipboard(teks).then(function (berhasil) {
          if (berhasil) {
            tampilToast("Kode grup " + teks + " disalin ke clipboard.");
          } else {
            tampilToast("Gagal menyalin. Kode grup: " + teks, "error");
          }
        });
      });
    });
  });
})();
