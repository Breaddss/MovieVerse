(function () {
  const input   = document.getElementById('search-input');
  const results = document.getElementById('search-results');
  const status  = document.getElementById('search-status');
  if (!input || !results) return;

  let timer = null;

  function card(m) {
    const hasPoster = m.Poster && m.Poster !== 'N/A';
    const poster = hasPoster
      ? `<img src="${m.Poster}" alt="${escapeHtml(m.Title)} poster" loading="lazy">`
      : `<div class="poster-fallback">${escapeHtml(m.Title)}</div>`;

    return `
      <a class="movie-card" href="${window.BASE_URL}/movie.php?id=${encodeURIComponent(m.imdbID)}">
        <div class="poster-wrap">${poster}</div>
        <div class="movie-card-body">
          <h3>${escapeHtml(m.Title)}</h3>
          <div class="year">${escapeHtml(m.Year || '')}</div>
        </div>
      </a>`;
  }

  function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s == null ? '' : s;
    return d.innerHTML;
  }

  async function runSearch(q) {
    status.innerHTML = '<div class="spinner"></div>';
    results.innerHTML = '';
    try {
      const res  = await fetch(`${window.BASE_URL}/php/search.php?q=${encodeURIComponent(q)}`);
      const data = await res.json();

      if (!data.ok || !data.results.length) {
        status.innerHTML = `<p class="empty">No movies found for "<strong>${escapeHtml(q)}</strong>".</p>`;
        return;
      }
      status.textContent = `Found ${data.results.length} result(s)`;
      results.innerHTML = data.results.map(card).join('');
    } catch (err) {
      status.innerHTML = '<p class="alert alert-error">Search failed. Please try again.</p>';
    }
  }

  input.addEventListener('input', () => {
    const q = input.value.trim();
    clearTimeout(timer);
    if (q.length < 2) { status.textContent = ''; results.innerHTML = ''; return; }
    timer = setTimeout(() => runSearch(q), 350);
  });

  const params = new URLSearchParams(location.search);
  if (params.get('q')) { input.value = params.get('q'); runSearch(params.get('q').trim()); }
})();
