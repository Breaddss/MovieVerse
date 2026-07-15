function toast(message, type = 'info') {
  let box = document.getElementById('toast-box');
  if (!box) {
    box = document.createElement('div');
    box.id = 'toast-box';
    box.style.cssText =
      'position:fixed;bottom:20px;right:20px;display:flex;flex-direction:column;gap:10px;z-index:999';
    document.body.appendChild(box);
  }
  const t = document.createElement('div');
  const colors = { info: '#16324F', success: '#2E8B57', error: '#e5484d' };
  t.textContent = message;
  t.style.cssText =
    `background:${colors[type] || colors.info};color:#fff;padding:12px 18px;` +
    'border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.2);font-size:.92rem;' +
    'opacity:0;transform:translateY(10px);transition:all .25s';
  box.appendChild(t);
  requestAnimationFrame(() => { t.style.opacity = '1'; t.style.transform = 'translateY(0)'; });
  setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 250); }, 2800);
}


async function apiPost(endpoint, payload) {
  payload = payload || {};
  payload.csrf_token = window.CSRF_TOKEN;

  const res = await fetch(window.BASE_URL + endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  return res.json();
}

document.addEventListener('click', async (ev) => {
  const btn = ev.target.closest('[data-action]');
  if (!btn) return;

  const action = btn.dataset.action;
  const imdb   = btn.dataset.imdb;

  if (action === 'add-watchlist' || action === 'remove-watchlist') {
    ev.preventDefault();
    const add = action === 'add-watchlist';
    const r = await apiPost('/php/watchlist.php', { action: add ? 'add' : 'remove', imdb_id: imdb });
    if (r.ok) {
      toast(add ? 'Added to your watchlist' : 'Removed from watchlist', 'success');
      if (btn.dataset.toggle === '1') {
        if (add) {
          btn.dataset.action = 'remove-watchlist';
          btn.textContent = '✓ In watchlist';
          btn.classList.remove('btn-primary'); btn.classList.add('btn-outline');
        } else {
          btn.dataset.action = 'add-watchlist';
          btn.textContent = '+ Watchlist';
          btn.classList.add('btn-primary'); btn.classList.remove('btn-outline');
        }
      } else if (btn.dataset.removeRow === '1') {
        btn.closest('[data-row]')?.remove();
      }
    } else {
      toast(r.error || 'Something went wrong', 'error');
    }
  }

  if (action === 'toggle-watched') {
    ev.preventDefault();
    const r = await apiPost('/php/watchlist.php', { action: 'toggle_watched', imdb_id: imdb });
    if (r.ok) {
      btn.textContent = r.watched ? '✓ Watched' : 'Mark watched';
      btn.classList.toggle('btn-primary', r.watched);
      btn.classList.toggle('btn-outline', !r.watched);
      toast(r.watched ? 'Marked as watched' : 'Marked as not watched', 'success');
    } else {
      toast(r.error || 'Error', 'error');
    }
  }

  if (action === 'delete-review') {
    ev.preventDefault();
    if (!confirm('Delete this review?')) return;
    const r = await apiPost('/php/review.php', { action: 'delete', imdb_id: imdb });
    if (r.ok) { toast('Review deleted', 'success'); location.reload(); }
    else toast(r.error || 'Error', 'error');
  }
});

document.querySelectorAll('.rate-input').forEach((group) => {
  const hidden = group.parentElement.querySelector('input[name="rating"]');
  group.querySelectorAll('button').forEach((b) => {
    b.addEventListener('click', (e) => {
      e.preventDefault();
      const val = parseInt(b.dataset.val, 10);
      if (hidden) hidden.value = val;
      group.querySelectorAll('button').forEach((x) =>
        x.classList.toggle('active', parseInt(x.dataset.val, 10) <= val));
    });
  });
});

const reviewForm = document.getElementById('review-form');
if (reviewForm) {
  reviewForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const rating = reviewForm.querySelector('input[name="rating"]').value;
    const text   = reviewForm.querySelector('textarea[name="review_text"]').value;
    const imdb   = reviewForm.dataset.imdb;
    if (!rating) { toast('Please pick a rating from 1 to 10', 'error'); return; }

    const r = await apiPost('/php/review.php', { action: 'save', imdb_id: imdb, rating, review_text: text });
    if (r.ok) { toast('Review saved', 'success'); location.reload(); }
    else toast(r.error || 'Could not save review', 'error');
  });
}
