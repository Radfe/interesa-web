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
  const fillFromCardReadyButton = document.querySelector('[data-fill-card-ready]');
  const selectCardReadyProductsButton = document.querySelector('[data-select-card-ready-products]');
  const selectMoneyReadyProductsButton = document.querySelector('[data-select-money-ready-products]');
  const clearProductSelectionButton = document.querySelector('[data-clear-product-selection]');
  const fillFromReadyShortlistButton = document.querySelector('[data-fill-ready-shortlist]');
  const fillMoneyScaffoldButton = document.querySelector('[data-fill-money-scaffold]');
  const syncProductsFromComparisonButton = document.querySelector('[data-sync-products-from-comparison]');
  const productRatingButtons = Array.from(document.querySelectorAll('[data-set-product-rating]'));
  const fillProductRatingAutoButton = document.querySelector('[data-fill-product-rating-auto]');
  const fillProductEmptyButton = document.querySelector('[data-fill-product-empty]');
  const fillProductSummaryButton = document.querySelector('[data-fill-product-summary]');
  const fillProductProsButton = document.querySelector('[data-fill-product-pros]');
  const fillProductConsButton = document.querySelector('[data-fill-product-cons]');
  const fillProductAllButton = document.querySelector('[data-fill-product-all]');
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

  const recommendedCheckboxes = () => Array.from(document.querySelectorAll('input[name="recommended_product_checks[]"]'))

  const setRecommendedSelection = (predicate) => {
    recommendedCheckboxes().forEach((input) => {
      if (!(input instanceof HTMLInputElement)) {
        return;
      }
      input.checked = predicate(input);
    });

    const checkedSlugs = recommendedCheckboxes()
      .filter((input) => input instanceof HTMLInputElement && input.checked)
      .map((input) => input.value.trim())
      .filter(Boolean);

    const manualField = document.querySelector('textarea[name="recommended_products"]');
    if (manualField instanceof HTMLTextAreaElement) {
      manualField.value = checkedSlugs.join("\\n");
    }
  };

  const selectedRecommendedProductMeta = (options = {}) => {
    const onlyReady = options.onlyReady === true;
    const onlyCardReady = options.onlyCardReady === true;
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
        packshotReady: (input.dataset.productPackshotReady || '') === 'true',
        summaryReady: (input.dataset.productSummaryReady || '') === 'true',
        ratingReady: (input.dataset.productRatingReady || '') === 'true',
        prosReady: (input.dataset.productProsReady || '') === 'true',
        consReady: (input.dataset.productConsReady || '') === 'true',
        cardReady: (input.dataset.productCardReady || '') === 'true'
      });
    });

    return selectedRecommendedProducts()
      .map((slug) => lookup.get(slug) || ({ slug, name: slug, bestFor: '', merchant: '', rating: '', summary: '', affiliateReady: false, packshotReady: false, summaryReady: false, ratingReady: false, prosReady: false, consReady: false, cardReady: false }))
      .filter((product) => (!onlyReady || (product.affiliateReady && product.packshotReady)) && (!onlyCardReady || product.cardReady));
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
  const applyMoneyPageScaffold = (productRows) => {
    const cardReadyRows = productRows.filter((product) => product.cardReady);
    const readyRows = productRows.filter((product) => product.affiliateReady && product.packshotReady);
    const sourceRows = cardReadyRows.length > 0 ? cardReadyRows : (readyRows.length > 0 ? readyRows : productRows);
    const sortedRows = [...sourceRows].sort((left, right) => {
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

    const comparisonTitleField = document.querySelector("input[name=\"comparison_title\"]");
    const comparisonIntroField = document.querySelector("input[name=\"comparison_intro\"]");
    const articleTitleField = document.querySelector("input[name=\"title\"]");
    const articleIntroField = document.querySelector("textarea[name=\"intro\"]");
    const recommendedField = document.querySelector("textarea[name=\"recommended_products\"]");
    const sectionHeadings = Array.from(document.querySelectorAll("input[name=\"section_heading[]\"]"));
    const sectionBodies = Array.from(document.querySelectorAll("textarea[name=\"section_body[]\"]"));
    const articleTitle = articleTitleField instanceof HTMLInputElement ? articleTitleField.value.trim() : "";

    if (comparisonTitleField instanceof HTMLInputElement && comparisonTitleField.value.trim() === "") {
      comparisonTitleField.value = articleTitle !== "" ? `Rychle porovnanie: ${articleTitle}` : "Rychle porovnanie top produktov";
    }
    if (comparisonIntroField instanceof HTMLInputElement && comparisonIntroField.value.trim() === "") {
      comparisonIntroField.value = "Vyber najzaujimavejsich odporucanych produktov na rychle porovnanie podla vyuzitia, hodnotenia a pripravenosti na money page.";
    }
    if (articleIntroField instanceof HTMLTextAreaElement && articleIntroField.value.trim() === "") {
      articleIntroField.value = "Strucny prehlad top odporucanych produktov a najdolezitejsich rozdielov na jednom mieste.";
    }
    if (recommendedField instanceof HTMLTextAreaElement && recommendedField.value.trim() === "") {
      recommendedField.value = sortedRows.map((product) => product.slug).join("\\n");
    }
    if (sectionHeadings[0] instanceof HTMLInputElement && sectionHeadings[0].value.trim() === "") {
      sectionHeadings[0].value = "Rychly vyber";
    }
    if (sectionBodies[0] instanceof HTMLTextAreaElement && sectionBodies[0].value.trim() === "") {
      sectionBodies[0].value = "Strucny vyber top produktov a komu jednotlive moznosti davaju najvacsi zmysel.";
    }
    if (sectionHeadings[1] instanceof HTMLInputElement && sectionHeadings[1].value.trim() === "") {
      sectionHeadings[1].value = "Na co sa pri vybere zamerat";
    }
    if (sectionBodies[1] instanceof HTMLTextAreaElement && sectionBodies[1].value.trim() === "") {
      sectionBodies[1].value = "Pri porovnani sleduj hlavne vyuzitie produktu, formu, zlozenie, davkovanie a pomer hodnoty k cene.";
    }

    if (rowsField instanceof HTMLTextAreaElement) {
      rowsField.value = JSON.stringify(sortedRows.map((product) => ({
        product_slug: product.slug,
        best_for: product.bestFor || product.summary || product.name || "",
        rating: product.rating || "",
        cta: ""
      })), null, 2);
    }
    if (columnsField instanceof HTMLTextAreaElement) {
      columnsField.value = JSON.stringify(presets['top-picks'].columns, null, 2);
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

  const comparisonProductSlugs = () => {
    if (!ensureComparisonRoots()) {
      return [];
    }

    const columns = Array.from(columnsRoot.querySelectorAll("[data-comparison-column]"));
    const productColumnIndex = columns.findIndex((column) => {
      const typeSelect = column.querySelector("select[name=\"comparison_column_type[]\"]");
      const keyInput = column.querySelector("input[name=\"comparison_column_key[]\"]");
      const typeValue = typeSelect instanceof HTMLSelectElement ? typeSelect.value.trim() : "";
      const keyValue = keyInput instanceof HTMLInputElement ? keyInput.value.trim() : "";
      return typeValue === "product" || keyValue === "product_slug" || keyValue === "product";
    });

    if (productColumnIndex < 0) {
      return [];
    }

    return comparisonRows()
      .map((row) => {
        const cells = Array.from(row.querySelectorAll("textarea"));
        const cell = cells[productColumnIndex];
        return cell instanceof HTMLTextAreaElement ? cell.value.trim() : "";
      })
      .filter(Boolean)
      .filter((value, index, all) => all.indexOf(value) === index);
  };

  const syncRecommendedProductsFromComparison = () => {
    const slugs = comparisonProductSlugs();
    if (slugs.length === 0) {
      return;
    }

    const manualField = document.querySelector("textarea[name=\"recommended_products\"]");
    if (manualField instanceof HTMLTextAreaElement) {
      manualField.value = slugs.join("\\n");
    }

    Array.from(document.querySelectorAll("input[name=\"recommended_product_checks[]\"]")).forEach((input) => {
      if (!(input instanceof HTMLInputElement)) {
        return;
      }
      input.checked = slugs.includes(input.value.trim());
    });
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

  if (syncProductsFromComparisonButton) {
    syncProductsFromComparisonButton.addEventListener('click', () => {
      syncRecommendedProductsFromComparison();
    });
  }

  if (fillMoneyScaffoldButton) {
    fillMoneyScaffoldButton.addEventListener('click', () => {
      applyMoneyPageScaffold(selectedRecommendedProductMeta());
    });
  }

  if (fillFromReadyProductsButton) {
    fillFromReadyProductsButton.addEventListener('click', () => {
      applyComparisonRowsFromProducts(selectedRecommendedProductMeta({ onlyReady: true }));
    });
  }

  if (fillFromCardReadyButton) {
    fillFromCardReadyButton.addEventListener('click', () => {
      applyComparisonRowsFromProducts(selectedRecommendedProductMeta({ onlyCardReady: true }));
    });
  }

  reindexRowCells();
  const productField = (selector) => document.querySelector(selector);

  const productContext = () => {
    const nameField = productField("input[name=\"name\"]");
    const brandField = productField("input[name=\"brand\"]");
    const merchantField = productField("input[name=\"merchant\"]");
    const categoryField = productField("input[name=\"category\"]");
    return {
      name: nameField instanceof HTMLInputElement ? nameField.value.trim() : "",
      brand: brandField instanceof HTMLInputElement ? brandField.value.trim() : "",
      merchant: merchantField instanceof HTMLInputElement ? merchantField.value.trim() : "",
      category: categoryField instanceof HTMLInputElement ? categoryField.value.trim() : ""
    };
  };

  const productCategoryHint = (category) => {
    const value = (category || "").toLowerCase();
    if (value.includes("protein")) return "kazdodenne doplnenie bielkovin";
    if (value.includes("kreat")) return "vykon, silu a regeneraciu";
    if (value.includes("horc") || value.includes("miner")) return "kazdodennu suplementaciu a doplnenie mineralov";
    if (value.includes("kolagen") || value.includes("klb")) return "starostlivost o klby, pokozku a regeneraciu";
    if (value.includes("probiotik") || value.includes("traven")) return "travienie a rovnovahu mikrobiomu";
    if (value.includes("imunit")) return "dlhodobu podporu imunity";
    return "kazdodennu doplnkovu rutinu";
  };

  const buildProductSummary = () => {
    const context = productContext();
    const source = context.brand || context.merchant || "overeny merchant";
    const focus = productCategoryHint(context.category);
    const name = context.name || "Tento produkt";
    return `${name} od ${source} je doplnok vhodny pre ${focus}. V reusable katalogu sluzi ako zaklad pre odporucania, porovnania a money-page karty.`;
  };

  const buildProductPros = () => {
    const context = productContext();
    const focus = productCategoryHint(context.category);
    return [
      `jasne zameranie na ${focus}`,
      "vhodny do porovnani a odporucanych vyberov",
      "lahko sa zaradi do beznej suplementacnej rutiny"
    ].join("\\n");
  };

  const buildProductCons = () => [
    "treba skontrolovat zlozenie, davkovanie a formu produktu",
    "vysledna vhodnost zavisi od ciela a individualnej tolerancie",
    "pred zaradenim je dobre porovnat aj alternativy v rovnakej kategorii"
  ].join("\\n");

  const setProductFieldValue = (selector, value) => {
    const field = productField(selector);
    if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) {
      field.value = value;
      field.dispatchEvent(new Event("input", { bubbles: true }));
    }
  };

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


  if (fillProductSummaryButton) {
    fillProductSummaryButton.addEventListener('click', () => {
      setProductFieldValue("textarea[name=\"summary\"]", buildProductSummary());
    });
  }

  if (fillProductProsButton) {
    fillProductProsButton.addEventListener('click', () => {
      setProductFieldValue("textarea[name=\"pros\"]", buildProductPros());
    });
  }

  if (fillProductConsButton) {
    fillProductConsButton.addEventListener('click', () => {
      setProductFieldValue("textarea[name=\"cons\"]", buildProductCons());
    });
  }

  if (fillProductAllButton) {
    fillProductAllButton.addEventListener('click', () => {
      setProductFieldValue("textarea[name=\"summary\"]", buildProductSummary());
      setProductFieldValue("textarea[name=\"pros\"]", buildProductPros());
      setProductFieldValue("textarea[name=\"cons\"]", buildProductCons());
    });
  }

  let copyToastTimer = null;

  const ensureCopyToast = () => {
    let toast = document.querySelector('[data-admin-copy-toast]');
    if (toast instanceof HTMLElement) {
      return toast;
    }

    toast = document.createElement('div');
    toast.dataset.adminCopyToast = 'true';
    Object.assign(toast.style, {
      position: 'fixed',
      right: '20px',
      bottom: '20px',
      zIndex: '9999',
      maxWidth: 'min(360px, calc(100vw - 32px))',
      padding: '12px 16px',
      borderRadius: '12px',
      background: '#133b2c',
      color: '#fff',
      boxShadow: '0 12px 30px rgba(15, 23, 42, 0.22)',
      fontWeight: '600',
      opacity: '0',
      transform: 'translateY(10px)',
      pointerEvents: 'none',
      transition: 'opacity .18s ease, transform .18s ease'
    });
    toast.setAttribute('aria-live', 'polite');
    toast.setAttribute('aria-atomic', 'true');
    document.body.appendChild(toast);
    return toast;
  };

  const showCopyToast = (message, isError = false) => {
    const toast = ensureCopyToast();
    toast.textContent = message;
    toast.style.background = isError ? '#8a1c1c' : '#133b2c';
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';

    if (copyToastTimer) {
      window.clearTimeout(copyToastTimer);
    }

    copyToastTimer = window.setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px)';
    }, 2200);
  };

  const fallbackCopyValue = (value) => {
    const textarea = document.createElement('textarea');
    textarea.value = value;
    textarea.setAttribute('readonly', 'readonly');
    Object.assign(textarea.style, {
      position: 'fixed',
      top: '-9999px',
      left: '-9999px'
    });
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();

    let success = false;
    try {
      success = document.execCommand('copy');
    } catch (error) {
      success = false;
    }

    document.body.removeChild(textarea);
    return success;
  };

  const markCopyButton = (button) => {
    const originalText = button.dataset.copyOriginalText || button.textContent || 'Kopirovane';
    button.dataset.copyOriginalText = originalText;
    button.textContent = 'Skopirovane';
    button.classList.add('is-copied');

    window.setTimeout(() => {
      button.textContent = button.dataset.copyOriginalText || originalText;
      button.classList.remove('is-copied');
    }, 1400);
  };

  const copyValue = async (value) => {
    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function' && window.isSecureContext) {
      await navigator.clipboard.writeText(value);
      return true;
    }

    return fallbackCopyValue(value);
  };

  const imageUploadInputs = Array.from(document.querySelectorAll(
    'input[type="file"][name="hero_image"], input[type="file"][name="product_image"]'
  ));

  const normalizedUploadExtension = (file) => {
    if (!(file instanceof File)) {
      return '';
    }

    const parts = String(file.name || '').toLowerCase().split('.');
    if (parts.length < 2) {
      return '';
    }

    const ext = parts.pop() || '';
    return ext === 'jpeg' ? 'jpg' : ext;
  };

  const readUploadHeader = async (file, length = 16) => {
    if (!(file instanceof File) || typeof file.slice !== 'function') {
      return new Uint8Array();
    }

    const buffer = await file.slice(0, length).arrayBuffer();
    return new Uint8Array(buffer);
  };

  const detectUploadFormat = async (file) => {
    if (!(file instanceof File)) {
      return 'unknown';
    }

    const type = (file.type || '').toLowerCase();
    if (type === 'image/webp') {
      return 'webp';
    }
    if (['image/png'].includes(type)) {
      return 'png';
    }
    if (['image/jpeg', 'image/jpg'].includes(type)) {
      return 'jpg';
    }

    const ext = normalizedUploadExtension(file);
    if (['webp', 'png', 'jpg'].includes(ext)) {
      return ext;
    }

    const header = await readUploadHeader(file, 16);
    if (
      header.length >= 8 &&
      header[0] === 0x89 &&
      header[1] === 0x50 &&
      header[2] === 0x4e &&
      header[3] === 0x47 &&
      header[4] === 0x0d &&
      header[5] === 0x0a &&
      header[6] === 0x1a &&
      header[7] === 0x0a
    ) {
      return 'png';
    }

    if (
      header.length >= 3 &&
      header[0] === 0xff &&
      header[1] === 0xd8 &&
      header[2] === 0xff
    ) {
      return 'jpg';
    }

    if (
      header.length >= 12 &&
      header[0] === 0x52 &&
      header[1] === 0x49 &&
      header[2] === 0x46 &&
      header[3] === 0x46 &&
      header[8] === 0x57 &&
      header[9] === 0x45 &&
      header[10] === 0x42 &&
      header[11] === 0x50
    ) {
      return 'webp';
    }

    return 'unknown';
  };

  const isConvertibleUpload = (file) => {
    if (!(file instanceof File)) {
      return false;
    }

    const type = (file.type || '').toLowerCase();
    if (['image/png', 'image/jpeg', 'image/jpg'].includes(type)) {
      return true;
    }

    return ['png', 'jpg'].includes(normalizedUploadExtension(file));
  };

  const isWebpUpload = (file) => {
    if (!(file instanceof File)) {
      return false;
    }

    const type = (file.type || '').toLowerCase();
    if (type === 'image/webp') {
      return true;
    }

    return normalizedUploadExtension(file) === 'webp';
  };

  const renameToWebp = (name) => {
    const base = (name || 'image').replace(/\.[^.]+$/u, '') || 'image';
    return base + '.webp';
  };

  const setUploadFormBusy = (form, isBusy) => {
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    form.dataset.imageConverting = isBusy ? 'true' : 'false';
    form.querySelectorAll('button[type="submit"]').forEach((button) => {
      if (button instanceof HTMLButtonElement) {
        button.disabled = isBusy;
      }
    });
  };

  const loadImageFromFile = (file) => new Promise((resolve, reject) => {
    const objectUrl = URL.createObjectURL(file);
    const image = new Image();
    image.onload = () => {
      URL.revokeObjectURL(objectUrl);
      resolve(image);
    };
    image.onerror = () => {
      URL.revokeObjectURL(objectUrl);
      reject(new Error('image-load-failed'));
    };
    image.src = objectUrl;
  });

  const canvasToWebpBlob = (canvas, quality = 0.92) => new Promise((resolve, reject) => {
    canvas.toBlob((blob) => {
      if (blob) {
        resolve(blob);
        return;
      }
      reject(new Error('webp-conversion-failed'));
    }, 'image/webp', quality);
  });

  const createWebpFileFromUpload = async (file) => {
    if (!(file instanceof File)) {
      throw new Error('missing-upload-file');
    }

    const detectedFormat = await detectUploadFormat(file);
    if (detectedFormat === 'webp' || isWebpUpload(file)) {
      return file;
    }

    const image = await loadImageFromFile(file);
    const canvas = document.createElement('canvas');
    canvas.width = image.naturalWidth || image.width;
    canvas.height = image.naturalHeight || image.height;
    const context = canvas.getContext('2d', { alpha: true });
    if (!context) {
      throw new Error('canvas-context-failed');
    }

    context.drawImage(image, 0, 0);
    const blob = await canvasToWebpBlob(canvas);
    return new File([blob], renameToWebp(file.name), {
      type: 'image/webp',
      lastModified: Date.now()
    });
  };

  const convertUploadToWebp = async (input) => {
    if (!(input instanceof HTMLInputElement) || !input.files || input.files.length === 0) {
      return false;
    }

    const originalFile = input.files[0];
    if (isWebpUpload(originalFile)) {
      return false;
    }

    if (!isConvertibleUpload(originalFile)) {
      throw new Error('unsupported-upload-format');
    }

    const form = input.form;
    setUploadFormBusy(form, true);

    try {
      const webpFile = await createWebpFileFromUpload(originalFile);
      const transfer = new DataTransfer();
      transfer.items.add(webpFile);
      input.files = transfer.files;
      showCopyToast('Obrazok bol automaticky prevedeny na WebP.', false);
      return true;
    } finally {
      setUploadFormBusy(form, false);
    }
  };

  imageUploadInputs.forEach((input) => {
    input.addEventListener('change', () => {
      const selectedFile = input.files && input.files[0] ? input.files[0] : null;
      if (selectedFile) {
        showCopyToast('Obrazok sa pri nahrati automaticky pripravi pre WebP workflow.', false);
      }
    });
  });

  const imageUploadForms = Array.from(new Set(
    imageUploadInputs
      .map((input) => input.form)
      .filter((form) => form instanceof HTMLFormElement)
  ));

  const submitImageUploadForm = async (form) => {
    const uploadInputs = imageUploadInputs.filter((input) => input.form === form && input.files && input.files.length > 0);
    if (uploadInputs.length === 0) {
      return false;
    }

    setUploadFormBusy(form, true);

    try {
      const formData = new FormData(form);

      for (const input of uploadInputs) {
        const originalFile = input.files[0];
        if (!(originalFile instanceof File)) {
          continue;
        }

        const webpFile = await createWebpFileFromUpload(originalFile);
        formData.set(input.name, webpFile, webpFile.name);
      }

      const response = await fetch(form.action || window.location.href, {
        method: (form.method || 'POST').toUpperCase(),
        body: formData,
        credentials: 'same-origin'
      });

      if (response.redirected && response.url) {
        window.location.assign(response.url);
        return true;
      }

      const html = await response.text();
      if (typeof html === 'string' && html.trim() !== '') {
        document.open();
        document.write(html);
        document.close();
        return true;
      }

      window.location.reload();
      return true;
    } finally {
      setUploadFormBusy(form, false);
    }
  };

  imageUploadForms.forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      try {
        await submitImageUploadForm(form);
      } catch (error) {
        console.error(error);
        showCopyToast('Konverzia do WebP zlyhala. Nahraj iny obrazok.', true);
      }
    });
  });

  document.addEventListener('click', async (event) => {
    const trigger = event.target.closest('[data-copy-value]');
    if (!(trigger instanceof HTMLButtonElement)) {
      return;
    }

    event.preventDefault();

    const value = trigger.getAttribute('data-copy-value') || '';
    if (!value) {
      showCopyToast('Nic na kopirovanie.', true);
      return;
    }

    try {
      const copied = await copyValue(value);
      if (!copied) {
        throw new Error('copy-failed');
      }

      markCopyButton(trigger);
      showCopyToast('Skopirovane.');
    } catch (error) {
      console.error(error);
      showCopyToast('Kopirovanie zlyhalo. Skus znova.', true);
    }
  });

});
