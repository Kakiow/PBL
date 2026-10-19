// Perilaku kecil lintas halaman: tombol simpan role, field bukti bayar, cetak, auto-submit, konfirmasi.
document.addEventListener("DOMContentLoaded", function () {
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
      s.addEventListener("change", function () {
        // Hanya satu anggota yang boleh dijadikan Admin dalam sekali simpan.
        if (s.value === "Admin") {
          selects.forEach(function (lain, i) {
            if (lain !== s && lain.value === "Admin") {
              lain.value = awal[i];
              if (window.tampilToast) {
                window.tampilToast("Admin hanya satu orang. Pilihan Admin sebelumnya dibatalkan.", "error");
              }
            }
          });
        }
        perbarui();
      });
    });
    perbarui();
  }

  const formBayar = document.getElementById("form-bayar");
  if (formBayar) {
    const selectMetode = formBayar.querySelector("#metode_pembayaran");
    const fieldBukti = formBayar.querySelector("#field-bukti");
    const inputBukti = formBayar.querySelector("#berkas_bukti");

    const perbaruiBukti = function () {
      const tunai = Boolean(selectMetode) && selectMetode.value === "Tunai";

      if (fieldBukti) fieldBukti.hidden = tunai;
      if (inputBukti) {
        inputBukti.required = !tunai;
        if (tunai) inputBukti.value = "";
      }
    };

    if (selectMetode) {
      selectMetode.addEventListener("change", perbaruiBukti);
      perbaruiBukti();
    }
  }

  document.querySelectorAll("[data-print]").forEach(function (el) {
    el.addEventListener("click", function () {
      window.print();
    });
  });

  document.querySelectorAll("form[data-autosubmit]").forEach(function (form) {
    form.querySelectorAll("input, select").forEach(function (input) {
      input.addEventListener("change", function () {
        form.submit();
      });
    });
  });

  document.querySelectorAll("form[data-konfirmasi]").forEach(function (form) {
    form.addEventListener("submit", function (event) {
      if (!window.confirm(form.dataset.konfirmasi)) {
        event.preventDefault();
      }
    });
  });
});
