/**
 * UTeM MCMS UI/UX Design System (v3) - UI Interactions
 * Pure Vanilla JavaScript. Zero external dependencies. Works offline.
 */

document.addEventListener('DOMContentLoaded', function () {
  // ==========================================================================
  // Test Personas Modal Dialog (Sign-In)
  // ==========================================================================
  const modal = document.getElementById('personas-modal');
  const openBtn = document.getElementById('open-personas-modal');
  const closeBtn = document.getElementById('close-personas-modal');
  const searchInput = document.getElementById('persona-search');
  const personaCards = document.querySelectorAll('.persona-select-card');
  const roleFilters = document.querySelectorAll('.role-filter-btn');

  if (openBtn && modal) {
    openBtn.addEventListener('click', function () {
      modal.classList.add('open');
      if (searchInput) searchInput.focus();
    });
  }

  if (closeBtn && modal) {
    closeBtn.addEventListener('click', function () {
      modal.classList.remove('open');
    });
  }

  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal) {
        modal.classList.remove('open');
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal.classList.contains('open')) {
        modal.classList.remove('open');
      }
    });
  }

  // Persona Live Filtering
  let activeRole = 'all';

  function filterPersonas() {
    const query = (searchInput ? searchInput.value : '').toLowerCase().trim();

    personaCards.forEach(function (card) {
      const name = (card.getAttribute('data-name') || '').toLowerCase();
      const role = (card.getAttribute('data-role') || '').toLowerCase();
      const faculty = (card.getAttribute('data-faculty') || '').toLowerCase();
      const email = (card.getAttribute('data-email') || '').toLowerCase();

      const matchesRole = (activeRole === 'all' || role === activeRole);
      const matchesQuery = (!query || name.includes(query) || role.includes(query) || faculty.includes(query) || email.includes(query));

      if (matchesRole && matchesQuery) {
        card.style.display = '';
      } else {
        card.style.display = 'none';
      }
    });
  }

  if (searchInput) {
    searchInput.addEventListener('input', filterPersonas);
  }

  roleFilters.forEach(function (btn) {
    btn.addEventListener('click', function () {
      roleFilters.forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      activeRole = btn.getAttribute('data-role');
      filterPersonas();
    });
  });

  // ==========================================================================
  // FAQ Category Tab Switching
  // ==========================================================================
  const faqTabs = document.querySelectorAll('.faq-tab-btn');
  const faqPanes = document.querySelectorAll('.faq-category-pane');

  if (faqTabs.length > 0) {
    faqTabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        const target = tab.getAttribute('data-category');
        faqTabs.forEach(function (t) { t.classList.remove('active'); });
        tab.classList.add('active');

        faqPanes.forEach(function (pane) {
          if (pane.getAttribute('data-category') === target) {
            pane.style.display = '';
          } else {
            pane.style.display = 'none';
          }
        });
      });
    });
  }

  // ==========================================================================
  // Public Verification Code Search Form
  // ==========================================================================
  const verifyForm = document.getElementById('public-verify-form');
  if (verifyForm) {
    verifyForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const codeInput = document.getElementById('verify-code-input');
      if (codeInput) {
        const code = codeInput.value.trim().toUpperCase();
        if (code === 'CND01-7K9MJ') {
          window.location.href = 'verify-result-valid.html';
        } else if (code === 'SEC02-3X8PQ') {
          window.location.href = 'verify-result-superseded.html';
        } else if (code === 'DATA9-1Z4TR') {
          window.location.href = 'verify-result-revoked.html';
        } else if (code.startsWith('BADGE-')) {
          window.location.href = 'verify-result-badge.html';
        } else {
          window.location.href = 'verify-result-not-found.html';
        }
      }
    });
  }
});
