/**
 * Form alanı kısıtlamaları ve anlık formatlama:
 * - Telefon: 05322222222 → 0 532 222 22 22 (+90 destekli)
 * - E-posta: yalnızca geçerli e-posta karakterleri
 * - TC Kimlik: maksimum 11 rakam
 */
(function () {
  'use strict';

  var PHONE_SELECTOR = [
    'input[type="tel"]',
    'input[data-input="phone"]',
    'input[name="phone"]',
    'input[name="phone2"]',
    'input[name="assignedDriverPhone"]',
    'input[name="driverPhone"]',
  ].join(', ');

  var EMAIL_SELECTOR = [
    'input[type="email"]',
    'input[data-input="email"]',
    'input[name="email"]',
    'input[name="mailFrom"]',
  ].join(', ');

  function digitsBeforeCaret(value, caret) {
    return String(value || '').slice(0, caret).replace(/\D/g, '').length;
  }

  function caretAfterDigits(formatted, digitTarget) {
    if (digitTarget <= 0) {
      if (!formatted) return 0;
      if (formatted.charAt(0) === '+') {
        var space = formatted.indexOf(' ');
        return space > -1 ? space : 1;
      }
      return 1;
    }
    var count = 0;
    for (var i = 0; i < formatted.length; i++) {
      if (/\d/.test(formatted.charAt(i))) {
        count++;
        if (count >= digitTarget) return i + 1;
      }
    }
    return formatted.length;
  }

  function formatTurkishPhone(value, finalize) {
    if (!value) return '';

    var trimmed = String(value).trim();
    if (trimmed.charAt(0) === '+') {
      var intDigits = trimmed.slice(1).replace(/\D/g, '').slice(0, 12);
      if (intDigits.indexOf('90') === 0) {
        var national = intDigits.slice(2, 12);
        var intOut = '+90';
        if (national.length > 0) intOut += ' ' + national.slice(0, 3);
        if (national.length > 3) intOut += ' ' + national.slice(3, 6);
        if (national.length > 6) intOut += ' ' + national.slice(6, 8);
        if (national.length > 8) intOut += ' ' + national.slice(8, 10);
        return intOut;
      }
      return '+' + intDigits;
    }

    var digits = String(value).replace(/\D/g, '');
    if (!digits.length) return '';

    if (digits.charAt(0) !== '0') {
      digits = digits.slice(0, 10);
      if (finalize && digits.length === 10) {
        digits = '0' + digits;
      }
    } else {
      digits = digits.slice(0, 11);
    }

    if (digits.length <= 1) return digits;

    var rest = digits.slice(1);
    var formatted = digits.charAt(0) + ' ' + rest.slice(0, 3);
    if (rest.length > 3) formatted += ' ' + rest.slice(3, 6);
    if (rest.length > 6) formatted += ' ' + rest.slice(6, 8);
    if (rest.length > 8) formatted += ' ' + rest.slice(8, 10);
    return formatted;
  }

  function setInputValue(input, value, caret) {
    if (input.value === value) return false;
    input.value = value;
    if (typeof caret === 'number') {
      try {
        input.setSelectionRange(caret, caret);
      } catch (e) {}
    }
    input.dispatchEvent(new Event('input', { bubbles: true }));
    return true;
  }

  function applyPhoneFormat(input, finalize) {
    var caret = input.selectionStart || 0;
    var digitPos = digitsBeforeCaret(input.value, caret);
    var formatted = formatTurkishPhone(input.value, finalize);
    if (input.value === formatted) return;
    var newCaret = caretAfterDigits(formatted, digitPos);
    setInputValue(input, formatted, newCaret);
  }

  function filterEmail(value) {
    return String(value || '')
      .replace(/\s/g, '')
      .replace(/[^\w.@+\-]/g, '');
  }

  function applyEmailFilter(input) {
    var caret = input.selectionStart || 0;
    var before = input.value.slice(0, caret);
    var filtered = filterEmail(input.value);
    if (input.value === filtered) return;
    var filteredBefore = filterEmail(before);
    var removed = before.length - filteredBefore.length;
    var newCaret = Math.max(0, caret - removed);
    setInputValue(input, filtered, newCaret);
  }

  function bindPhone(el) {
    if (el.dataset.formInputsPhone) return;
    el.dataset.formInputsPhone = '1';
    el.setAttribute('type', 'tel');
    el.setAttribute('inputmode', 'tel');
    if (!el.getAttribute('autocomplete')) el.setAttribute('autocomplete', 'tel');
    if (!el.placeholder) el.setAttribute('placeholder', '05XX XXX XX XX');

    if (el.value) {
      el.value = formatTurkishPhone(el.value, true);
    }

    el.addEventListener('input', function () {
      applyPhoneFormat(el, false);
    });
    el.addEventListener('paste', function () {
      setTimeout(function () {
        applyPhoneFormat(el, true);
      }, 0);
    });
    el.addEventListener('blur', function () {
      if (el.value) {
        applyPhoneFormat(el, true);
      }
    });
  }

  function bindEmail(el) {
    if (el.dataset.formInputsEmail) return;
    el.dataset.formInputsEmail = '1';
    if (el.type !== 'email') {
      el.setAttribute('type', 'email');
    }
    el.setAttribute('inputmode', 'email');
    if (!el.getAttribute('autocomplete')) el.setAttribute('autocomplete', 'email');
    if (!el.placeholder) el.setAttribute('placeholder', 'ornek@email.com');

    el.addEventListener('input', function () {
      applyEmailFilter(el);
    });
    el.addEventListener('paste', function () {
      setTimeout(function () {
        applyEmailFilter(el);
      }, 0);
    });
  }

  function bindTc(el) {
    if (el.dataset.formInputsTc) return;
    el.dataset.formInputsTc = '1';
    el.setAttribute('inputmode', 'numeric');
    el.setAttribute('maxlength', '11');
    el.setAttribute('pattern', '[0-9]{0,11}');
    el.setAttribute('title', '11 haneli TC kimlik numarası giriniz (sadece rakam)');
    el.addEventListener('input', function (e) {
      var v = e.target.value.replace(/\D/g, '').slice(0, 11);
      if (e.target.value !== v) e.target.value = v;
    });
  }

  function init() {
    document.querySelectorAll(PHONE_SELECTOR).forEach(bindPhone);
    document.querySelectorAll(EMAIL_SELECTOR).forEach(bindEmail);
    document.querySelectorAll('input[data-input="tc"], input[name="identityNumber"]').forEach(bindTc);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  document.addEventListener('alpine:initialized', init);

  var debounceTimer;
  var observer = new MutationObserver(function () {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(init, 50);
  });

  if (document.body) {
    observer.observe(document.body, { childList: true, subtree: true });
  }
})();
