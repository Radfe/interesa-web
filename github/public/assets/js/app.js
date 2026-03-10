(function(){
  const yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = String(new Date().getFullYear());

  const navToggle = document.getElementById('nav-toggle');
  const navToggleBtn = document.querySelector('label[for="nav-toggle"]');
  const mainNav = document.getElementById('hlavne-menu');

  function syncExpanded(){
    const expanded = navToggle && navToggle.checked;
    if (navToggleBtn) navToggleBtn.setAttribute('aria-expanded', String(!!expanded));
    if (mainNav) mainNav.setAttribute('data-open', String(!!expanded));
    document.body.classList.toggle('nav-open', !!expanded);
  }

  if (navToggle) {
    navToggle.addEventListener('change', syncExpanded);
    syncExpanded();
  }

  if (mainNav) {
    mainNav.addEventListener('click', (event) => {
      const target = event.target;
      if (target && target.matches('a[href]') && window.matchMedia('(max-width: 920px)').matches) {
        if (navToggle) {
          navToggle.checked = false;
          syncExpanded();
        }
      }
    });
  }

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && navToggle && navToggle.checked) {
      navToggle.checked = false;
      syncExpanded();
    }
  });
})();