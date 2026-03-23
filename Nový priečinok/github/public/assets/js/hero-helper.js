const applyHeroFilters = () => {
  const searchInput = document.querySelector('#hero-filter-search');
  const categoryInput = document.querySelector('#hero-filter-category');
  const priorityInput = document.querySelector('#hero-filter-priority');
  const missingInput = document.querySelector('#hero-filter-missing');
  const cards = Array.from(document.querySelectorAll('.hero-card'));
  const count = document.querySelector('#hero-filter-count');

  if (!searchInput || !categoryInput || !priorityInput || !missingInput || !count) {
    return;
  }

  const query = searchInput.value.trim().toLowerCase();
  const category = categoryInput.value;
  const priorityOnly = priorityInput.checked;
  const missingOnly = missingInput.checked;

  let visible = 0;
  cards.forEach((card) => {
    const title = card.getAttribute('data-title') || '';
    const slug = card.getAttribute('data-slug') || '';
    const cardCategory = card.getAttribute('data-category') || '';
    const matchesQuery = query === '' || title.includes(query) || slug.includes(query);
    const matchesCategory = category === '' || cardCategory === category;
    const matchesPriority = !priorityOnly || card.getAttribute('data-priority') === '1';
    const matchesMissing = !missingOnly || card.getAttribute('data-has-webp') === '0';
    const show = matchesQuery && matchesCategory && matchesPriority && matchesMissing;

    card.hidden = !show;
    if (show) {
      visible += 1;
    }
  });

  count.textContent = String(visible);
};

document.addEventListener('click', async (event) => {
  const button = event.target.closest('.js-copy-prompt');
  if (button) {
    const prompt = button.getAttribute('data-prompt') || '';
    if (!prompt) {
      return;
    }

    try {
      await navigator.clipboard.writeText(prompt);
      const original = button.textContent;
      button.textContent = 'Prompt skopírovaný';
      window.setTimeout(() => {
        button.textContent = original;
      }, 1400);
    } catch (error) {
      button.textContent = 'Kopírovanie zlyhalo';
    }

    return;
  }

  const summary = event.target.closest('.js-hero-category-filter');
  if (summary) {
    const categoryInput = document.querySelector('#hero-filter-category');
    if (categoryInput) {
      categoryInput.value = summary.getAttribute('data-category') || '';
      applyHeroFilters();
      categoryInput.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
  }
});

document.addEventListener('input', (event) => {
  if (event.target.closest('#hero-helper-toolbar')) {
    applyHeroFilters();
  }
});

document.addEventListener('change', (event) => {
  if (event.target.closest('#hero-helper-toolbar')) {
    applyHeroFilters();
  }
});

document.addEventListener('DOMContentLoaded', () => {
  applyHeroFilters();
});
