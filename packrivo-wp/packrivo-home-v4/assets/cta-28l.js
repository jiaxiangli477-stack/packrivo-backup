/* Keep quote destinations in the visitor's language; text and pricing stay unchanged. */
(function () {
  'use strict';
  var config = window.pkv4QuoteTargets || {}, supported = ['ar','de','es','fr','it','ja','ko','nl','pt','zh-CN','zh-TW'];
  var segment = location.pathname.split('/')[1], lang = supported.indexOf(segment) >= 0 ? segment : '';
  var site = new URL(config.base || '/', location.href);

  function rewrite(link) {
    if (!link || !link.getAttribute('href')) return;
    var u;
    try { u = new URL(link.getAttribute('href'), location.href); } catch (_) { return; }
    // Only local site links are handled; never alter WhatsApp or external links.
    if (u.origin !== location.origin && u.origin !== site.origin && u.hostname !== 'www.packrivo.com') return;
    var normalized = u.pathname.replace(/^\/(?:ar|de|es|fr|it|ja|ko|nl|pt|zh-CN|zh-TW)(?=\/|$)/, '');
    var quotePath = /^\/get-a-quote\/?$/.test(normalized), inquiryPath = /^\/inquiry\/?$/.test(normalized);
    var sticky = link.classList.contains('pr-sticky-main');
    var quoteText = /(?:\bquote\b|\bquotation\b|\bprice\b|\bconfigure\b)/i.test(link.textContent || '');
    if (!quotePath && !(inquiryPath && (lang || sticky || quoteText || !!config.product))) return;
    var product = u.searchParams.get('product') || config.product || '';
    var isCurrentProduct = !!product && !!config.product && product.replace(/^product-/, '') === config.product.replace(/^product-/, '');
    if (isCurrentProduct) product = config.product;
    var target = new URL(lang ? '/' + lang + '/inquiry/' : '/get-a-quote/', site);
    if (product) {
      if (lang) {
        target.searchParams.set('product', product.replace(/^product-/, ''));
      } else {
        var style = u.searchParams.get('style') || (isCurrentProduct ? config.style : '') || '';
        target.searchParams.set('style', style);
        target.searchParams.set('qty', '500');
        target.searchParams.set('product', product);
      }
    } else if (!lang && quotePath) {
      // Preserve an existing explicit non-product quote preset if present.
      ['style', 'qty'].forEach(function (key) {
        if (u.searchParams.has(key)) target.searchParams.set(key, u.searchParams.get(key));
      });
    }
    var relative = target.pathname + target.search;
    if (link.getAttribute('href') !== relative) link.setAttribute('href', relative);
  }

  function scan(root) {
    if (root.nodeType !== 1 && root.nodeType !== 9) return;
    if (root.matches && root.matches('a[href]')) rewrite(root);
    root.querySelectorAll('a[href]').forEach(rewrite);
  }
  function start() {
    scan(document);
    new MutationObserver(function (records) {
      records.forEach(function (record) { record.addedNodes.forEach(scan); });
    }).observe(document.documentElement, {childList:true, subtree:true});
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
  // A late-inserted sticky CTA is also corrected synchronously before navigation.
  document.addEventListener('click', function (event) { rewrite(event.target.closest && event.target.closest('a[href]')); }, true);
}());
