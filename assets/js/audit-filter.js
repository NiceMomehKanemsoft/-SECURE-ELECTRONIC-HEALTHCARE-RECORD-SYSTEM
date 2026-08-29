// EHRS Audit Filter JS
(function () {
  'use strict';

  const filterForm = document.getElementById('auditFilterForm');
  if (!filterForm) return;

  filterForm.addEventListener('submit', (e) => {
    e.preventDefault();
    if (typeof showSpinner === 'function') showSpinner('Loading audit logs…');
    filterForm.submit();
  });

  // Live search on visible table
  const searchInput = document.getElementById('auditSearch');
  const auditTable  = document.getElementById('auditTable');
  if (searchInput && auditTable) {
    searchInput.addEventListener('input', () => {
      const q = searchInput.value.toLowerCase();
      auditTable.querySelectorAll('tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }
})();
