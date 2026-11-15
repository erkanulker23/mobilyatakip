# Model kalitesi — ara sonuç

İş emri: MEEMARE-MASTER-2026-002  
Tarih: 2026-11-02  
Karar: Üç referans model onaylanmadı. Diğer 42 modüle yayılmadı. Canlıya çıkış yok.

## Bu turda kapanan hata

`y26_tek_01` Ultra Render’da sol yan panel siyah levha gibi duruyordu. Kaynak GLB’de `000_GOVDE_SOL` dış yüzü dışarı bakıyor. Hata, sahneye oturunca eksen hizalı kutuların yeniden kurulduğu `chamferBoxGeometry` içindeydi. +X ve −X yüzlerinin üçgen sırası, yazılan normalin tersiydi. Raster `DoubleSide` ve normal özniteliğiyle ahşap gösteriyor, yol izleyici ise sarımı geometrik normal olarak kullanıp dış yüzü karartıyordu.

Düzeltme `public/js/wardrobe-studio.js` içinde bu iki yüzün sarımıdır. Önbellek `?v=193`. Aynı sahne yeniden alındı: 1400×728, 12 geçiş, 48 örnek, 15,5 saniye.

| Bölge | Önce | Sonra |
| --- | --- | --- |
| Sol yan bant, piksel parlaklığı 28 altı | %78,5, ortalama RGB 35, 34, 32 | %0, ortalama RGB 147, 138, 123 |
| İç hacim, aynı eşik | %84, ortalama 24, 23, 21 | %34, ortalama 108, 90, 67 |
| Duvar parlaklık sapması | ortalama 204,5, sapma 19,5 | ortalama 204,2, sapma 20,0 |

Kanıt: `test-results/reference-quality/black-face-measure.json`  
Önce: `test-results/reference-quality/shots/ultra-tek.png`  
Sonra: `test-results/reference-quality/shots/ultra-tek-after.png`  
Raster yan görünüş: `test-results/reference-quality/shots/tek-side-after.jpg`

Sol dış yüz artık ahşap. Bu, fotoğraf gerçekçiliği onayı değildir.

## Hâlâ başarısız

- Duvar beneği aynı örnek sayısında duruyor. Sapma 19,5’ten 20,0’a geldi.
- İç hacmin %34’ü hâlâ eşik altı. Boşluk koyu; bu turda ışık eklenmedi.
- Cam kalınlığı 0’da bırakıldı. Şeffaf, füme, bronz, ayna ve alüminyum çerçeve ayrı ayrı path karşılaştırması yapılmadı.
- Ahşap hâlâ katalog JPEG albedo. Ayrı roughness, normal ve AO haritası yok. Kastamonu eşlemesi resmi değil; arayüzdeki metin temsili diyor.
- Kaynak GLB çekmece kutusu hâlâ dolu kutu. Sahnede `hollowDrawerBox` bu kutuyu kaldırıp 16 mm yan ve arka, 8 mm taban üretiyor. Açık çekmece: `test-results/reference-quality/shots/tek-drawer-hollow.jpg`. Kaynak dosya ile sahne aynı geometri değil.
- Raf kalınlığı 18 mm. Görüntüde şişirilmedi.
- Kulplar ve raylar basit metal çubuk. Tek gömlek modeli doğrulanmadı. Diğer kıyafetler üretilmedi.
- Köşe üst ve alt 18 mm bindirmeleri duruyor. Diğer beş `y26_cift` çakışması duruyor.

Ortak pah fonksiyonu her eksen hizalı gövde kutusunu kurar. Sarım düzeltmesi bu yüzden tek modele özel bir dal değil. Raster görünüm, normal özniteliği zaten doğru olduğu için diğer modüllerde değişmedi. Malzeme ve aksesuar standardı diğer 42’ye uygulanmadı.
