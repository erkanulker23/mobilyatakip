/**
 * Türk Lirası formatı: 20.000 (binlik ayraç nokta, ondalık virgül)
 */
(function () {
  'use strict';

  var MONEY_NAMES = /^(amount|depositAmount|serviceChargeAmount|openingBalance|unitPrice|netPurchasePrice|grandTotalOverride|generalDiscountAmount|minPrice|maxPrice)$/;
  var MONEY_NESTED = /\[(unitPrice|listPrice|lineDiscountAmount)\]$/;

  function decimalsFor(el) {
    var d = el.getAttribute('data-money-decimals');
    return d !== null && d !== '' ? parseInt(d, 10) : 0;
  }

  window.fmtMoney = function (n, decimals) {
    var d = decimals === undefined ? 0 : decimals;
    return new Intl.NumberFormat('tr-TR', {
      minimumFractionDigits: d,
      maximumFractionDigits: d,
    }).format(Number(n) || 0);
  };

  window.parseMoney = function (s) {
    if (s == null || s === '') return NaN;
    var t = String(s).trim().replace(/\s/g, '');
    if (!t) return NaN;

    if (t.indexOf(',') !== -1) {
      t = t.replace(/\./g, '').replace(',', '.');
      return parseFloat(t);
    }

    var dotCount = (t.match(/\./g) || []).length;
    if (dotCount > 1) {
      return parseFloat(t.replace(/\./g, ''));
    }

    if (dotCount === 1) {
      var parts = t.split('.');
      var intPart = parts[0];
      var frac = parts[1] || '';

      if (frac === '') return parseFloat(intPart);

      if (frac.length >= 3 && /^0+$/.test(frac)) {
        return parseFloat(intPart + frac.substring(0, 3));
      }

      if (frac.length === 3 && /^\d+$/.test(frac)) {
        return parseFloat(intPart + frac);
      }

      if (frac.length <= 2 && /^\d+$/.test(frac)) {
        return parseFloat(intPart + '.' + frac);
      }
    }

    return parseFloat(t);
  };

  /** Yazarken 50.000 gibi binlik girişi bozmayalım */
  function isPartialMoneyInput(value) {
    var t = String(value || '').trim();
    if (/^\d+\.$/.test(t)) return true;
    if (t.indexOf(',') !== -1) return false;
    var m = t.match(/^(\d+)\.(\d+)$/);
    if (!m) return false;
    var frac = m[2];
    if (frac.length < 3) return true;
    if (frac.length >= 3 && !/^0+$/.test(frac)) return false;
    return false;
  }

  if (!window.fmt) window.fmt = window.fmtMoney;
  if (!window.parseTrNum) window.parseTrNum = window.parseMoney;

  function isMoneyField(el) {
    if (!el || el.tagName !== 'INPUT' || el.type === 'hidden') return false;
    if (el.dataset.input === 'money' || el.classList.contains('money-input')) return true;
    if (el.classList.contains('item-price') || el.classList.contains('item-listprice') || el.classList.contains('item-disc-amt')) return true;
    if (el.id === 'grandTotalOverride' || el.id === 'depositAmount') return true;
    var name = el.getAttribute('name') || '';
    if (MONEY_NAMES.test(name)) return true;
    if (MONEY_NESTED.test(name)) return true;
    return false;
  }

  function formatMoneyInput(el) {
    if (!el || !isMoneyField(el)) return;
    var decimals = decimalsFor(el);
    var v = window.parseMoney(el.value);
    if (isNaN(v)) {
      if (el.value.trim() === '') el.removeAttribute('data-raw');
      return;
    }
    el.value = window.fmtMoney(v, decimals);
    el.setAttribute('data-raw', String(v));
  }

  function bindMoney(el) {
    if (!isMoneyField(el) || el.dataset.moneyInit) return;
    el.dataset.moneyInit = '1';
    if (el.type === 'number') {
      el.type = 'text';
      el.setAttribute('inputmode', 'decimal');
    }
    if (!el.getAttribute('autocomplete')) el.setAttribute('autocomplete', 'off');
    if (!el.placeholder || el.placeholder === '0.00') el.setAttribute('placeholder', '0');
    el.classList.add('tabular-nums');

    if (el.value) formatMoneyInput(el);

    el.addEventListener('input', function () {
      if (isPartialMoneyInput(el.value)) return;
      formatMoneyInput(el);
      el.dispatchEvent(new Event('change', { bubbles: true }));
    });
    el.addEventListener('blur', function () {
      formatMoneyInput(el);
    });
  }

  function initMoneyInputs(root) {
    (root || document).querySelectorAll('input').forEach(function (el) {
      if (isMoneyField(el)) bindMoney(el);
    });
  }

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || form.tagName !== 'FORM') return;
    form.querySelectorAll('input').forEach(function (el) {
      if (!isMoneyField(el) || el.dataset.moneyKeepFormatted === '1') return;
      var v = window.parseMoney(el.value);
      el.value = isNaN(v) ? '' : String(v);
    });
  }, true);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { initMoneyInputs(); });
  } else {
    initMoneyInputs();
  }

  document.addEventListener('alpine:initialized', function () { initMoneyInputs(); });

  var debounceTimer;
  var observer = new MutationObserver(function () {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function () { initMoneyInputs(); }, 50);
  });
  if (document.body) {
    observer.observe(document.body, { childList: true, subtree: true });
  }

  window.initMoneyInputs = initMoneyInputs;
  window.formatMoneyInput = formatMoneyInput;
})();
