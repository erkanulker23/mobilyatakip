# Meemare model kalite tablosu

Ölçüm: `placeCabinet` varsayılan katalog ölçüsünde. Tolerans 0.05 cm (0.5 mm). Dış kutu, parça kalınlığı ve 30 mm lef bu toleransla ölçüldü. Dekoratif pah ayrı bir mesh değil; panel ölçüsü kalınlığın ince eksenidir.

Stüdyo bu 45 modelin GLB dosyasını yüklemez. `layoutFor` doluysa parametrik gövde kurulur ve GLB isteği yapılmaz. Diskte 45/45 `model.glb` dosyası var. `meemareGlb` artık `parametric` yazıyor.

İç imza: raf, çekmece önü, askı ve dikmenin yükseklik dizisi. 45 imzanın 45'i birbirinden farklı.

| Model | Grup | Katalog cm | Dış kutu cm | Raf | Çekmece | Askı | Gövde 18 | Raf 18 | Arkalık | Lef 30 | İç çakışma | GLB disk | GLB sahne |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| y26_tek_01 | Tek kapılı | 45×60×250 | 45×60×250 | 3 | 2 | 1 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| y26_tek_02 | Tek kapılı | 45×60×250 | 45×60×250 | 4 | 1 | 1 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| y26_tek_03 | Tek kapılı | 45×60×250 | 45×60×250 | 6 | 0 | 0 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| y26_tek_04 | Tek kapılı | 45×60×250 | 45×60×250 | 3 | 2 | 2 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| y26_tek_05 | Tek kapılı | 45×60×250 | 45×60×250 | 2 | 0 | 2 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| y26_tek_06 | Tek kapılı | 45×60×250 | 45×60×250 | 4 | 0 | 1 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| y26_tek_07 | Tek kapılı | 45×60×250 | 45×60×250 | 5 | 0 | 0 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| y26_tek_08 | Tek kapılı | 45×60×250 | 45×60×250 | 2 | 0 | 1 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| y26_tek_09 | Tek kapılı | 45×60×250 | 45×60×250 | 5 | 3 | 0 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| y26_tek_10 | Tek kapılı | 45×60×250 | 45×60×250 | 6 | 0 | 0 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| y26_cift_01 | Çift kapılı | 90×60×250 | 90×60×250 | 2 | 2 | 1 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| y26_cift_02 | Çift kapılı | 90×60×250 | 90×60×250 | 4 | 2 | 4 | PASS | PASS | 5 mm | PASS | FAIL | var | yüklenmez |
| y26_cift_03 | Çift kapılı | 90×60×250 | 90×60×250 | 6 | 2 | 4 | PASS | PASS | 5 mm | PASS | FAIL | var | yüklenmez |
| y26_cift_04 | Çift kapılı | 90×60×250 | 90×60×250 | 5 | 1 | 4 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| y26_cift_05 | Çift kapılı | 90×60×250 | 90×60×250 | 7 | 0 | 2 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| y26_cift_06 | Çift kapılı | 90×60×250 | 90×60×250 | 7 | 2 | 2 | PASS | PASS | 5 mm | PASS | FAIL | var | yüklenmez |
| y26_cift_07 | Çift kapılı | 90×60×250 | 90×60×250 | 11 | 0 | 0 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| y26_cift_08 | Çift kapılı | 90×60×250 | 90×60×250 | 3 | 0 | 4 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| y26_cift_09 | Çift kapılı | 90×60×250 | 90×60×250 | 6 | 4 | 2 | PASS | PASS | 5 mm | PASS | FAIL | var | yüklenmez |
| y26_cift_10 | Çift kapılı | 90×60×250 | 90×60×250 | 9 | 0 | 2 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| tek_v1 | Tek kapılı | 45×60×250 | 45×60×250 | 3 | 2 | 1 | PASS | PASS | 5 mm | PASS | FAIL | var | yüklenmez |
| tek_v2 | Tek kapılı | 45×60×250 | 45×60×250 | 6 | 0 | 1 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| tek_v3 | Tek kapılı | 45×60×250 | 45×60×250 | 3 | 2 | 1 | PASS | PASS | 5 mm | PASS | FAIL | var | yüklenmez |
| tek_v4 | Tek kapılı | 45×60×250 | 45×60×250 | 5 | 3 | 1 | PASS | PASS | 5 mm | PASS | FAIL | var | yüklenmez |
| tek_v5 | Tek kapılı | 45×60×250 | 45×60×250 | 4 | 2 | 1 | PASS | PASS | 5 mm | PASS | FAIL | var | yüklenmez |
| tek_6 | Tek kapılı | 45×60×250 | 45×60×250 | 7 | 0 | 0 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| tek_7 | Tek kapılı | 45×60×250 | 45×60×250 | 2 | 0 | 2 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| tek_8 | Tek kapılı | 45×60×250 | 45×60×250 | 8 | 0 | 0 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| tek_9 | Tek kapılı | 45×60×250 | 45×60×250 | 3 | 4 | 1 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| tek_10 | Tek kapılı | 45×60×250 | 45×60×250 | 2 | 0 | 1 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| cift_v1 | Çift kapılı | 90×60×250 | 90×60×250 | 5 | 3 | 1 | PASS | PASS | 5 mm | PASS | FAIL | var | yüklenmez |
| cift_v2 | Çift kapılı | 90×60×250 | 90×60×250 | 6 | 2 | 1 | PASS | PASS | 5 mm | PASS | FAIL | var | yüklenmez |
| cift_v3 | Çift kapılı | 90×60×250 | 90×60×250 | 8 | 4 | 2 | PASS | PASS | 5 mm | PASS | FAIL | var | yüklenmez |
| cift_v4 | Çift kapılı | 90×60×250 | 90×60×250 | 7 | 2 | 1 | PASS | PASS | 5 mm | PASS | FAIL | var | yüklenmez |
| cift_v5 | Çift kapılı | 90×60×250 | 90×60×250 | 6 | 2 | 3 | PASS | PASS | 5 mm | PASS | FAIL | var | yüklenmez |
| cift_6 | Çift kapılı | 90×60×250 | 90×60×250 | 2 | 2 | 2 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| cift_7 | Çift kapılı | 90×60×250 | 90×60×250 | 8 | 4 | 1 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| cift_8 | Çift kapılı | 90×60×250 | 90×60×250 | 3 | 3 | 3 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| cift_9 | Çift kapılı | 90×60×250 | 90×60×250 | 11 | 2 | 0 | PASS | PASS | 5 mm | PASS | FAIL | var | yüklenmez |
| cift_10 | Çift kapılı | 90×60×250 | 90×60×250 | 3 | 2 | 3 | PASS | PASS | 5 mm | PASS | yok | var | yüklenmez |
| kose_1 | Köşe | 90×90×250 | 90×90×250 | 10 | 0 | 0 | PASS | PASS | 18 mm köşe | — | yok | var | yüklenmez |
| kose_2 | Köşe | 90×90×250 | 90×90×250 | 6 | 0 | 1 | PASS | PASS | 18 mm köşe | — | yok | var | yüklenmez |
| kose_3 | Köşe | 90×90×250 | 90×90×250 | 8 | 2 | 1 | PASS | PASS | 18 mm köşe | — | FAIL | var | yüklenmez |
| kose_4 | Köşe | 90×90×250 | 90×90×250 | 12 | 0 | 0 | PASS | PASS | 18 mm köşe | — | yok | var | yüklenmez |
| kose_5 | Köşe | 90×90×250 | 90×90×250 | 8 | 0 | 1 | PASS | PASS | 18 mm köşe | — | yok | var | yüklenmez |

