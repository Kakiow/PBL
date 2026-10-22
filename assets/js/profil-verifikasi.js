// Dialog verifikasi password di halaman profil.
(function () {
  const dialog = document.getElementById("dialog-verifikasi");
  if (!dialog || typeof dialog.showModal !== "function") return;

  const kartu = dialog.querySelector(".profile-verify");

  function buka() {
    if (!dialog.open) dialog.showModal();
  }

  // PHP bisa merender dialog dalam keadaan `open` (mis. ?verifikasi=1 atau error).
  // Naikkan ke modal supaya ada backdrop dan fokus terkunci.
  if (dialog.open) {
    dialog.close();
    dialog.showModal();
  }

  document.querySelectorAll("[data-buka-verifikasi]").forEach(function (tombol) {
    tombol.addEventListener("click", function (e) {
      e.preventDefault();
      buka();
    });
  });

  document.querySelectorAll("[data-tutup-verifikasi]").forEach(function (tombol) {
    tombol.addEventListener("click", function (e) {
      e.preventDefault();
      dialog.close();
    });
  });

  dialog.addEventListener("click", function (e) {
    if (!kartu.contains(e.target)) dialog.close();
  });

  dialog.addEventListener("close", function () {
    const url = new URL(window.location.href);
    if (!url.searchParams.has("verifikasi")) return;

    url.searchParams.delete("verifikasi");
    history.replaceState(null, "", url.toString());
  });
})();
