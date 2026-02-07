/**
 * Form alanı kısıtlamaları:
 * - Telefon: Sadece rakam, +, boşluk, tire, parantez (Örn: 0555 123 45 67)
 * - E-posta: type="email" tarayıcı tarafından doğrulanır
 * - TC Kimlik: Maksimum 11 rakam
 */
(function () {
  'use strict';

  function init() {
    // Telefon alanları - data-input="phone" veya name="phone", name="assignedDriverPhone"
    document.querySelectorAll('input[data-input="phone"], input[name="phone"], input[name="assignedDriverPhone"]').forEach(function (el) {
      if (el.dataset.formInputsInit) return;
      el.dataset.formInputsInit = '1';
      el.setAttribute('type', 'tel');
      el.setAttribute('inputmode', 'tel');
      el.setAttribute('autocomplete', 'tel');
      if (!el.placeholder) el.setAttribute('placeholder', '05XX XXX XX XX');
      el.addEventListener('input', function (e) {
        var v = e.target.value;
        var filtered = v.replace(/[^0-9+\s\-()]/g, '');
        if (v !== filtered) e.target.value = filtered;
      });
    });

    // E-posta alanları - type="email" zaten var; data-input="email" veya name içinde email geçenler
    document.querySelectorAll('input[data-input="email"], input[name="email"], input[name="mailFrom"]').forEach(function (el) {
      if (el.type !== 'email') {
        el.setAttribute('type', 'email');
        el.setAttribute('inputmode', 'email');
        el.setAttribute('autocomplete', 'email');
      }
    });

    // TC Kimlik alanları
    document.querySelectorAll('input[data-input="tc"], input[name="identityNumber"]').forEach(function (el) {
      if (el.dataset.formInputsInit) return;
      el.dataset.formInputsInit = '1';
      el.setAttribute('inputmode', 'numeric');
      el.setAttribute('maxlength', '11');
      el.setAttribute('pattern', '[0-9]{0,11}');
      el.setAttribute('title', '11 haneli TC kimlik numarası giriniz (sadece rakam)');
      el.addEventListener('input', function (e) {
        var v = e.target.value.replace(/\D/g, '').slice(0, 11);
        if (e.target.value !== v) e.target.value = v;
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
  // Alpine.js veya dinamik içerik için
  document.addEventListener('alpine:initialized', init);
})();
