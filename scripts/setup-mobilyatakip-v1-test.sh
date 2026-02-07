#!/bin/bash
# https://mobilyatakip-v1.test/ için Valet kurulumu (Laravel + PHP)
# Terminalde çalıştırın: ./scripts/setup-mobilyatakip-v1-test.sh

set -e
cd "$(dirname "$0")/.."

echo "1. Valet CA güvenilir yapılıyor (tarayıcı sertifika uyarısı kalkar)..."
valet trust

echo ""
echo "2. Eski proxy/link kaldırılıyor..."
valet unlink mobilyatakip-v1.test 2>/dev/null || valet unlink mobilyatakip-v1 2>/dev/null || true

echo ""
echo "3. Laravel (PHP) olarak link ve HTTPS..."
valet link mobilyatakip-v1
valet secure mobilyatakip-v1

echo ""
echo "4. https://mobilyatakip-v1.test/ test ediliyor..."
CODE=$(curl -sk --connect-timeout 5 -o /dev/null -w "%{http_code}" https://mobilyatakip-v1.test/ 2>/dev/null || echo "000")
if [ "$CODE" = "200" ] || [ "$CODE" = "302" ]; then
  echo "   OK (HTTP $CODE) - Site çalışıyor."
else
  echo "   Uyarı: HTTP $CODE. Tarayıcıda https://mobilyatakip-v1.test/ adresini deneyin."
fi

echo ""
echo "Kurulum tamam. Tarayıcıda açın: https://mobilyatakip-v1.test/"
echo "Giriş: mevcut users tablosundaki e-posta ve şifre ile."
