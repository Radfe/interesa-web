document.addEventListener('click', async function (event) {
  const button = event.target.closest('[data-copy-product-url]');
  if (!button) {
    return;
  }

  const productUrl = button.getAttribute('data-copy-product-url') || '';
  if (!productUrl) {
    return;
  }

  try {
    await navigator.clipboard.writeText(productUrl);
    button.textContent = 'URL skopirovana';
  } catch (error) {
    button.textContent = 'Skopiruj URL rucne';
  }
});