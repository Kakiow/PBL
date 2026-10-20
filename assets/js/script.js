// Perilaku kecil lintas halaman: simpan peran, form bayar, konfirmasi ketik nama, cetak, auto-submit, konfirmasi.
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
      // Tombol selalu terlihat (tidak muncul tiba-tiba), aktif hanya saat ada perubahan.
      tombolRole.disabled = !berubah;
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
    const namaBukti = formBayar.querySelector("#berkas-nama");
    const infoTujuan = formBayar.querySelector("#info-tujuan");

    const perbaruiNama = function () {
      if (!inputBukti || !namaBukti) return;
      const berkas = inputBukti.files && inputBukti.files[0];
      namaBukti.textContent = berkas ? berkas.name : "Belum ada file dipilih";
      namaBukti.classList.toggle("is-filled", Boolean(berkas));
    };

    // Tunai: tanpa bukti & tanpa info rekening. Metode non-tunai: tampilkan tujuan transfer.
    const perbaruiBukti = function () {
      const metode = selectMetode ? selectMetode.value : "";
      const tunai = metode === "Tunai";

      if (fieldBukti) fieldBukti.hidden = tunai;
      if (infoTujuan) infoTujuan.hidden = metode === "" || tunai;
      if (inputBukti) {
        inputBukti.required = !tunai;
        if (tunai) {
          inputBukti.value = "";
          perbaruiNama();
        }
      }
    };

    if (inputBukti) inputBukti.addEventListener("change", perbaruiNama);

    if (selectMetode) {
      selectMetode.addEventListener("change", perbaruiBukti);
      perbaruiBukti();
    }
  }

  // Tombol berbahaya baru aktif setelah teks konfirmasi diketik persis (mis. nama grup).
  document.querySelectorAll("input[data-cocok]").forEach(function (input) {
    const tombol = document.getElementById(input.dataset.tombol || "");
    if (!tombol) return;
    const cek = function () {
      tombol.disabled = input.value.trim() !== input.dataset.cocok;
    };
    input.addEventListener("input", cek);
    cek();
  });

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
