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
  const addColumnButton = document.querySelector('[data-add-column]');
  const addRowButton = document.querySelector('[data-add-row]');
  const columnsRoot = document.querySelector('[data-comparison-columns]');
  const rowsRoot = document.querySelector('[data-comparison-rows]');

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

  const ensureComparisonRoots = () => columnsRoot instanceof HTMLElement && rowsRoot instanceof HTMLElement;

  const currentColumnCount = () => {
    if (!ensureComparisonRoots()) {
      return 0;
    }
    return columnsRoot.querySelectorAll('[data-comparison-column]').length;
  };

  const comparisonRows = () => Array.from(rowsRoot.querySelectorAll('[data-comparison-row]'));

  const reindexRowCells = () => {
    comparisonRows().forEach((row) => {
      const cells = Array.from(row.querySelectorAll('textarea'));
      cells.forEach((cell, columnIndex) => {
        cell.name = `comparison_cell_${columnIndex}[]`;
      });
    });
  };

  const buildColumn = (column = {}, index = 0) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'admin-comparison-column';
    wrapper.dataset.comparisonColumn = 'true';

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'btn btn-secondary btn-small admin-comparison-remove';
    removeButton.dataset.removeColumn = 'true';
    removeButton.textContent = 'Odstranit stlpec';

    const labelInput = document.createElement('input');
    labelInput.type = 'text';
    labelInput.name = 'comparison_column_label[]';
    labelInput.placeholder = 'Label stlpca';
    labelInput.value = column.label || `Stlpec ${index + 1}`;

    const keyInput = document.createElement('input');
    keyInput.type = 'text';
    keyInput.name = 'comparison_column_key[]';
    keyInput.placeholder = 'key';
    keyInput.value = column.key || `column_${index + 1}`;

    const typeSelect = document.createElement('select');
    typeSelect.name = 'comparison_column_type[]';
    ['text', 'product', 'cta'].forEach((type) => {
      const option = document.createElement('option');
      option.value = type;
      option.textContent = type;
      if ((column.type || 'text') === type) {
        option.selected = true;
      }
      typeSelect.appendChild(option);
    });

    wrapper.appendChild(removeButton);
    wrapper.appendChild(labelInput);
    wrapper.appendChild(keyInput);
    wrapper.appendChild(typeSelect);

    return wrapper;
  };

  const buildCell = (columnIndex, value = '') => {
    const cell = document.createElement('textarea');
    cell.name = `comparison_cell_${columnIndex}[]`;
    cell.rows = 2;
    cell.placeholder = 'Hodnota bunky';
    cell.value = value;
    return cell;
  };

  const buildRow = (values = []) => {
    const row = document.createElement('div');
    row.className = 'admin-comparison-row-grid';
    row.dataset.comparisonRow = 'true';

    const columnCount = currentColumnCount() > 0 ? currentColumnCount() : 1;
    for (let columnIndex = 0; columnIndex < columnCount; columnIndex += 1) {
      row.appendChild(buildCell(columnIndex, values[columnIndex] || ''));
    }

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'btn btn-secondary btn-small admin-comparison-remove admin-comparison-remove-row';
    removeButton.dataset.removeRow = 'true';
    removeButton.textContent = 'Odstranit riadok';
    row.appendChild(removeButton);

    return row;
  };

  const addColumn = (column = {}) => {
    if (!ensureComparisonRoots()) {
      return;
    }

    const index = currentColumnCount();
    columnsRoot.appendChild(buildColumn(column, index));

    comparisonRows().forEach((row) => {
      const removeButton = row.querySelector('[data-remove-row]');
      row.insertBefore(buildCell(index), removeButton || null);
    });

    reindexRowCells();
  };

  const addRow = (values = []) => {
    if (!ensureComparisonRoots()) {
      return;
    }

    if (currentColumnCount() === 0) {
      addColumn();
    }

    rowsRoot.appendChild(buildRow(values));
    reindexRowCells();
  };

  const removeColumn = (columnElement) => {
    if (!ensureComparisonRoots() || !(columnElement instanceof HTMLElement)) {
      return;
    }

    const columns = Array.from(columnsRoot.querySelectorAll('[data-comparison-column]'));
    if (columns.length <= 1) {
      return;
    }

    const columnIndex = columns.indexOf(columnElement);
    if (columnIndex < 0) {
      return;
    }

    columnElement.remove();
    comparisonRows().forEach((row) => {
      const cells = Array.from(row.querySelectorAll('textarea'));
      if (cells[columnIndex]) {
        cells[columnIndex].remove();
      }
    });

    reindexRowCells();
  };

  const removeRow = (rowElement) => {
    if (!(rowElement instanceof HTMLElement)) {
      return;
    }
    const rows = comparisonRows();
    if (rows.length <= 1) {
      return;
    }
    rowElement.remove();
    reindexRowCells();
  };

  if (addColumnButton) {
    addColumnButton.addEventListener('click', () => addColumn());
  }

  if (addRowButton) {
    addRowButton.addEventListener('click', () => addRow());
  }

  if (columnsRoot) {
    columnsRoot.addEventListener('click', (event) => {
      const trigger = event.target.closest('[data-remove-column]');
      if (!trigger) {
        return;
      }
      const column = trigger.closest('[data-comparison-column]');
      removeColumn(column);
    });
  }

  if (rowsRoot) {
    rowsRoot.addEventListener('click', (event) => {
      const trigger = event.target.closest('[data-remove-row]');
      if (!trigger) {
        return;
      }
      const row = trigger.closest('[data-comparison-row]');
      removeRow(row);
    });
  }

  if (fillColumnsButton) {
    fillColumnsButton.addEventListener('click', () => {
      if (!ensureComparisonRoots()) {
        return;
      }

      while (currentColumnCount() < sampleColumns.length) {
        addColumn();
      }

      const labelInputs = Array.from(columnsRoot.querySelectorAll('input[name="comparison_column_label[]"]'));
      const keyInputs = Array.from(columnsRoot.querySelectorAll('input[name="comparison_column_key[]"]'));
      const typeInputs = Array.from(columnsRoot.querySelectorAll('select[name="comparison_column_type[]"]'));

      sampleColumns.forEach((column, index) => {
        if (labelInputs[index]) {
          labelInputs[index].value = column.label;
        }
        if (keyInputs[index]) {
          keyInputs[index].value = column.key;
        }
        if (typeInputs[index]) {
          typeInputs[index].value = column.type;
        }
      });

      if (columnsField instanceof HTMLTextAreaElement && columnsField.value.trim() === '') {
        columnsField.value = JSON.stringify(sampleColumns, null, 2);
      }
    });
  }

  if (fillRowsButton) {
    fillRowsButton.addEventListener('click', () => {
      if (!ensureComparisonRoots()) {
        return;
      }

      while (currentColumnCount() < sampleColumns.length) {
        addColumn();
      }

      while (comparisonRows().length < sampleRows.length) {
        addRow();
      }

      const rowGrids = comparisonRows();
      sampleRows.forEach((rowValues, rowIndex) => {
        const row = rowGrids[rowIndex];
        if (!row) {
          return;
        }

        const cells = Array.from(row.querySelectorAll('textarea'));
        rowValues.forEach((value, columnIndex) => {
          if (cells[columnIndex] instanceof HTMLTextAreaElement && cells[columnIndex].value.trim() === '') {
            cells[columnIndex].value = value;
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

  reindexRowCells();
});