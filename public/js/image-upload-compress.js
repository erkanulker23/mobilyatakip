/**
 * Profil / personel fotoğraflarını yüklemeden önce küçültür (nginx 413 önlemi).
 */
(function () {
  'use strict';

  function compressImageFile(file, options) {
    options = options || {};
    var maxWidth = options.maxWidth || 1200;
    var maxHeight = options.maxHeight || 1200;
    var maxBytes = options.maxBytes || 900 * 1024;
    var mime = options.mime || 'image/jpeg';
    var quality = options.quality || 0.85;

    return new Promise(function (resolve, reject) {
      if (!file || !String(file.type || '').startsWith('image/')) {
        resolve(file);
        return;
      }

      if (file.size <= maxBytes && /jpe?g$/i.test(file.name)) {
        resolve(file);
        return;
      }

      var reader = new FileReader();
      reader.onload = function () {
        var img = new Image();
        img.onload = function () {
          var scale = Math.min(1, maxWidth / img.width, maxHeight / img.height);
          var width = Math.max(1, Math.round(img.width * scale));
          var height = Math.max(1, Math.round(img.height * scale));
          var canvas = document.createElement('canvas');
          canvas.width = width;
          canvas.height = height;
          var ctx = canvas.getContext('2d');
          if (!ctx) {
            resolve(file);
            return;
          }
          ctx.drawImage(img, 0, 0, width, height);

          function exportAtQuality(q) {
            canvas.toBlob(function (blob) {
              if (!blob) {
                reject(new Error('compress_failed'));
                return;
              }
              if (blob.size <= maxBytes || q <= 0.45) {
                var base = String(file.name || 'photo').replace(/\.[^.]+$/, '') || 'photo';
                resolve(new File([blob], base + '.jpg', { type: mime, lastModified: Date.now() }));
                return;
              }
              exportAtQuality(Math.max(0.45, q - 0.08));
            }, mime, q);
          }

          exportAtQuality(quality);
        };
        img.onerror = function () { reject(new Error('image_load_failed')); };
        img.src = reader.result;
      };
      reader.onerror = function () { reject(new Error('file_read_failed')); };
      reader.readAsDataURL(file);
    });
  }

  function bindImageUploadCompress(input, options) {
    if (!input || input.dataset.compressBound === '1') {
      return;
    }
    input.dataset.compressBound = '1';
    options = options || {};
    var statusEl = options.statusEl || (input.id ? document.getElementById(input.id + 'Hint') : null);

    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file) {
        if (statusEl) statusEl.textContent = '';
        return;
      }

      if (statusEl) {
        statusEl.textContent = 'Resim optimize ediliyor...';
        statusEl.classList.remove('text-red-600');
        statusEl.classList.add('text-neutral-500');
      }

      compressImageFile(file, options).then(function (compressed) {
        var dt = new DataTransfer();
        dt.items.add(compressed);
        input.files = dt.files;
        if (statusEl) {
          statusEl.textContent = 'Hazır: ' + Math.round(compressed.size / 1024) + ' KB';
        }
      }).catch(function () {
        if (statusEl) {
          statusEl.textContent = 'Resim optimize edilemedi; daha küçük bir dosya seçin.';
          statusEl.classList.add('text-red-600');
        }
      });
    });
  }

  function initImageCompressInputs() {
    document.querySelectorAll('input[type="file"][data-compress-image]').forEach(function (input) {
      bindImageUploadCompress(input, {
        maxBytes: parseInt(input.dataset.maxBytes || '921600', 10),
        maxWidth: parseInt(input.dataset.maxWidth || '1200', 10),
        maxHeight: parseInt(input.dataset.maxHeight || '1200', 10),
      });
    });
  }

  window.compressImageFile = compressImageFile;
  window.bindImageUploadCompress = bindImageUploadCompress;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initImageCompressInputs);
  } else {
    initImageCompressInputs();
  }
})();
