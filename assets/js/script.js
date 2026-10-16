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
});

function konfirmasiHapus() {
  return confirm("Apakah Anda yakin ingin menghapus data ini?");
}
