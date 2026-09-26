/**
 * theme_admin.js
 * Theme Settings admin screens: colour picker <-> text sync, font dropdown,
 * live previews, unsaved-change tracking for quick edit, key-from-label,
 * and swapping the value control when the property type changes.
 * External file because the site's CSP is script-src 'self'.
 */
(function () {
  'use strict';

  var PROP = { color: 'color', font: 'font-family', size: 'font-size', weight: 'font-weight', style: 'font-style', transform: 'text-transform' };
  var allowLeave = false;

  // ---------- Confirm destructive buttons / links ----------
  // (forms with data-confirm are handled by confirm_submit.js)
  document.addEventListener('click', function (e) {
    var el = e.target.closest('button[data-confirm], a[data-confirm]');
    if (!el) return;
    if (!window.confirm(el.getAttribute('data-confirm'))) {
      e.preventDefault();
      e.stopImmediatePropagation();
    } else {
      allowLeave = true;
    }
  }, true);

  // ---------- Live preview ----------
  function updatePreview(input) {
    if (!input.id) return;
    var v = input.value.trim();
    document.querySelectorAll('[data-preview="' + input.id + '"]').forEach(function (pv) {
      var kind = pv.getAttribute('data-kind');
      if (kind === 'color' || kind === 'bar') {
        var ok = kind === 'color' ? CSS.supports('color', v) : CSS.supports('width', v);
        pv.style.setProperty('--pv', ok ? v : 'transparent');
        pv.classList.toggle('ts-pv--invalid', !ok);
      } else if (PROP[kind]) {
        var good = CSS.supports(PROP[kind], v);
        pv.style.setProperty(PROP[kind], good ? v : '');
        pv.classList.toggle('ts-pv--invalid', !good);
      }
    });
    input.setAttribute('aria-invalid', isValid(input) ? 'false' : 'true');
  }

  function isValid(input) {
    var v = input.value.trim();
    switch (input.getAttribute('data-type')) {
      case 'color': return CSS.supports('color', v) && !/^(var|calc)/i.test(v);
      case 'font_size': return /^\d{1,4}(\.\d{1,3})?(px|rem|em|%|vw)$/.test(v);
      case 'size': return /^-?\d{1,4}(\.\d{1,3})?(px|rem|em|%|vw|vh)$|^0$/.test(v);
      case 'number': return /^\d{1,4}(\.\d{1,3})?$/.test(v);
      case 'font_family': return /^[A-Za-z0-9 \-',"]{2,120}$/.test(v);
      default: return true;
    }
  }

  function toHex6(v) {
    v = v.trim();
    if (/^#[0-9a-f]{6}$/i.test(v)) return v;
    var m = v.match(/^#([0-9a-f])([0-9a-f])([0-9a-f])$/i);
    return m ? '#' + m[1] + m[1] + m[2] + m[2] + m[3] + m[3] : null;
  }

  document.addEventListener('input', function (e) {
    var t = e.target;

    // Colour picker -> text field
    if (t.matches('input[type=color][data-sync]')) {
      var txt = document.getElementById(t.getAttribute('data-sync'));
      if (txt) { txt.value = t.value.toUpperCase(); fire(txt); }
      return;
    }
    // Text field -> colour picker
    if (t.matches('[data-type=color]')) {
      var picker = t.parentElement.querySelector('input[type=color]');
      var hex = toHex6(t.value);
      if (picker && hex) picker.value = hex;
    }
    if (t.matches('[data-type]')) {
      updatePreview(t);
      trackDirty();
    }
    if (t.matches('[data-label-source]')) autoKey(t.value);
    if (t.matches('[name=setting_key]')) {
      t.removeAttribute('data-key-target'); // user took over the key
      echoKey(t.value);
    }
  });

  document.addEventListener('change', function (e) {
    var t = e.target;
    // Font dropdown -> hidden text field (or reveal it for a custom stack)
    if (t.matches('[data-font-select]')) {
      var txt = document.getElementById(t.getAttribute('data-font-select'));
      if (!txt) return;
      if (t.value === '__custom') {
        txt.hidden = false;
        txt.focus();
      } else {
        txt.hidden = true;
        txt.value = t.value;
        fire(txt);
      }
      return;
    }
    if (t.matches('select[data-type]')) { updatePreview(t); trackDirty(); }
    if (t.matches('[data-type-select]')) switchType(t.value);
  });

  function fire(el) { el.dispatchEvent(new Event('input', { bubbles: true })); }

  // ---------- Quick-edit: unsaved change tracking ----------
  var quick = document.getElementById('quick-edit');
  var savebar = document.querySelector('[data-savebar]');

  function dirtyFields() {
    if (!quick) return [];
    return Array.prototype.filter.call(quick.querySelectorAll('[data-type]'), function (el) {
      if (el.tagName === 'SELECT') {
        var def = Array.prototype.find.call(el.options, function (o) { return o.defaultSelected; });
        return def && el.value !== def.value;
      }
      return el.value !== el.defaultValue;
    });
  }

  function trackDirty() {
    if (!quick || !savebar) return;
    var dirty = dirtyFields();
    quick.querySelectorAll('tr.is-dirty').forEach(function (r) { r.classList.remove('is-dirty'); });
    dirty.forEach(function (el) { var tr = el.closest('tr'); if (tr) tr.classList.add('is-dirty'); });
    savebar.hidden = dirty.length === 0;
    savebar.querySelector('[data-dirty-count]').textContent = dirty.length;
  }

  if (quick) {
    quick.addEventListener('submit', function (e) {
      var bad = Array.prototype.filter.call(quick.querySelectorAll('[data-type]'), function (el) { return !isValid(el); });
      if (bad.length) {
        e.preventDefault();
        bad[0].focus();
        alert('Fix the highlighted value before saving. ' + (bad[0].getAttribute('data-type') === 'color'
          ? 'Colours use #RRGGBB, rgb(), rgba() or transparent.' : 'Sizes need a unit such as px or rem.'));
        return;
      }
      allowLeave = true;
    });
    window.addEventListener('beforeunload', function (e) {
      if (!allowLeave && dirtyFields().length) { e.preventDefault(); e.returnValue = ''; }
    });
  }

  // ---------- Setting form: key from label ----------
  function slug(s) {
    return s.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').replace(/^[^a-z]+/, '').slice(0, 78);
  }
  function autoKey(label) {
    var key = document.querySelector('[data-key-target]');
    if (key) { key.value = slug(label); echoKey(key.value); }
  }
  function echoKey(v) {
    var echo = document.querySelector('[data-key-echo]');
    if (echo) echo.textContent = v || 'your-key';
  }

  // ---------- Setting form: swap controls when the property type changes ----------
  function switchType(type) {
    var tpl = document.querySelector('template[data-type-template="' + type + '"]');
    if (!tpl) return;
    [['setting_value', 'f-value'], ['default_value', 'f-default']].forEach(function (pair) {
      var holder = document.querySelector('[data-control="' + pair[0] + '"]');
      if (!holder) return;
      holder.innerHTML = tpl.innerHTML.replace(/__NAME__/g, pair[0]).replace(/__ID__/g, pair[1]);
    });
    var hint = document.querySelector('[data-type-hint]');
    if (hint) hint.textContent = tpl.getAttribute('data-hint');
    var big = document.querySelector('[data-big-preview]');
    var ptpl = document.querySelector('template[data-preview-template="' + type + '"]');
    if (big && ptpl) big.innerHTML = ptpl.innerHTML;
  }

  // Initial state
  document.querySelectorAll('[data-type]').forEach(function (el) {
    if (!isValid(el)) el.setAttribute('aria-invalid', 'true');
  });
})();
