document.addEventListener('DOMContentLoaded', () => {
  const addSectionButton = document.querySelector('[data-add-section]');
  const sectionsRoot = document.querySelector('[data-sections-root]');
  const sectionTemplate = document.querySelector('#admin-section-template');

  if (addSectionButton && sectionsRoot && sectionTemplate instanceof HTMLTemplateElement) {
    addSectionButton.addEventListener('click', () => {
      const fragment = sectionTemplate.content.cloneNode(true);
      sectionsRoot.appendChild(fragment);
    });
  }

  const columnsField = document.querySelector('[data-columns-json]');
  const rowsField = document.querySelector('[data-rows-json]');
  const fillColumnsButton = document.querySelector('[data-fill-columns]');
  const fillRowsButton = document.querySelector('[data-fill-rows]');

  if (fillColumnsButton && columnsField instanceof HTMLTextAreaElement) {
    fillColumnsButton.addEventListener('click', () => {
      if (columnsField.value.trim() !== '') {
        return;
      }
      columnsField.value = JSON.stringify([
        { key: 'product_slug', label: 'Produkt', type: 'product' },
        { key: 'best_for', label: 'Najlepsie pre', type: 'text' },
        { key: 'rating', label: 'Rating', type: 'text' },
        { key: 'cta', label: 'Akcia', type: 'cta' }
      ], null, 2);
    });
  }

  if (fillRowsButton && rowsField instanceof HTMLTextAreaElement) {
    fillRowsButton.addEventListener('click', () => {
      if (rowsField.value.trim() !== '') {
        return;
      }
      rowsField.value = JSON.stringify([
        {
          product_slug: 'gymbeam-true-whey',
          best_for: 'kazdodenne pouzitie',
          rating: '4.7'
        },
        {
          product_slug: 'gymbeam-creatine-monohydrate',
          best_for: 'vykon a sila',
          rating: '4.6'
        }
      ], null, 2);
    });
  }
});
