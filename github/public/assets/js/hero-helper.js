document.addEventListener('click', async (event) => {
  const button = event.target.closest('.js-copy-prompt');
  if (!button) {
    return;
  }

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
});