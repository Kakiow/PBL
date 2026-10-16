document.addEventListener("DOMContentLoaded", function () {
  const tombol = document.getElementById("nav-toggle-btn");
  const nav = document.getElementById("main-nav");

  if (tombol && nav) {
    tombol.addEventListener("click", function () {
      nav.classList.toggle("show");
    });
  }
});

function konfirmasiHapus() {
  return confirm("Apakah Anda yakin ingin menghapus data ini?");
}
