# Geometri ve çakışma

45 GLB `window.__studio.measure` ile yüklendi. Ölçü, düğüm dönüşümünden sonra dünya sınır kutusudur. Ham kayıt: `test-results/meemare-master-quality/geometry-audit-before-stile-fix.json`.

## Y26

20 modelin kök kutusu katalogdaki genişlik, yükseklik ve derinlikle 2 cm toleransın içindeydi. Gövde ve raf parçalarının ince kenarı 18 mm, arkalık 8 mm. Raf ve çekmece kutusu sayıları katalog alanlarıyla eşleşti.

`y26_cift_01` çekmece kutusu 0,834 m genişliğindedir. Modül 0,90 m olduğu için bu kutu gövdenin dışına taşmaz. Önceki 0,834 m ölçüsü 45 cm’lik tek modüle ait değildir.

## Dikme çakışması

Düzeltmeden önce çekmece kutusu ile `IC_DIKME` aynı hacmi kesiyordu:

| Model | Düzeltmeden sonra dikme Y (m) | Çakışma |
| --- | --- | --- |
| y26_cift_01 | 0,468–2,118 (değişmedi) | yoktu |
| y26_cift_02 | 0,409–2,118 | kalktı |
| y26_cift_03 | 0,409–2,118 | kalktı |
| y26_cift_04 | 0,254–2,118 | kalktı |
| y26_cift_06 | 0,409–2,118 | kalktı |
| y26_cift_09 | 0,719–2,118 | kalktı |

Çekmece önleri tam genişlik olduğu için kutu daraltılmadı. Dikmenin çekmece bölgesindeki alt parçası kısaltıldı. Yeniden ölçümde altı modelde de kesişim yok.

## Diğer 25 GLB

`tek_v1` kökü 0,45 × 2,50 × 0,60 m. Yan paneller 18 mm. İki çekmece önü, tabanı ve arkası var. Katalogdaki `drawers: 6` bu geometriyle uyuşmuyor.  
`kose_1` kökü 0,90 × 2,50 × 0,90 m. Paneller 18 mm. Sol ve sağ L raflarının X aralıkları çakışmıyor.  
Bu 25 dosyanın parça adları Y26 adlarından farklı. Hepsi yüklendi; her birinin PNG ile karşılaştırması yapılmadı.

Çekmece kutuları Y26’da hâlâ tek prizma. Yan, arka ve taban ayrı parçalar değil.
