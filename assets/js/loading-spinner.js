// EHRS Loading Spinner
(function () {
  'use strict';

  function ensureOverlay() {
    let overlay = document.getElementById('spinnerOverlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'spinnerOverlay';
      overlay.className = 'spinner-overlay';
      overlay.innerHTML = '<div class="spinner"></div><span class="spinner-text" id="spinnerText">Processing…</span>';
      document.body.appendChild(overlay);
    }
    return overlay;
  }

  window.showSpinner = function (msg) {
    const overlay = ensureOverlay();
    document.getElementById('spinnerText').textContent = msg || 'Processing…';
    overlay.classList.add('active');
  };

  window.hideSpinner = function () {
    const overlay = document.getElementById('spinnerOverlay');
    if (overlay) overlay.classList.remove('active');
  };

  // Auto-show on any form submit that doesn't already handle it
  document.querySelectorAll('form[data-spinner]').forEach(form => {
    form.addEventListener('submit', () => showSpinner(form.dataset.spinner || 'Processing…'));
  });
})();
