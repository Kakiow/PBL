// Perilaku sidebar: drawer mobile, clop rail desktop, dan penyimpanan preferensi.
document.addEventListener("DOMContentLoaded", function () {
  const toggle = document.getElementById("nav-toggle-btn");
  const sidebar = document.getElementById("sidebar");
  const backdrop = document.getElementById("sidebar-backdrop");

  function tutupSidebar() {
    document.body.classList.remove("sidebar-open");
    if (toggle) toggle.setAttribute("aria-expanded", "false");
    if (backdrop) backdrop.hidden = true;
  }

  function bukaSidebar() {
    document.body.classList.add("sidebar-open");
    if (toggle) toggle.setAttribute("aria-expanded", "true");
    if (backdrop) backdrop.hidden = false;
  }

  if (toggle && sidebar) {
    toggle.addEventListener("click", function () {
      if (document.body.classList.contains("sidebar-open")) {
        tutupSidebar();
      } else {
        bukaSidebar();
      }
    });

    if (backdrop) backdrop.addEventListener("click", tutupSidebar);

    sidebar.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", tutupSidebar);
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") tutupSidebar();
    });

    window.addEventListener("resize", function () {
      if (window.innerWidth > 900) tutupSidebar();
    });
  }

  const railToggle = document.getElementById("rail-toggle");
  const mediaRail = window.matchMedia("(min-width: 1000px)");

  function terapkanRail() {
    const rail = Boolean(mediaRail.matches) && localStorage.getItem("sidebar-rail") === "1";
    document.body.classList.toggle("sidebar-rail", rail);
    if (railToggle) {
      railToggle.setAttribute("aria-pressed", rail ? "true" : "false");
      railToggle.setAttribute("aria-label", rail ? "Perluas menu navigasi" : "Ciutkan menu navigasi");
    }
  }

  if (railToggle) {
    railToggle.addEventListener("click", function () {
      const aktif = document.body.classList.toggle("sidebar-rail");

      try {
        localStorage.setItem("sidebar-rail", aktif ? "1" : "0");
      } catch (e) {
        /* mode privat: preferensi tidak disimpan */
      }

      railToggle.setAttribute("aria-pressed", aktif ? "true" : "false");
      railToggle.setAttribute("aria-label", aktif ? "Perluas menu navigasi" : "Ciutkan menu navigasi");
    });

    terapkanRail();

    if (typeof mediaRail.addEventListener === "function") {
      mediaRail.addEventListener("change", terapkanRail);
    }
  }
});
