document.addEventListener('DOMContentLoaded', () => {
  const rolePaths = {
    admin: 'pages/admin/dashboard.php',
    bendahara: 'pages/bendahara/dashboard.php',
    anggota: 'pages/anggota/dashboard.php'
  };
  const goToRole = (role) => {
    if (rolePaths[role]) window.location.href = rolePaths[role];
  };
  
const loginForm = document.getElementById('login-form');

if (loginForm) {
  let selectedRole = '';

  const roleButtons = document.querySelectorAll(
    '.demo-roles [data-role]'
  );

  roleButtons.forEach(button => {
    button.addEventListener('click', () => {
      selectedRole = button.dataset.role;

      roleButtons.forEach(item => {
        const active = item === button;
        item.classList.toggle('selected', active);
        item.setAttribute(
          'aria-pressed',
          active ? 'true' : 'false'
        );
      });
    });
  });

  loginForm.addEventListener('submit', event => {
    event.preventDefault();

    if (!loginForm.reportValidity()) return;

    if (!selectedRole) {
      alert('Pilih Demo Role terlebih dahulu.');
      return;
    }

    const rolePaths = {
      admin: 'pages/admin/dashboard.php',
      bendahara: 'pages/bendahara/dashboard.php',
      anggota: 'pages/anggota/dashboard.php'
    };

    window.location.href = rolePaths[selectedRole];
  });
}

  if (loginForm) {
    loginForm.addEventListener('submit', event => {
      event.preventDefault();

      const username = document.getElementById('username');
      const passwordInput = document.getElementById('password');
      const roleSelect = document.getElementById('role');

      if (!loginForm.reportValidity()) return;

      if (!username.value.trim()) {
        username.setCustomValidity('Username tidak boleh kosong.');
        username.reportValidity();
        username.setCustomValidity('');
        return;
      }

      if (!passwordInput.value) {
        passwordInput.reportValidity();
        return;
      }

      const role = roleSelect.value;
      if (!rolePaths[role]) {
        alert('Pilih role terlebih dahulu.');
        return;
      }

      // Demo frontend: username/password belum divalidasi melalui database.
      goToRole(role);
    });
  }
 
let selectedRole = '';


const roleInput = document.getElementById('role');

document.querySelectorAll('.demo-roles [data-role]').forEach(button => {
  button.setAttribute('aria-pressed', 'false');

  button.addEventListener('click', () => {
    const selected = button.dataset.role;

    // Simpan role yang dipilih jika input role tersedia
    if (roleInput) {
      roleInput.value = selected;
    }

    // Perbarui tampilan semua tombol role
    document.querySelectorAll('.demo-roles [data-role]').forEach(item => {
      const isSelected = item === button;

      item.classList.toggle('selected', isSelected);
      item.setAttribute('aria-pressed', String(isSelected));
    });
  });
});


  document.querySelectorAll('.login-role-buttons [data-role]').forEach(button => {
    button.addEventListener('click', () => {
      if (roleInput) roleInput.value = button.dataset.role;
      document.querySelectorAll('.login-role-buttons [data-role]').forEach(item => {
        const active = item === button;
        item.classList.toggle('selected', active);
        item.setAttribute('aria-pressed', active ? 'true' : 'false');
      });
    });
  });
  const toggle = document.getElementById('toggle-password');
  const password = document.getElementById('password');
  if (toggle && password) toggle.addEventListener('click', () => {
    const show = password.type === 'password'; password.type = show ? 'text' : 'password'; toggle.textContent = show ? 'Sembunyi' : 'Lihat';
  });
  const menuToggle = document.getElementById('menu-toggle');
  const sidebar = document.getElementById('sidebar');
  if (menuToggle && sidebar) menuToggle.addEventListener('click', () => sidebar.classList.toggle('sidebar-open'));
  document.querySelectorAll('[data-open-modal]').forEach(button => button.addEventListener('click', () => {
    const modal = document.getElementById(button.dataset.openModal);
    if (modal) { modal.hidden = false; document.body.classList.add('modal-open'); }
  }));
  document.querySelectorAll('[data-close-modal]').forEach(button => button.addEventListener('click', () => {
    const modal = button.closest('.modal-backdrop');
    if (modal) { modal.hidden = true; document.body.classList.remove('modal-open'); }
  }));
  document.querySelectorAll('.modal-backdrop').forEach(modal => modal.addEventListener('click', event => {
    if (event.target === modal) { modal.hidden = true; document.body.classList.remove('modal-open'); }
  }));
  document.querySelectorAll('.modal-card form').forEach(form => form.addEventListener('submit', event => {
    event.preventDefault();
    alert('Tampilan formulir berhasil diuji. Data belum disimpan ke database.');
    const modal = form.closest('.modal-backdrop');
    if (modal) { modal.hidden = true; document.body.classList.remove('modal-open'); form.reset(); }
  }));
  document.querySelectorAll('[data-demo-action]').forEach(button => button.addEventListener('click', () => {
    const message = button.dataset.demoAction || 'Aksi ini akan aktif setelah backend terhubung.';
    alert(message);
  }));
  document.querySelectorAll('[data-filter-table]').forEach(input => input.addEventListener('input', () => {
    const table = document.querySelector(input.dataset.filterTable); if (!table) return;
    const term = input.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => row.hidden = !row.innerText.toLowerCase().includes(term));
  }));
});
