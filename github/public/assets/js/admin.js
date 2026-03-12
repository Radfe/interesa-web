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
  const fillFromProductsButton = document.querySelector('[data-fill-from-products]');
  const fillFromReadyProductsButton = document.querySelector('[data-fill-ready-products]');
  const fillFromReadyShortlistButton = document.querySelector('[data-fill-ready-shortlist]');
  const presetButtons = Array.from(document.querySelectorAll('[data-apply-preset]'));
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

  const presets = {
    'top-picks': {
      columns: [
        { key: 'product_slug', label: 'Produkt', type: 'product' },
        { key: 'best_for', label: 'Najlepsie pre', type: 'text' },
        { key: 'rating', label: 'Rating', type: 'text' },
        { key: 'cta', label: 'Akcia', type: 'cta' }
      ]
    },
    'catalog-picks': {
      columns: [
        { key: 'product_slug', label: 'Produkt', type: 'product' },
        { key: 'merchant', label: 'Obchod', type: 'text' },
        { key: 'rating', label: 'Rating', type: 'text' },
        { key: 'cta', label: 'Akcia', type: 'cta' }
      ]
    },
    duel: {
      columns: [
        { key: 'feature', label: 'Parameter', type: 'text' },
        { key: 'option_a', label: 'Moznost A', type: 'text' },
        { key: 'option_b', label: 'Moznost B', type: 'text' }
      ],
      rows: [
        ['Typ produktu', '', ''],
        ['Najlepsie pre', '', ''],
        ['Silne stranky', '', ''],
        ['Slabsie stranky', '', '']
      ]
    }
  };

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

  const setColumns = (columns) => {
    if (!ensureComparisonRoots()) {
      return;
    }
    columnsRoot.innerHTML = '';
    columns.forEach((column, index) => {
      columnsRoot.appendChild(buildColumn(column, index));
    });
  };

  const setRows = (rows) => {
    if (!ensureComparisonRoots()) {
      return;
    }
    rowsRoot.innerHTML = '';
    rows.forEach((row) => {
      rowsRoot.appendChild(buildRow(row));
    });
    if (rows.length === 0) {
      rowsRoot.appendChild(buildRow());
    }
    reindexRowCells();
  };

  const selectedRecommendedProducts = () => {
    const checked = Array.from(document.querySelectorAll('input[name="recommended_product_checks[]"]:checked'))
      .map((input) => input.value.trim())
      .filter(Boolean);
    const manualField = document.querySelector('textarea[name="recommended_products"]');
    const manual = manualField instanceof HTMLTextAreaElement
      ? manualField.value.split(/\r?\n/).map((item) => item.trim()).filter(Boolean)
      : [];

    return Array.from(new Set([...checked, ...manual]));
  };

  const selectedRecommendedProductMeta = (options = {}) => {
    const onlyReady = options.onlyReady === true;
    const lookup = new Map();
    Array.from(document.querySelectorAll('input[name="recommended_product_checks[]"]')).forEach((input) => {
      if (!(input instanceof HTMLInputElement)) {
        return;
      }
      const slug = input.value.trim();
      if (!slug) {
        return;
      }
      lookup.set(slug, {
        slug,
        name: (input.dataset.productName || slug).trim(),
        bestFor: (input.dataset.productBestfor || input.dataset.productSummary || '').trim(),
        merchant: (input.dataset.productMerchant || '').trim(),
        rating: (input.dataset.productRating || '').trim(),
        summary: (input.dataset.productSummary || '').trim(),
        affiliateReady: (input.dataset.productAffiliateReady || '') === 'true',
        packshotReady: (input.dataset.productPackshotReady || '') === 'true'
      });
    });

    return selectedRecommendedProducts()
      .map((slug) => lookup.get(slug) || ({ slug, name: slug, bestFor: '', merchant: '', rating: '', summary: '', affiliateReady: false, packshotReady: false }))
      .filter((product) => !onlyReady || (product.affiliateReady && product.packshotReady));
  };

  const applyComparisonRowsFromProducts = (productRows) => {
    if (productRows.length === 0 || !ensureComparisonRoots()) {
      return;
    }

    setColumns(presets['catalog-picks'].columns);
    const rows = productRows.map((product) => [product.slug, product.merchant, product.rating, '']);
    setRows(rows);
    if (rowsField instanceof HTMLTextAreaElement) {
      rowsField.value = JSON.stringify(productRows.map((product) => ({
        product_slug: product.slug,
        merchant: product.merchant,
        rating: product.rating,
        cta: ''
      })), null, 2);
    }
    if (columnsField instanceof HTMLTextAreaElement) {
      columnsField.value = JSON.stringify(presets['catalog-picks'].columns, null, 2);
    }
  };
  const applyReadyShortlist = (productRows) => {
    const sortedRows = [...productRows].sort((left, right) => {
      const leftRating = Number.parseFloat(left.rating || '0');
      const rightRating = Number.parseFloat(right.rating || '0');
      return rightRating - leftRating;
    }).slice(0, 3);

    if (sortedRows.length === 0 || !ensureComparisonRoots()) {
      return;
    }

    setColumns(presets['top-picks'].columns);
    const rows = sortedRows.map((product) => [product.slug, product.bestFor || product.summary || product.name || '', product.rating || '', '']);
    setRows(rows);

    const comparisonTitleField = document.querySelector('input[name="comparison_title"]');
    const comparisonIntroField = document.querySelector('input[name="comparison_intro"]');
    if (comparisonTitleField instanceof HTMLInputElement && comparisonTitleField.value.trim() === '') {
      comparisonTitleField.value = 'Top odporucane produkty';
    }
    if (comparisonIntroField instanceof HTMLInputElement && comparisonIntroField.value.trim() === '') {
      comparisonIntroField.value = 'Kratky shortlist produktov, ktore uz maju hotove packshoty aj affiliate napojenie.';
    }

    if (rowsField instanceof HTMLTextAreaElement) {
      rowsField.value = JSON.stringify(sortedRows.map((product) => ({
        product_slug: product.slug,
        best_for: product.bestFor || product.summary || product.name || '',
        rating: product.rating || '',
        cta: ''
      })), null, 2);
    }
    if (columnsField instanceof HTMLTextAreaElement) {
      columnsField.value = JSON.stringify(presets['top-picks'].columns, null, 2);
    }
  };

  const ensureProductPreset = () => {
    setColumns(presets['top-picks'].columns);
    if (comparisonRows().length === 0) {
      setRows([]);
    }
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

  presetButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const presetKey = button.getAttribute('data-apply-preset');
      const preset = presetKey ? presets[presetKey] : null;
      if (!preset || !ensureComparisonRoots()) {
        return;
      }
      setColumns(preset.columns || []);
      setRows(preset.rows || []);
      if (columnsField instanceof HTMLTextAreaElement) {
        columnsField.value = JSON.stringify(preset.columns || [], null, 2);
      }
      if (rowsField instanceof HTMLTextAreaElement) {
        rowsField.value = JSON.stringify((preset.rows || []).map((rowValues) => {
          const row = {};
          (preset.columns || []).forEach((column, index) => {
            row[column.key] = rowValues[index] || '';
          });
          return row;
        }), null, 2);
      }
    });
  });

  if (fillFromProductsButton) {
    fillFromProductsButton.addEventListener('click', () => {
      applyComparisonRowsFromProducts(selectedRecommendedProductMeta());
    });
  }

  if (fillFromReadyProductsButton) {
    fillFromReadyProductsButton.addEventListener('click', () => {
      applyComparisonRowsFromProducts(selectedRecommendedProductMeta({ onlyReady: true }));
    });
  }

  reindexRowCells();
  const slugifyForAdmin = (value) => value
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

  const syncAutoSlugField = (group) => {
    const source = document.querySelector(`[data-auto-slug-source="${group}"]`);
    const target = document.querySelector(`[data-auto-slug-target="${group}"]`);
    if (!(source instanceof HTMLInputElement) || !(target instanceof HTMLInputElement)) {
      return;
    }

    const apply = () => {
      const nextValue = slugifyForAdmin(source.value || '');
      if (target.value.trim() === '' || target.dataset.autoSlugDirty !== 'true') {
        target.value = nextValue;
      }
    };

    target.addEventListener('input', () => {
      target.dataset.autoSlugDirty = target.value.trim() !== '' ? 'true' : 'false';
    });

    source.addEventListener('input', apply);
    if (target.value.trim() === '') {
      apply();
    }
  };

  syncAutoSlugField('product');
  syncAutoSlugField('merchant');


  document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-copy-value]');
    if (!(trigger instanceof HTMLButtonElement)) {
      return;
    }

    const value = trigger.getAttribute('data-copy-value') || '';
    if (!value) {
      return;
    }

    try {
      await navigator.clipboard.writeText(value);
      const original = trigger.textContent;
      trigger.textContent = 'Skopirovane';
      window.setTimeout(() => {
        trigger.textContent = original;
      }, 1200);
    } catch (error) {
      console.error(error);
    }
  });
});
