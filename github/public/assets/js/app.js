// Vanilla enhancements: year injection and accessible states for mobile menu.

(function(){
  // Current year in footer
  const yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = String(new Date().getFullYear());

  // Reflect aria-expanded on the hamburger label based on checkbox state
  const navToggle = document.getElementById('nav-toggle');
  const navToggleBtn = document.querySelector('label[for="nav-toggle"]');
  const mainNav = document.getElementById('hlavne-menu');

  function syncExpanded(){
    const expanded = navToggle && navToggle.checked;
    if (navToggleBtn) navToggleBtn.setAttribute('aria-expanded', String(!!expanded));
    if (mainNav) mainNav.setAttribute('data-open', String(!!expanded));
    document.body.classList.toggle('nav-open', !!expanded);
  }
  if (navToggle){
    navToggle.addEventListener('change', syncExpanded);
    syncExpanded();
  }

  // Close mobile nav when clicking a link (UX nicety)
  if (mainNav){
    mainNav.addEventListener('click', (e)=>{
      const t = e.target;
      if (t && t.matches('a[href]') && window.matchMedia('(max-width: 860px)').matches){
        if (navToggle) { navToggle.checked = false; syncExpanded(); }
      }
    });
  }

  // Allow closing mega sections when one opens on mobile (only one open at a time)
  const megaToggles = Array.from(document.querySelectorAll('.mega-toggle'));
  megaToggles.forEach((chk)=>{
    chk.addEventListener('change', ()=>{
      if (chk.checked && window.matchMedia('(max-width: 860px)').matches){
        megaToggles.forEach(other=>{
          if (other !== chk) other.checked = false;
        });
      }
    });
  });
})();
