// EHRS Auth JS
(function () {
  'use strict';

  // MFA digit auto-advance
  const digits = document.querySelectorAll('.mfa-digit');
  digits.forEach((input, i) => {
    input.addEventListener('input', () => {
      input.value = input.value.replace(/\D/g, '').slice(0, 1);
      if (input.value && i < digits.length - 1) digits[i + 1].focus();
      syncHiddenMfa();
    });
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && !input.value && i > 0) digits[i - 1].focus();
    });
    input.addEventListener('paste', (e) => {
      e.preventDefault();
      const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
      [...text].slice(0, digits.length).forEach((ch, j) => {
        if (digits[i + j]) digits[i + j].value = ch;
      });
      syncHiddenMfa();
      const next = Math.min(i + text.length, digits.length - 1);
      digits[next].focus();
    });
  });

  function syncHiddenMfa() {
    const hidden = document.getElementById('mfa_code');
    if (hidden) hidden.value = [...digits].map(d => d.value).join('');
  }

  // Login form spinner
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', () => {
      if (typeof showSpinner === 'function') showSpinner('Authenticating…');
    });
  }

  // Password visibility toggle
  document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.dataset.target);
      if (!target) return;
      target.type = target.type === 'password' ? 'text' : 'password';
      btn.textContent = target.type === 'password' ? '👁' : '🙈';
    });
  });
})();
