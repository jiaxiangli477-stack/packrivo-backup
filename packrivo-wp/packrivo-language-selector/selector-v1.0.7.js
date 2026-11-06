(() => {
  'use strict';

  const config = window.PackrivoLanguageSelector || {};
  const rows = Array.isArray(config.languages) ? config.languages : [];
  const known = new Set(rows.map(row => row.code));
  const current = known.has(config.current) ? config.current : 'en';

  function init() {
    const root = document.getElementById('packrivo-language-root');
    const header = document.querySelector('.site-header');
    if (!root) return;
    if (header) header.append(root);
    else document.body.prepend(root);

    const dialog = document.getElementById('packrivo-language-dialog');
    const trigger = document.getElementById('packrivo-language-trigger');
    const search = document.getElementById('packrivo-language-search');
    const list = document.getElementById('packrivo-language-list');
    const status = document.getElementById('packrivo-language-status');
    let previousOverflow = '';

    const setState = lang => {
      document.getElementById('packrivo-language-code').textContent = lang.toUpperCase();
      list.querySelectorAll('a[data-lang]').forEach(button => {
        if (button.dataset.lang === lang) button.setAttribute('aria-current', 'page');
        else button.removeAttribute('aria-current');
      });
    };
    const close = () => { if (dialog.open) dialog.close(); };

    trigger.addEventListener('click', () => {
      previousOverflow = document.body.style.overflow;
      list.before(status);
      dialog.showModal();
      document.body.style.overflow = 'hidden';
      trigger.setAttribute('aria-expanded', 'true');
      search.focus();
    });
    document.getElementById('packrivo-language-close').addEventListener('click', close);
    dialog.addEventListener('keydown', event => {
      if (event.key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();
        close();
      }
    }, true);
    dialog.addEventListener('close', () => {
      root.append(status);
      document.body.style.overflow = previousOverflow;
      trigger.setAttribute('aria-expanded', 'false');
      trigger.focus();
    });
    dialog.addEventListener('click', event => {
      const rect = dialog.getBoundingClientRect();
      if (event.target === dialog && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) close();
    });

    for (const row of rows) {
      const anchor = list.querySelector(`a[data-lang="${row.code}"]`);
      if (anchor) anchor.dataset.search = [row.code, row.label, row.english, row.aliases || ''].join(' ').toLocaleLowerCase();
    }

    search.addEventListener('input', () => {
      const query = search.value.trim().toLocaleLowerCase();
      let visible = 0;
      list.querySelectorAll('a[data-lang]').forEach(button => {
        button.hidden = !button.dataset.search.includes(query);
        if (!button.hidden) visible++;
      });
      document.getElementById('packrivo-language-empty').hidden = visible > 0;
    });

    setState(current);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
  else init();
})();