## Bu tabloda PASS olmayan iç çakışmalar

`interiorCollides` raf, çekmece önü ve çekmece kutusunun aynı hacmi paylaşmasını sayar. Aynı çekmecenin alt ve arka tahtası da kutu rolünde olduğu için v serisinde bu sayaca girer.

| Model | Kayıtlı kesişmeler |
|---|---|
| y26_cift_02 | drawerFront/shelf:1.80, drawerBox/shelf:1.80 |
| y26_cift_03 | drawerFront/shelf:1.80, drawerBox/shelf:1.80 |
| y26_cift_05 | divider/shelf:0.80 |
| y26_cift_06 | drawerFront/shelf:1.80, drawerBox/shelf:1.80 |
| y26_cift_09 | drawerFront/shelf:1.80, drawerBox/shelf:0.50 |
| y26_cift_10 | divider/shelf:0.80 |
| tek_v1 | drawerBox/drawerBox:1.10, drawerBox/drawerBox:1.10 |
| tek_v3 | drawerBox/drawerBox:1.10, drawerBox/drawerBox:1.10 |
| tek_v4 | drawerBox/drawerBox:1.10, drawerBox/drawerBox:1.10, drawerBox/drawerBox:1.10 |
| tek_v5 | drawerBox/drawerBox:1.10, drawerBox/drawerBox:1.10 |
| tek_10 | bottom/panel:1.80 |
| cift_v1 | divider/shelf:0.90, drawerBox/drawerBox:1.10, drawerBox/drawerBox:1.10, drawerBox/drawerBox:1.10 |
| cift_v2 | drawerBox/drawerBox:1.10, drawerBox/drawerBox:1.10 |
| cift_v3 | drawerBox/drawerBox:1.10, drawerBox/drawerBox:1.10, drawerBox/drawerBox:1.10, drawerBox/drawerBox:1.10 |
| cift_v4 | divider/shelf:0.90, drawerBox/drawerBox:1.10, drawerBox/drawerBox:1.10 |
| cift_v5 | drawerBox/drawerBox:1.10, drawerBox/drawerBox:1.10 |
| cift_6 | plinth/divider:1.80 |
| cift_7 | plinth/divider:1.80 |
| cift_8 | plinth/divider:1.80 |
| cift_9 | plinth/divider:1.80, drawerFront/shelf:1.40 |
| cift_10 | plinth/divider:1.80 |
| kose_1 | side/top:1.80, side/bottom:1.80, back/side:1.80, back/top:1.80, back/top:1.80, back/bottom:1.80, back/bottom:1.80, back/shelf:0.90 |
| kose_2 | side/back:1.80, side/side:1.80, side/top:1.80, side/bottom:1.80, back/side:1.80, back/top:1.80, back/top:1.80, back/bottom:1.80 |
| kose_3 | side/back:1.80, side/side:1.80, side/top:1.80, side/bottom:1.80, back/side:1.80, back/top:1.80, back/top:1.80, back/bottom:1.80 |
| kose_4 | side/back:1.80, side/side:1.80, side/top:1.80, side/bottom:1.80, back/side:1.80, back/top:1.80, back/top:1.80, back/bottom:1.80 |
| kose_5 | side/back:1.80, side/side:1.80, side/top:1.80, side/bottom:1.80, back/side:1.80, back/top:1.80, back/top:1.80, back/bottom:1.80 |
