/* Packrivo quote configurator — vanilla JS, no dependencies. */
(function () {
  'use strict';

  var MATERIALS = {
    kraft: { label: 'Kraft E-flute', factor: 1.0, note: 'Recycled, natural finish, best value' },
    white: { label: 'White E-flute', factor: 1.06, note: 'Bright print surface' },
    recycled: { label: '100% recycled board', factor: 1.1, note: 'Certification subject to supplier verification' },
    corrugated: { label: 'Double-wall corrugated', factor: 1.22, note: 'Heavy-duty transit' },
    rigidboard: { label: 'Rigid greyboard 2 mm', factor: 1.75, note: 'Luxury wrap construction' },
    kraftpoly: { label: 'Kraft-look poly', factor: 0.92, note: 'Water resistant' },
    compostable: { label: 'Compostable stock', factor: 1.38, note: 'Compostability subject to supplier verification' }
  };

  var PRINTS = {
    none: { label: 'Unprinted', add: 0 },
    'one-color': { label: '1-colour flexo', add: 0.09 },
    'full-color': { label: 'Full-colour CMYK', add: 0.18 },
    'full-color-spot': { label: 'CMYK + spot', add: 0.29 }
  };

  var FINISHES = {
    none: { label: 'None', add: 0 },
    matte: { label: 'Matte varnish', add: 0.06 },
    gloss: { label: 'Gloss varnish', add: 0.05 },
    'soft-touch': { label: 'Soft-touch lamination', add: 0.13 },
    'spot-uv': { label: 'Spot UV', add: 0.16 },
    foil: { label: 'Foil stamp', add: 0.35 },
    emboss: { label: 'Blind emboss', add: 0.24 }
  };

  var TIERS = [500, 1000, 2500, 5000, 10000, 25000];
  var TIER_FACTORS = [1.0, 0.93, 0.85, 0.78, 0.72, 0.66];

  var STYLE_DIMS = {
    mailer: [[25, 20, 10], [30, 22, 12], [40, 30, 15]],
    product: [[15, 12, 6], [22, 16, 9], [30, 22, 12]],
    shipping: [[30, 25, 20], [40, 30, 25], [50, 40, 35]],
    rigid: [[16, 12, 5], [24, 18, 7], [32, 24, 10]],
    carton: [[12, 4, 16], [8, 8, 8], [18, 6, 24]],
    pouch: [[16, 6, 23], [19, 7, 28], [23, 9, 33]],
    // Thickness is the middle axis; the server rejects anything under 2 cm, so the
    // old 1 cm preset could be selected and then bounced on submit.
    poly: [[25, 2, 35], [32, 2, 45], [40, 2, 55]],
    bag: [[18, 8, 22], [25, 11, 30], [32, 12, 40]],
    sticker: [[8, 8, 0.1], [15, 10, 0.1], [15, 11, 0.1]]
  };

  function quantityFactor(qty, cfg) {
    var tiers = (cfg && cfg.tiers && cfg.tiers.length) ? cfg.tiers : TIERS.map(function (q, i) { return [q, TIER_FACTORS[i]]; });
    var floor = (cfg && typeof cfg.tierFloor === 'number' && cfg.tierFloor > 0) ? cfg.tierFloor : 0.64;
    if (qty <= tiers[0][0]) return tiers[0][1];
    for (var i = 1; i < tiers.length; i++) {
      if (qty <= tiers[i][0]) {
        var t = (qty - tiers[i - 1][0]) / (tiers[i][0] - tiers[i - 1][0]);
        return tiers[i - 1][1] + (tiers[i][1] - tiers[i - 1][1]) * t;
      }
    }
    return floor;
  }

  function sizeFactor(l, w, h, cfg) {
    var area = 2 * (l * w + l * h + w * h);
    var d = cfg.sizeRef || [30, 20, 10];
    var ref = 2 * (d[0] * d[1] + d[0] * d[2] + d[1] * d[2]);
    return Math.pow(area / ref, 0.6);
  }

  /** PHP rounds dimensions to 1 dp before pricing; the browser must use the same rule. */
  function dim(v) {
    var n = parseFloat(v);
    if (!isFinite(n)) return 1;
    n = Math.round(n * 10) / 10;
    return Math.min(120, Math.max(2, n));
  }

  function price(state, cfg) {
    var style = cfg.styles[state.style] || { base: 1.4 };
    var mat = MATERIALS[state.material] || MATERIALS.kraft;
    var print = PRINTS[state.print] || PRINTS.none;
    var finish = FINISHES[state.finish] || FINISHES.none;
    var qty = Math.min(cfg.maxQty, Math.max(cfg.minQty, Math.round(state.qty)));
    var tier = quantityFactor(qty, cfg);
    var size = sizeFactor(dim(state.length), dim(state.width), dim(state.height), cfg);
    var printed = state.print !== 'none' || state.finish !== 'none';
    var setup = 0;
    if (printed) {
      if (qty < Number((cfg.rules || {}).freePrintSetupFrom || 2500)) setup += cfg.setupPrint;
      if (qty < Number((cfg.rules || {}).freeDieFrom || 5000)) setup += cfg.setupDie;
    }
    var rush = state.rush ? 1 + cfg.rushPercent / 100 : 1;
    var unit = (style.base * mat.factor * size + print.add + finish.add) * tier * rush + setup / qty;
    return {
      unit: Math.round(unit * 100) / 100,
      total: Math.round(unit * qty * 100) / 100,
      setup: setup,
      qty: qty
    };
  }

  function money(n) {
    var v = Math.round(n * 100) / 100;
    return '$' + v.toLocaleString('en-US', { minimumFractionDigits: v < 10 ? 2 : 0, maximumFractionDigits: 2 });
  }

  /** wp_localize_script hands numbers over as strings; coerce everything once. */
  function normaliseConfig(cfg) {
    ['minQty', 'maxQty', 'setupPrint', 'setupDie', 'rushPercent'].forEach(function (k) {
      cfg[k] = Number(cfg[k]) || 0;
    });
    Object.keys(cfg.styles || {}).forEach(function (k) {
      cfg.styles[k].base = Number(cfg.styles[k].base) || 0;
    });

    // Labels and coefficients come from pricing.json via wp_localize; the local
    // tables stay as a fallback but must not win when the server sends values.
    var groups = { materials: MATERIALS, prints: PRINTS, finishes: FINISHES };
    Object.keys(groups).forEach(function (group) {
      var incoming = cfg[group];
      if (!incoming) return;
      Object.keys(incoming).forEach(function (key) {
        var local = groups[group][key];
        if (!local) return;
        if (incoming[key].label) local.label = incoming[key].label;
        if (typeof incoming[key].factor === 'number') local.factor = incoming[key].factor;
        if (typeof incoming[key].add === 'number') local.add = incoming[key].add;
      });
    });

    return cfg;
  }

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text !== undefined) n.textContent = text;
    return n;
  }

  function mount(root) {
    var cfg = window.PKQ;
    if (!cfg) return;
    normaliseConfig(cfg);

    var requestedStyle = root.getAttribute('data-style') || '';
    var needsStyleChoice = !!root.getAttribute('data-product') && !cfg.styles[requestedStyle];
    var attrNum = function (name, fallback) {
      var v = parseFloat(root.getAttribute(name));
      return isFinite(v) && v > 0 ? v : fallback;
    };
    var attrStr = function (name) { return root.getAttribute(name) || ''; };
    var state = {
      style: needsStyleChoice ? '' : (requestedStyle || 'mailer'),
      material: 'kraft',
      print: 'full-color',
      finish: 'matte',
      qty: parseInt(root.getAttribute('data-qty'), 10) || 1000,
      length: attrNum('data-length', 30),
      width: attrNum('data-width', 22),
      height: attrNum('data-height', 12),
      rush: false,
      step: 0
    };

    if (!cfg.styles[state.style] && !needsStyleChoice) state.style = Object.keys(cfg.styles)[0];
    // The card reference spec wins when the page hands one over; the style default
    // below is only the fallback for a bare visit.
    if (attrStr('data-material')) state.material = attrStr('data-material');
    else if (state.style === 'rigid') state.material = 'rigidboard';
    if (!attrStr('data-material') && (state.style === 'pouch' || state.style === 'poly')) state.material = 'kraftpoly';
    if (attrStr('data-print')) state.print = attrStr('data-print');
    if (attrStr('data-finish')) state.finish = attrStr('data-finish');

    root.innerHTML = '';
    var shell = el('div', 'pkq-shell');
    var head = el('div', 'pkq-head');
    head.appendChild(el('p', 'pkq-eyebrow', 'Instant quote'));
    head.appendChild(el('h2', 'pkq-title', cfg.i18n.title));
    head.appendChild(el('p', 'pkq-sub', cfg.i18n.sub));
    shell.appendChild(head);

    var steps = el('div', 'pkq-steps');
    ['Style', 'Size', 'Material', 'Quantity', 'Details'].forEach(function (label, i) {
      var b = el('button', 'pkq-step', String(i + 1).padStart(2, '0') + ' ' + label);
      b.type = 'button';
      b.addEventListener('click', function () {
        state.step = i;
        render();
      });
      steps.appendChild(b);
    });
    shell.appendChild(steps);

    var grid = el('div', 'pkq-grid');
    var main = el('div', 'pkq-main');
    var aside = el('aside', 'pkq-aside');
    grid.appendChild(main);
    grid.appendChild(aside);
    shell.appendChild(grid);
    root.appendChild(shell);

    function optionRow(title, entries, activeKey, onPick, note) {
      var wrap = el('div', 'pkq-options');
      wrap.appendChild(el('p', 'pkq-label', title));
      var row = el('div', 'pkq-chips');
      entries.forEach(function (entry) {
        var b = el('button', 'pkq-chip' + (entry.key === activeKey ? ' is-active' : ''), entry.label);
        b.type = 'button';
        b.addEventListener('click', function () {
          onPick(entry.key);
          render();
        });
        row.appendChild(b);
      });
      wrap.appendChild(row);
      if (note) wrap.appendChild(el('p', 'pkq-note', note));
      return wrap;
    }

    function render() {
      Array.prototype.forEach.call(steps.children, function (b, i) {
        b.className = 'pkq-step' + (i === state.step ? ' is-active' : '') + (i < state.step ? ' is-done' : '');
        b.disabled = !state.style && i > 0;
      });

      main.innerHTML = '';
      var p = price(state, cfg);

      if (state.step === 0) {
        main.appendChild(el('h3', 'pkq-h3', 'What are we making?'));
        var tiles = el('div', 'pkq-tiles');
        Object.keys(cfg.styles).forEach(function (key) {
          var s = cfg.styles[key];
          var b = el('button', 'pkq-tile' + (key === state.style ? ' is-active' : ''));
          b.type = 'button';
          b.appendChild(el('span', 'pkq-tile-name', s.label));
          b.appendChild(el('span', 'pkq-tile-price', 'from ' + money(price(Object.assign({}, state, { style: key, qty: 500 }), cfg).unit)));
          b.addEventListener('click', function () {
            state.style = key;
            if (key === 'rigid') state.material = 'rigidboard';
            else if (key === 'pouch' || key === 'poly') state.material = 'kraftpoly';
            else if (state.material === 'rigidboard' || state.material === 'kraftpoly') state.material = 'kraft';
            var dims = STYLE_DIMS[key] || STYLE_DIMS.mailer;
            state.length = dims[1][0];
            state.width = dims[1][1];
            state.height = dims[1][2];
            render();
          });
          tiles.appendChild(b);
        });
        main.appendChild(tiles);
      }

      if (state.step === 1) {
        main.appendChild(el('h3', 'pkq-h3', 'Size'));
        main.appendChild(el('p', 'pkq-note', 'External dimensions in centimetres. Add 3–5 mm per axis for the product.'));
        var presets = el('div', 'pkq-chips');
        (STYLE_DIMS[state.style] || STYLE_DIMS.mailer).forEach(function (d) {
          var active = state.length === d[0] && state.width === d[1];
          var b = el('button', 'pkq-chip' + (active ? ' is-active' : ''), d[0] + '×' + d[1] + '×' + d[2]);
          b.type = 'button';
          b.addEventListener('click', function () {
            state.length = d[0];
            state.width = d[1];
            state.height = d[2];
            render();
          });
          presets.appendChild(b);
        });
        main.appendChild(presets);

        var dims = el('div', 'pkq-dims');
        [['length', 'Length'], ['width', 'Width'], ['height', 'Height']].forEach(function (pair) {
          var field = el('label', 'pkq-field');
          field.appendChild(el('span', 'pkq-label', pair[1] + ' (cm)'));
          var input = el('input', 'pkq-input');
          input.type = 'number';
          input.min = '1';
          input.max = '120';
          input.step = '0.5';
          input.value = state[pair[0]];
          input.addEventListener('input', function () {
            state[pair[0]] = parseFloat(input.value) || 1;
            updateAside();
          });
          field.appendChild(input);
          dims.appendChild(field);
        });
        main.appendChild(dims);
      }

      if (state.step === 2) {
        main.appendChild(el('h3', 'pkq-h3', 'Material, print and finish'));
        main.appendChild(optionRow('Board', Object.keys(MATERIALS).map(function (k) {
          return { key: k, label: MATERIALS[k].label };
        }), state.material, function (k) {
          state.material = k;
        }, MATERIALS[state.material].note));

        main.appendChild(optionRow('Print', Object.keys(PRINTS).map(function (k) {
          return { key: k, label: PRINTS[k].label };
        }), state.print, function (k) {
          state.print = k;
        }));

        main.appendChild(optionRow('Finish', Object.keys(FINISHES).map(function (k) {
          return { key: k, label: FINISHES[k].label };
        }), state.finish, function (k) {
          state.finish = k;
        }));

        var rushLabel = el('label', 'pkq-check');
        var rush = el('input');
        rush.type = 'checkbox';
        rush.checked = state.rush;
        rush.addEventListener('change', function () {
          state.rush = rush.checked;
          render();
        });
        rushLabel.appendChild(rush);
        rushLabel.appendChild(el('span', '', 'Rush production (+' + cfg.rushPercent + '%)'));
        main.appendChild(rushLabel);
      }

      if (state.step === 3) {
        main.appendChild(el('h3', 'pkq-h3', 'Quantity'));
        main.appendChild(el('p', 'pkq-big', p.qty.toLocaleString() + ' units'));

        var range = el('input', 'pkq-range');
        range.type = 'range';
        range.min = String(cfg.minQty);
        range.max = String(cfg.maxQty);
        range.step = '500';
        range.value = String(p.qty);
        range.addEventListener('input', function () {
          state.qty = parseInt(range.value, 10);
          render();
        });
        main.appendChild(range);

        var table = el('table', 'pkq-table');
        var thead = el('thead');
        var hr = el('tr');
        ['Quantity', 'Unit', 'Total'].forEach(function (h) {
          hr.appendChild(el('th', '', h));
        });
        thead.appendChild(hr);
        table.appendChild(thead);
        var tbody = el('tbody');
        TIERS.forEach(function (q) {
          var row = price(Object.assign({}, state, { qty: q }), cfg);
          var tr = el('tr', q === p.qty ? 'is-active' : '');
          tr.appendChild(el('td', '', q.toLocaleString()));
          tr.appendChild(el('td', '', money(row.unit)));
          tr.appendChild(el('td', '', money(row.total)));
          tr.addEventListener('click', function () {
            state.qty = q;
            render();
          });
          tbody.appendChild(tr);
        });
        table.appendChild(tbody);
        main.appendChild(table);
      }

      if (state.step === 4) {
        main.appendChild(el('h3', 'pkq-h3', 'Where do we send it?'));
        var form = el('form', 'pkq-form');
        form.innerHTML = [
          '<label class="pkq-field"><span class="pkq-label">Full name</span><input name="name" class="pkq-input" required /></label>',
          '<label class="pkq-field"><span class="pkq-label">Work email</span><input name="email" type="email" class="pkq-input" required /></label>',
          '<label class="pkq-field"><span class="pkq-label">Company</span><input name="company" class="pkq-input" required /></label>',
          '<label class="pkq-field"><span class="pkq-label">Country</span><input name="country" class="pkq-input" /></label>',
          '<label class="pkq-field"><span class="pkq-label">Phone (optional)</span><input name="phone" class="pkq-input" /></label>',
          '<label class="pkq-field pkq-field-wide"><span class="pkq-label">Notes</span><textarea name="notes" rows="3" class="pkq-input"></textarea></label>',
          '<label class="pkq-hp"><span>Website</span><input name="website" tabindex="-1" autocomplete="off" /></label>'
        ].join('');
        main.appendChild(form);

        var submit = el('button', 'pkq-submit', cfg.i18n.submit + ' · ' + money(p.total));
        submit.type = 'button';
        var status = el('p', 'pkq-status');
        submit.addEventListener('click', function () {
          var data = new FormData(form);
          var payload = {
            style: state.style,
            product: root.getAttribute('data-product') || '',
            material: state.material,
            print: state.print,
            finish: state.finish,
            quantity: p.qty,
            length: state.length,
            width: state.width,
            height: state.height,
            rush: state.rush,
            name: data.get('name') || '',
            email: data.get('email') || '',
            company: data.get('company') || '',
            country: data.get('country') || '',
            phone: data.get('phone') || '',
            notes: data.get('notes') || '',
            website: data.get('website') || ''
          };
          submit.disabled = true;
          submit.textContent = cfg.i18n.sending;
          status.textContent = '';
          fetch(cfg.endpoint, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-PKQ-Nonce': cfg.nonce,
              // Logged-in visitors hit rest_cookie_check_errors(); it requires the
              // core wp_rest nonce, which our own header does not satisfy.
              'X-WP-Nonce': cfg.wpNonce || ''
            },
            body: JSON.stringify(payload)
          })
            .then(function (r) {
              return r.json().then(function (j) {
                if (!r.ok) throw new Error(j.message || cfg.i18n.error);
                return j;
              });
            })
            .then(function (j) {
              main.innerHTML = '';
              main.appendChild(el('h3', 'pkq-h3', cfg.i18n.done));
              main.appendChild(el('p', 'pkq-big', j.ref));
              main.appendChild(el('p', 'pkq-note', 'We reply within one business day with a proof timeline. Reference ' + j.ref + '.'));
            })
            .catch(function (err) {
              status.textContent = err.message || cfg.i18n.error;
              submit.disabled = false;
              submit.textContent = cfg.i18n.submit;
            });
        });
        main.appendChild(submit);
        main.appendChild(status);
      }

      var nav = el('div', 'pkq-nav');
      var back = el('button', 'pkq-btn-ghost', '← Back');
      back.type = 'button';
      back.disabled = state.step === 0;
      back.addEventListener('click', function () {
        state.step = Math.max(0, state.step - 1);
        render();
      });
      nav.appendChild(back);
      if (state.step < 4) {
        var next = el('button', 'pkq-btn', 'Continue');
        next.type = 'button';
        next.disabled = !state.style;
        next.addEventListener('click', function () {
          state.step = Math.min(4, state.step + 1);
          render();
        });
        nav.appendChild(next);
      }
      main.appendChild(nav);

      aside.hidden = !state.style;
      if (state.style) updateAside();
    }

    function updateAside() {
      var p = price(state, cfg);
      aside.innerHTML = '';
      var card = el('div', 'pkq-card');
      card.appendChild(el('p', 'pkq-label', 'Live estimate'));
      card.appendChild(el('p', 'pkq-price', money(p.unit)));
      card.appendChild(el('p', 'pkq-note', 'per unit · ' + p.qty.toLocaleString() + ' units'));
      var list = el('dl', 'pkq-specs');
      [
        ['Style', (cfg.styles[state.style] || {}).label || state.style],
        ['Size', state.length + ' × ' + state.width + ' × ' + state.height + ' cm'],
        ['Material', MATERIALS[state.material].label],
        ['Print', PRINTS[state.print].label],
        ['Finish', FINISHES[state.finish].label],
        ['Order total', money(p.total)]
      ].forEach(function (row) {
        var d = el('div');
        d.appendChild(el('dt', '', row[0]));
        d.appendChild(el('dd', '', row[1]));
        list.appendChild(d);
      });
      card.appendChild(list);
      card.appendChild(el('p', 'pkq-fine', 'Ex-works. Freight quoted separately. Setup amortised into the unit price.'));
      aside.appendChild(card);
    }

    render();
  }

  function boot() {
    Array.prototype.forEach.call(document.querySelectorAll('.pkq-root:not([data-ready])'), function (root) {
      root.setAttribute('data-ready', '1');
      mount(root);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
