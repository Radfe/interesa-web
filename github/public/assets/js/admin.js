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
  const columnLabelInputs = Array.from(document.querySelectorAll('input[name="comparison_column_label[]"]'));
  const columnKeyInputs = Array.from(document.querySelectorAll('input[name="comparison_column_key[]"]'));
  const columnTypeInputs = Array.from(document.querySelectorAll('select[name="comparison_column_type[]"]'));

  const sampleColumns = [
    { key: 'product_slug', label: 'Produkt', type: 'product' },
    { key: 'best_for', label: 'Najlepsie pre', type: 'text' },
    { key: 'rating', label: 'Rating', type: 'text' },
    { key: 'cta', label: 'Akcia', type: 'cta' }
  ];

  const sampleRows = [
    ['gymbeam-true-whey', 'kazdodenne pouzitie', '4.7', ''],
    ['gymbeam-creatine-monohydrate', 'vykon a sila', '4.6', '']
  ];

  if (fillColumnsButton) {
    fillColumnsButton.addEventListener('click', () => {
      sampleColumns.forEach((column, index) => {
        if (columnLabelInputs[index]) {
          columnLabelInputs[index].value = column.label;
        }
        if (columnKeyInputs[index]) {
          columnKeyInputs[index].value = column.key;
        }
        if (columnTypeInputs[index]) {
          columnTypeInputs[index].value = column.type;
        }
      });

      if (columnsField instanceof HTMLTextAreaElement && columnsField.value.trim() === '') {
        columnsField.value = JSON.stringify(sampleColumns, null, 2);
      }
    });
  }

  if (fillRowsButton) {
    fillRowsButton.addEventListener('click', () => {
      sampleRows.forEach((row, rowIndex) => {
        row.forEach((value, columnIndex) => {
          const field = document.querySelector(`textarea[name="comparison_cell_${columnIndex}[]"]:nth-of-type(${rowIndex + 1})`);
          if (field instanceof HTMLTextAreaElement && field.value.trim() === '') {
            field.value = value;
          }
        });
      });

      if (rowsField instanceof HTMLTextAreaElement && rowsField.value.trim() === '') {
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
      }
    });
  }
});
