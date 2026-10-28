/* Nav v4 — click to open, Escape/outside to close, mobile drawer.
   Fixes the live theme's hover-only top-level items and the <summary> overlay. */
(function () {
  'use strict';

  var header = document.querySelector('[data-pk-header]');
  if (!header) return;

  var nav = header.querySelector('#pk-nav');
  var burger = header.querySelector('[data-pk-burger]');
  var toggles = Array.prototype.slice.call(header.querySelectorAll('[data-pk-toggle]'));

  function panelFor(btn) {
    return document.getElementById(btn.getAttribute('aria-controls'));
  }

  function closeAll(except) {
    toggles.forEach(function (btn) {
      if (btn === except) return;
      btn.setAttribute('aria-expanded', 'false');
      var panel = panelFor(btn);
      if (panel) panel.hidden = true;
    });
  }

  toggles.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      var panel = panelFor(btn);
      if (!panel) return;
      var open = btn.getAttribute('aria-expanded') === 'true';
      closeAll(btn);
      btn.setAttribute('aria-expanded', open ? 'false' : 'true');
      panel.hidden = open;
      var base = btn.getAttribute('data-label') || btn.getAttribute('aria-label') || '';
      if (base) {
        btn.setAttribute('data-label', base.replace(/^(Open|Close) /, ''));
        btn.setAttribute('aria-label', (open ? 'Open ' : 'Close ') + base.replace(/^(Open|Close) /, ''));
      }
    });
  });

  document.addEventListener('click', function (e) {
    if (!header.contains(e.target)) closeAll();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeAll();
      if (nav && nav.classList.contains('is-open')) {
        nav.classList.remove('is-open');
        if (burger) {
          burger.setAttribute('aria-expanded', 'false');
          burger.focus();
        }
      }
    }
  });

  // 28p: 汉堡断点由 CSS 决定（site.css 里是 max-width:1294px）。这里不再写死宽度数字，
  // 直接看汉堡按钮是否可见——以后调断点只改 CSS 一处，JS 不会和它脱节。
  function drawerMode() {
    return !!burger && window.getComputedStyle(burger).display !== 'none';
  }

  if (burger && nav) {
    var burgerText = burger.querySelector('.pk-burger-text');
    burger.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (burgerText) burgerText.textContent = open ? 'Close' : 'Menu';
      if (!open) closeAll();
    });

    nav.addEventListener('click', function (e) {
      var link = e.target.closest('a');
      if (link && drawerMode()) {
        nav.classList.remove('is-open');
        burger.setAttribute('aria-expanded', 'false');
        if (burgerText) burgerText.textContent = 'Menu';
      }
    });
  }

  window.addEventListener('resize', function () {
    if (!drawerMode() && nav) {
      nav.classList.remove('is-open');
      if (burger) burger.setAttribute('aria-expanded', 'false');
      closeAll();
    }
  });

  // Fold the theme's floating language selector into the header row.
  var langRoot = document.getElementById('packrivo-language-root');
  var actions = document.querySelector('.pk-actions');
  var chip = document.querySelector('.pk-lang');
  if (langRoot && actions) {
    actions.insertBefore(langRoot, actions.firstChild);
    langRoot.classList.add('is-inline');
    if (chip) chip.hidden = true;
  }
})();
