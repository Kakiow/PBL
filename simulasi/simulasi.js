/* SIMKAS — Simulasi Interaksi Dashboard */
(function () {
  const body = document.body;
  const railBtn = document.getElementById('rail-toggle');
  const navBtn = document.getElementById('nav-toggle-btn');
  const backdrop = document.getElementById('sidebar-backdrop');
  const toastArea = document.getElementById('toast-area');
  const dashGrid = document.getElementById('dash-grid');
  const sideCard = document.getElementById('card-perlu-tindakan');
  const roleLabel = document.getElementById('sidebar-role-label');
  const roleButtons = document.querySelectorAll('[data-sim-role]');
  const summaryEl = document.getElementById('dash-summary');

  function showToast(msg) {
    if (!toastArea) return;
    const el = document.createElement('div');
    el.className = 'toast';
    el.textContent = msg;
    toastArea.appendChild(el);
    setTimeout(() => {
      el.remove();
    }, 3200);
  }

  // Toggle sidebar rail (desktop)
  if (railBtn) {
    railBtn.addEventListener('click', () => {
      const collapsed = body.classList.toggle('sidebar-rail');
      railBtn.setAttribute('aria-pressed', String(collapsed));
    });
  }

  // Drawer mobile
  function closeDrawer() {
    body.classList.remove('sidebar-open');
    if (backdrop) backdrop.hidden = true;
    if (navBtn) navBtn.setAttribute('aria-expanded', 'false');
  }

  if (navBtn) {
    navBtn.addEventListener('click', () => {
      const open = body.classList.toggle('sidebar-open');
      if (backdrop) backdrop.hidden = !open;
      navBtn.setAttribute('aria-expanded', String(open));
    });
  }

  if (backdrop) {
    backdrop.addEventListener('click', closeDrawer);
  }

  // Switcher peran simulasi (Admin/Bendahara vs Anggota)
  roleButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
      const role = btn.dataset.simRole;
      roleButtons.forEach((b) => b.classList.toggle('is-active', b === btn));
      const isManager = role === 'bendahara';

      if (dashGrid) dashGrid.classList.toggle('has-side', isManager);
      if (sideCard) sideCard.hidden = !isManager;
      if (roleLabel) roleLabel.textContent = isManager ? 'Bendahara' : 'Anggota';

      document.querySelectorAll('[data-manager-only]').forEach((el) => {
        el.hidden = !isManager;
      });

      showToast(isManager ? 'Tampilan simulasi: Bendahara (pengelola)' : 'Tampilan simulasi: Anggota');
    });
  });

  // Simulasi tombol Salin Kode Grup
  const copyBtn = document.querySelector('[data-copy-code]');
  if (copyBtn) {
    copyBtn.addEventListener('click', () => {
      const code = copyBtn.dataset.copyCode || '';
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(code).catch(() => {});
      }
      showToast('Kode grup ' + code + ' disalin.');
    });
  }

  // Simulasi aksi Bayar pada daftar tagihan
  document.querySelectorAll('[data-sim-pay]').forEach((btn) => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const actionSlot = btn.closest('.bill-action');
      if (actionSlot) {
        actionSlot.innerHTML = '<span class="badge warning">Menunggu</span>';
      }
      showToast('Simulasi: bukti pembayaran dikirim, status menjadi Menunggu.');
    });
  });
})();
