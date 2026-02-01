#!/bin/bash
# https://mobilyatakip-v1.test/ için Valet kurulumu ve test
# Terminalde çalıştırın (sudo şifresi istenecek).

set -e
cd "$(dirname "$0")/.."

echo "1. Valet CA güvenilir yapılıyor (tarayıcı sertifika uyarısı kalkar)..."
valet trust

echo ""
echo "2. Eski link kaldırılıyor (site PHP olarak değil proxy olarak çalışsın)..."
valet unlink mobilyatakip-v1.test 2>/dev/null || valet unlink mobilyatakip-v1 2>/dev/null || true
echo ""
echo "3. Proxy ayarlanıyor (HTTP + HTTPS): mobilyatakip-v1.test -> http://127.0.0.1:3001"
valet proxy mobilyatakip-v1.test http://127.0.0.1:3001 --secure
echo "   Nginx yeniden başladı, 3 saniye bekleniyor..."
sleep 3

echo ""
echo "4. Backend çalışıyor mu? (port 3001)"
if curl -s -o /dev/null -w "" http://127.0.0.1:3001/ 2>/dev/null; then
  echo "   Evet, backend yanıt veriyor."
else
  echo "   HAYIR. Önce başka bir terminalde: npm run start:dev"
  exit 1
fi

echo ""
echo "5. https://mobilyatakip-v1.test/ test ediliyor (birkaç deneme)..."
CODE="000"
for i in 1 2 3 4 5; do
  CODE=$(curl -sk --connect-timeout 5 -o /dev/null -w "%{http_code}" https://mobilyatakip-v1.test/ 2>/dev/null || echo "000")
  if [ "$CODE" = "200" ]; then
    echo "   OK (HTTP $CODE) - Panel HTML dönüyor."
    break
  fi
  [ "$i" -lt 5 ] && echo "   Deneme $i: HTTP $CODE, 2 sn sonra tekrar..." && sleep 2
done
if [ "$CODE" != "200" ]; then
  echo "   Uyarı: Beklenen 200, alınan HTTP $CODE (000 = nginx henüz hazır değil veya bağlantı sorunu)."
  echo "   Birkaç saniye bekleyip tarayıcıda https://mobilyatakip-v1.test/ adresini deneyin."
fi

echo ""
echo "6. Proxy durumu (proxy olmalı, Path/PHP olmamalı):"
valet links 2>/dev/null | grep -E "mobilyatakip|Site " || true

echo ""
echo "Kurulum tamam. Tarayıcıda açın: https://mobilyatakip-v1.test/"
echo "Giriş: erkanulker0@gmail.com / password"
echo ""
echo "Not: Sayfa PHP/Laravel ise proxy etkin değildir; şunu deneyin:"
echo "  valet unlink mobilyatakip-v1.test 2>/dev/null; valet proxy mobilyatakip-v1.test http://127.0.0.1:3001"
