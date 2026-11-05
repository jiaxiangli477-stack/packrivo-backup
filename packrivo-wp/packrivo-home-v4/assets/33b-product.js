(function () {
  'use strict';
  function boot() {
    document.querySelectorAll('[data-pk33b-gallery]').forEach(function (gallery) {
      var main = gallery.querySelector('[data-pk33b-main]');
      var caption = gallery.querySelector('[data-pk33b-caption]');
      if (!main) return;
      gallery.querySelectorAll('[data-pk33b-image]').forEach(function (link) {
        link.addEventListener('click', function (event) {
          event.preventDefault();
          main.src = link.href;
          main.alt = link.getAttribute('data-alt') || '';
          if (caption) caption.textContent = link.getAttribute('data-caption') || '';
          gallery.querySelectorAll('[data-pk33b-image]').forEach(function (item) { item.removeAttribute('aria-current'); });
          link.setAttribute('aria-current', 'true');
        });
      });
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
