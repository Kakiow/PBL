// Tombol mata pada field password: tampilkan ketikan supaya mudah diperiksa.
(function () {
  document.querySelectorAll("[data-sandi-toggle]").forEach(function (tombol) {
    const input = document.getElementById(tombol.getAttribute("aria-controls") || "");
    if (!input) return;

    const labelTampil = tombol.getAttribute("aria-label") || "Tampilkan password";
    const labelSembunyi = tombol.dataset.labelSembunyi || "Sembunyikan password";

    tombol.addEventListener("click", function () {
      const tampil = input.type === "password";
      input.type = tampil ? "text" : "password";
      tombol.setAttribute("aria-pressed", tampil ? "true" : "false");
      tombol.setAttribute("aria-label", tampil ? labelSembunyi : labelTampil);
    });
  });
})();
