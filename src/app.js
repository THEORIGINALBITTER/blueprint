(() => {
  const fields = document.querySelectorAll('[data-field]');

  fetch('./content.php?ts=' + Date.now(), { cache: 'no-store' })
    .then((response) => response.ok ? response.json() : fetch('/api/content', { cache: 'no-store' }).then((fallback) => fallback.ok ? fallback.json() : Promise.reject()))
    .then((content) => fields.forEach((element) => {
      const value = content[element.dataset.field];
      if (typeof value === 'string') element.textContent = value;
    }))
    .catch(() => { /* The static copy in index.html remains the graceful fallback. */ });
})();
