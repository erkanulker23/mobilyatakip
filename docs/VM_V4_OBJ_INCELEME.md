# Viessmann `vm_v4` OBJ incelemesi

Kaynak: `~/Desktop/Dolap Modülleri/dolap modülleri obj.obj` (~1,09 GB, 3ds Max OBJ).

## Özet

| Bulgu | Değer |
| --- | --- |
| `o` grupları (toplam) | ~581 |
| `vm_v4_*` gövde adayı | 33 örnek, **20 benzersiz geometri** |
| Y26 modül sayısı | 20 (10 tek + 10 çift) |
| Viessmann eşleşmesi | **20 benzersiz mesh = Y26 ile bire bir** (tesadüf değil; showroom sırası) |

Gövdeler **gerçek**: yüz binlerce üçgen, raf/çekmece/yan panel detayı var. Parametrik kutu GLB’lerinden farklı üretici kütüphanesi.

Sahne ayrıca ~245 Max Box/Arc primitifi, ~50 `Obj3d66_*` dekor, askı/tekstil objeleri içerir — stüdyoya **aktarılmıyor**, sadece `vm_v4_*` alınıyor.

## Ölçü

Ham bbox (mm, Max uzayı, Y = yükseklik):

| Obje | X | Y | Z |
| --- | ---: | ---: | ---: |
| `vm_v4_011_057` (tek) | 159 | 743 | 272 |
| `vm_v4_011_058` | 121 | 705 | 390 |
| `vm_v4_011_061` | 219 | 813 | 419 |
| `vm_v4_014_024` (çift) | 187 | 815 | 428 |

**Y26 katalog kutusu:** 450×2500×600 mm (tek), 900×2500×600 mm (çift).

Ham Y ~520–815 mm aralığında değişir; tam 2500 mm değil. Stüdyo duvar hizası için içe aktarımda **hedef kutuya fit** (eksene göre ölçek) uygulanır: `scripts/blender-vm-v4-to-y26.py`.

Bu, üretici modelinin oranını Y26 montaj hattına oturtur; üretim DXF’leri hâlâ `module.json` + mevcut 2D dosyalarla doğrulanmalı.

## Y26 eşleme

`public/data/vm-v4-y26-mapping.json` — her `y26_tek_XX` / `y26_cift_XX` için bir `vm_v4_*` temsilci.

## İçe aktarım

```bash
python3 scripts/import-vm-v4-y26-batch.py          # 20 modül
python3 scripts/import-vm-v4-y26-batch.py y26_tek_02  # tek modül
```

Çıktı: `public/catalog/y26/<id>/model.glb`, `preview.png`, `vm-v4-import.json`  
Eski parametrik GLB: `model.parametric.glb.bak`

## Önizleme (`preview.png?v=3` tarzı)

Mevcut katalog görselleri (`y26_tek_02/preview.png?v=3`) **konsept render**: açık kapak, kıyafet, vazo, stüdyo ışığı (`catalog_png_is_concept: true`).

VM_v4 aktarımı şu an **EEVEE teknik önizleme** üretir; geometri doğru, katalog stili için ikinci tur (Cycles, malzeme, kapak açısı, isteğe bağlı aksesuar strip) gerekir.

## Stüdyo

`/dolap/tasarla?proje=…` → `wardrobe-y26-modules.json` → `modelPath` GLB yükler. GLB değişince modül kartı ve sahne aynı ID ile çalışır.

## Durum (kademeli)

- Pilot: `y26_tek_02` ← `vm_v4_011_058` (GLB + `?v=4`)
- Toplu import: `storage/vm-v4-import.log`