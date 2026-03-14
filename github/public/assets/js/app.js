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

  // Copy helpers used across the admin workflow.
  let copyToastTimer = null;

  function ensureCopyToast(){
    let toast = document.querySelector('[data-copy-toast]');
    if (toast) return toast;

    toast = document.createElement('div');
    toast.className = 'copy-toast';
    toast.setAttribute('data-copy-toast', 'true');
    toast.setAttribute('aria-live', 'polite');
    toast.setAttribute('aria-atomic', 'true');
    document.body.appendChild(toast);
    return toast;
  }

  function showCopyToast(message, isError){
    const toast = ensureCopyToast();
    toast.textContent = message;
    toast.classList.toggle('is-error', !!isError);
    toast.classList.add('is-visible');

    if (copyToastTimer) window.clearTimeout(copyToastTimer);
    copyToastTimer = window.setTimeout(()=>{
      toast.classList.remove('is-visible');
    }, 2200);
  }

  function fallbackCopyText(value){
    const textarea = document.createElement('textarea');
    textarea.value = value;
    textarea.setAttribute('readonly', 'readonly');
    textarea.style.position = 'fixed';
    textarea.style.top = '-9999px';
    textarea.style.left = '-9999px';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();

    const success = document.execCommand('copy');
    document.body.removeChild(textarea);
    return success;
  }

  async function copyText(value){
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(value);
      return true;
    }

    return fallbackCopyText(value);
  }

  function markCopyButton(button){
    const originalText = button.getAttribute('data-copy-original-text') || button.textContent;
    button.setAttribute('data-copy-original-text', originalText);
    button.textContent = 'Skopirovane';
    button.classList.add('is-copied');

    window.setTimeout(()=>{
      button.textContent = button.getAttribute('data-copy-original-text') || originalText;
      button.classList.remove('is-copied');
    }, 1400);
  }

  document.addEventListener('click', async (event)=>{
    const button = event.target.closest('[data-copy-value]');
    if (!button) return;

    event.preventDefault();

    const value = button.getAttribute('data-copy-value') || '';
    const label = (button.getAttribute('data-copy-label') || button.textContent || 'Text').trim();
    if (!value) {
      showCopyToast(label + ': nic na kopirovanie.', true);
      return;
    }

    try {
      const copied = await copyText(value);
      if (!copied) {
        throw new Error('copy-failed');
      }

      markCopyButton(button);
      showCopyToast(label + ': skopirovane.', false);
    } catch (error) {
      showCopyToast('Kopirovanie zlyhalo. Skus znova.', true);
    }
  });
})();
