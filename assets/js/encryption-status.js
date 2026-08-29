// EHRS Encryption Status JS
(function () {
  'use strict';

  function animateBadge(el) {
    el.style.opacity = '0';
    el.style.transform = 'scale(0.8)';
    el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
    setTimeout(() => {
      el.style.opacity = '1';
      el.style.transform = 'scale(1)';
    }, 100);
  }

  document.querySelectorAll('.enc-badge').forEach(animateBadge);

  // Show encryption in progress when record form submits
  const recordForm = document.getElementById('recordForm');
  if (recordForm) {
    recordForm.addEventListener('submit', () => {
      const statusEl = document.getElementById('encStatus');
      if (statusEl) {
        statusEl.textContent = '🔒 Encrypting with AES-256-GCM…';
        statusEl.className = 'enc-badge';
      }
      if (typeof showSpinner === 'function') showSpinner('Encrypting & saving record…');
    });
  }
})();
