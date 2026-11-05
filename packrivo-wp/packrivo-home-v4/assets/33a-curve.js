(function () {
  'use strict';
  function boot() {
    var root = document.querySelector('[data-pk33a-curve]');
    if (!root || !window.PKQ || !window.PKQPricing) return;
    var slider = root.querySelector('input[type="range"]');
    var output = root.querySelector('[data-curve-quantity]');
    function setValue(selector, text) {
      var node = root.querySelector(selector);
      if (!node) return;
      var direction = document.createElement('bdi');
      direction.dir = 'ltr';
      direction.textContent = text;
      node.replaceChildren(direction);
    }
    function update() {
      var qty = Number(slider.value);
      var result = window.PKQPricing.price({style:'mailer',material:'kraft',print:'full-color',finish:'matte',qty:qty,length:30,width:22,height:12,rush:false}, window.PKQ);
      var money = function (n) { return '$' + Number(n).toLocaleString('en-US', {minimumFractionDigits:2,maximumFractionDigits:2}); };
      setValue('[data-curve-quantity]', qty.toLocaleString('en-US'));
      setValue('[data-curve-goods]', money(result.goods_total));
      setValue('[data-curve-fee]', money(result.fee));
      setValue('[data-curve-unit]', money(result.unit));
      setValue('[data-curve-total]', money(result.total));
      if (output) output.setAttribute('aria-label', String(qty));
    }
    slider.disabled = false;
    slider.addEventListener('input', update);
    update();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
