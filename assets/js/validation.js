// EHRS Validation JS
(function () {
  'use strict';

  function showError(input, msg) {
    input.classList.add('is-invalid');
    input.classList.remove('is-valid');
    let err = input.parentElement.querySelector('.form-error');
    if (!err) { err = document.createElement('span'); err.className = 'form-error'; input.parentElement.appendChild(err); }
    err.textContent = msg;
    err.classList.add('visible');
  }

  function showValid(input) {
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
    const err = input.parentElement.querySelector('.form-error');
    if (err) err.classList.remove('visible');
  }

  function clearState(input) {
    input.classList.remove('is-invalid', 'is-valid');
    const err = input.parentElement.querySelector('.form-error');
    if (err) err.classList.remove('visible');
  }

  // Password strength
  const pwdInputs = document.querySelectorAll('input[data-strength]');
  pwdInputs.forEach(input => {
    const barEl  = document.getElementById(input.dataset.strength + 'Fill');
    const textEl = document.getElementById(input.dataset.strength + 'Text');
    input.addEventListener('input', () => {
      const v = input.value;
      let score = 0;
      if (v.length >= 8)  score++;
      if (/[A-Z]/.test(v)) score++;
      if (/[0-9]/.test(v)) score++;
      if (/[^A-Za-z0-9]/.test(v)) score++;
      const levels = ['', 'weak', 'fair', 'good', 'strong'];
      const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
      if (barEl)  { barEl.className = 'strength-fill ' + (levels[score] || ''); }
      if (textEl) { textEl.textContent = labels[score] || ''; }
    });
  });

  // Generic required validation on blur
  document.querySelectorAll('[data-validate]').forEach(input => {
    input.addEventListener('blur', () => {
      const rules = input.dataset.validate.split('|');
      let valid = true;
      for (const rule of rules) {
        if (rule === 'required' && !input.value.trim()) {
          showError(input, 'This field is required.'); valid = false; break;
        }
        if (rule === 'email' && input.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
          showError(input, 'Enter a valid email address.'); valid = false; break;
        }
        if (rule.startsWith('min:')) {
          const min = parseInt(rule.split(':')[1]);
          if (input.value.length < min) { showError(input, `Minimum ${min} characters required.`); valid = false; break; }
        }
      }
      if (valid && input.value.trim()) showValid(input);
      else if (!input.value.trim()) clearState(input);
    });
    input.addEventListener('input', () => { if (input.classList.contains('is-invalid')) clearState(input); });
  });
})();
