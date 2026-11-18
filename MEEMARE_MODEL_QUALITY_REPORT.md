# Model kalitesi

Tarih: 2026-11-02  
Kapsam: `y26_tek_01`, `y26_cift_01`, `kose_1`  
Diğer modüllerin GLB dosyalarına dokunulmadı.

## Çift kapılı kaynak çakışması

Önce `006_IC_DIKME` çekmece kutularının içinden geçiyordu. Örtüşme 18 × 120 × 465 mm idi. Askılık da dikmenin içinden geçiyordu.

Kaynak düzeltme, sahne kaydırması değil:

- Dikmenin altı 118 mm’den 468 mm’ye alındı. Çekmece bankosunun üstündeki rafın üst yüzüne oturuyor. Yüksekliği 1650 mm. Kalınlık 18 mm.
- Askılığın sağ ucu 548 mm. Dikme 550 mm’de başlıyor. Aralık 2 mm.
- Dış ölçü 900 × 2500 × 600 mm kaldı. Yan panel 18 mm kaldı.
- Aynı sayılar `module.json` içindeki `IC_DIKME` satırına yazıldı.

Ölçülen örtüşme hacmi, dikme ile iki çekmece kutusu, iki çekmece önü, alt raf ve askılık için 0 mm³.  
Betik: `scripts/fix-reference-joints.py`

`y26_cift_02`, `03`, `04`, `06` ve `09` aynı hatayı taşımaya devam ediyor. Bu turda kesilmediler.

## Köşe kolonu

`left_outer` ile `back_wall` aynı 18 × 18 × 2500 mm kolonu paylaşıyordu. `front_left_end` ile de aynı kolon vardı.

Arka panel ve ön alın paneli, sol yan panelin iç yüzünden başlayacak şekilde 18 mm içeri alındı. Dış zarf 900 × 900 × 2500 mm kaldı. Bu iki çiftin örtüşme hacmi 0 mm³. Aynı kesim `meemare25-norm/kose_1.glb` dosyasına da uygulandı. Stüdyo bu dosyayı yüklüyor.

Üst ve alt tablaların yan kalınlığına 18 mm girmesi duruyor. O birleşim bu turda yeniden kesilmedi. Köşe bu yüzden tam üretim birleşimi sayılmaz.

## Görüntü

Kapaklar GLB içinde yok. Stüdyo kapak seçilince kanat üretiyor. Yeni modül hâlâ kapaksız geliyor.

Raster kareler, 1440 × 900 tuval:

- `shots/y26_tek_01-front.jpg` ve 45° kareler
- `shots/y26_cift_01-front.jpg`, `shots/y26_cift_01-drawer-open.jpg`
- `shots/kose_1-right45.jpg` L bacağın açık raf ve kapı kolunu birlikte gösteriyor

Ahşap, katalog JPEG’idir. Damar ölçeği fotoğraf değil. Kulp düz çubuk. Cam, kalınlık 0 ile çiziliyor. Path tracer hacim siyahı üretmesin diye cam kalınlığı açılmadı. Kumaş aksesuar ve gerçek ray eklenmedi.

## Ultra Render

Tek kapılı, kapaksız, varsayılan 12 geçiş, 48 örnek, 1400 × 728, GPU denoise sigma 1.6. Süre 12589 ms. Örnek başına 262 ms.  
Görüntü: `test-results/reference-quality/shots/ultra-tek.png`  
Sayılar: `results.json` içindeki `ultra-tek`.

Görüntüde sol yan yüz düz siyahtır. Duvar ve zemin beneklidir. İç hacim karanlıktır. Bu kare kabul eşiğinin altındadır. Örnek sayısı artırılmadı. Siyah yüzün nedeni bu turda kapatılmadı. Raster kare ile path kare aynı dolabın aynı oturumundan değildir. Path, stres testinden sonraki sahnedir.

Fotoğraf gerçekçiliği iddiası yok. Kastamonu görselleri resmi katalog diye sunulmuyor.
