// EHRS Main JS
(function () {
  'use strict';

  // Hamburger / sidebar toggle
  const hamburger = document.getElementById('hamburgerBtn');
  const navLinks  = document.getElementById('navLinks');
  const sidebar   = document.getElementById('sidebar');

  if (hamburger) {
    hamburger.addEventListener('click', () => {
      hamburger.classList.toggle('open');
      if (navLinks)  navLinks.classList.toggle('open');
      if (sidebar)   sidebar.classList.toggle('open');
    });
  }

  // Close nav on outside click
  document.addEventListener('click', (e) => {
    if (hamburger && !hamburger.contains(e.target) &&
        navLinks && !navLinks.contains(e.target)) {
      hamburger.classList.remove('open');
      navLinks.classList.remove('open');
      if (sidebar) sidebar.classList.remove('open');
    }
  });

  // Dropdown keyboard accessibility
  document.querySelectorAll('.dropdown-toggle').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const menu = btn.nextElementSibling;
      if (menu) menu.style.display = menu.style.display === 'block' ? '' : 'block';
    });
  });

  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = '');
  });

  // Spinner helpers (global)
  window.showSpinner = function (msg) {
    let overlay = document.getElementById('spinnerOverlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'spinnerOverlay';
      overlay.className = 'spinner-overlay';
      overlay.innerHTML = '<div class="spinner"></div><span class="spinner-text" id="spinnerText">Processing…</span>';
      document.body.appendChild(overlay);
    }
    if (msg) document.getElementById('spinnerText').textContent = msg;
    overlay.classList.add('active');
  };

  window.hideSpinner = function () {
    const overlay = document.getElementById('spinnerOverlay');
    if (overlay) overlay.classList.remove('active');
  };

  // Auto-dismiss toasts
  const toast = document.getElementById('flashToast');
  if (toast) setTimeout(() => toast.remove(), 5000);

  // Tooltips (title attribute enhancement)
  document.querySelectorAll('[data-tooltip]').forEach(el => {
    el.setAttribute('title', el.dataset.tooltip);
  });
})();
