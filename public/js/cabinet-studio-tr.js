(function () {
    const exact = {
        'Cabinet Studio': 'Üretim dolabı',
        'Cabinet Studio Layout': 'Mutfak yerleşimi',
        'Cabinet': 'Dolap',
        'Studio': 'Stüdyo',
        'Units': 'Birim',
        'Metric (mm)': 'Metrik (mm)',
        'Imperial (inches)': 'İnç',
        'Metric': 'Metrik',
        'Imperial': 'İnç',
        'Import': 'İçe aktar',
        'Import Project': 'Projeyi içe aktar',
        'Save Project': 'Projeyi kaydet',
        'Kitchen Layout': 'Mutfak yerleşimi',
        'Kitchen Layout View': 'Mutfak yerleşim görünümü',
        'Export Manufacturing ZIP': 'Üretim ZIP indir',
        'Cabinet Settings': 'Dolap ayarları',
        'Copies': 'Adet',
        'Show Edges': 'Kenarları göster',
        'Panel Preview': 'Panel önizleme',
        'Parts': 'Parçalar',
        'Nesting': 'Yerleşim',
        'Duplicate': 'Çoğalt',
        'Rename': 'Yeniden adlandır',
        'Delete': 'Sil',
        'Sheet Review': 'Levha inceleme',
        'Add cabinet': 'Dolap ekle',
        'Close preview': 'Önizlemeyi kapat',
        'Custom dimensions': 'Özel ölçü',
        'Cabinet Type': 'Dolap tipi',
        'Door Configuration': 'Kapak ayarı',
        'Drawer Configuration': 'Çekmece ayarı',
        'Interior': 'İç',
        'Construction': 'Konstrüksiyon',
        'Toe Kick': 'Baza',
        'Overlay & Reveal': 'Bindirme ve aralık',
        'Hardware': 'Donanım',
        'Category': 'Tür',
        'Width': 'Genişlik',
        'Height': 'Yükseklik',
        'Depth': 'Derinlik',
        'Doors': 'Kapaklar',
        'Drawers': 'Çekmeceler',
        'Base': 'Alt dolap',
        'Wall': 'Üst dolap',
        '1 door': '1 kapak',
        '2 doors': '2 kapak',
        'Left': 'Sol',
        'Right': 'Sağ',
        'Both (one each)': 'İki taraf (birer)',
        'Door layout': 'Kapak düzeni',
        'Hinge side': 'Menteşe yönü',
        'Routed detail': 'Ön yüz işlemi',
        'Plain (no detail)': 'Düz (işlemsiz)',
        'Vertical slats (V-bit)': 'Dikey çıta (V bıçak)',
        'Rectangle': 'Dikdörtgen',
        'Clipped corners': 'Kesik köşe',
        'Rounded corners': 'Yuvarlak köşe',
        'Inward rounded corners': 'İçe yuvarlak köşe',
        'Arched top': 'Kemerli üst',
        'Cathedral top': 'Katedral üst',
        'Detail inset from edges': 'Kenardan işlem payı',
        'Corner size / arch rise': 'Köşe ölçüsü / kemer yükselişi',
        'Slat line spacing (centre to centre)': 'Çıta aralığı (merkezden merkeze)',
        'Drawer count': 'Çekmece adedi',
        'Drawer front heights': 'Çekmece ön yükseklikleri',
        'Number of shelves': 'Raf sayısı',
        'Frameless (32mm system)': 'Çerçevesiz (32 mm sistem)',
        'Face-frame': 'Ön çerçeve',
        'Full top panel (instead of stretchers)': 'Tam üst tabla (kuşak yerine)',
        'Back mount': 'Arkalık montajı',
        'Grooved-in (thin back)': 'Kanala geçme (ince arkalık)',
        'Applied rear (structural)': 'Arkadan vidalı (taşıyıcı)',
        'Face frame width (stile/rail)': 'Ön çerçeve genişliği',
        'Top rail width': 'Üst kayıt genişliği',
        'Bottom rail width': 'Alt kayıt genişliği',
        'Toe kick height': 'Baza yüksekliği',
        'Toe kick setback': 'Baza geri çekmesi',
        'Overlay type': 'Bindirme tipi',
        'Full overlay': 'Tam bindirme',
        'Partial overlay': 'Kısmi bindirme',
        'Inset': 'İçe oturan',
        'Reveal / gap': 'Aralık',
        'Add another drawer': 'Bir çekmece daha ekle',
        'Connector pictures & datasheets': 'Bağlantı parçası resimleri ve föyleri',
        'Custom / existing dimensions': 'Özel / mevcut ölçü',
        'Drawing / reference': 'Çizim / referans',
        'Machining diagram': 'İşleme şeması',
        'Hinge cup': 'Menteşe yuvası',
        'Hinge plate': 'Menteşe tablası',
        'Shelf supports': 'Raf pimleri',
        'Handles / knobs': 'Kulp / düğme',
        'Drawer runners': 'Çekmece rayı',
        'Carcass joinery': 'Gövde birleşimi',
        'Hinge Cup': 'Menteşe yuvası',
        'Hinge Plate': 'Menteşe tablası',
        'Shelf Pins': 'Raf pimleri',
        'Shelf pins': 'Raf pimleri',
        'Handles': 'Kulplar',
        'Runners': 'Raylar',
        'Hinge bore diameter': 'Menteşe delik çapı',
        'Hinge bore inset (from door edge)': 'Menteşe deliği kenar payı',
        'Hinge cup depth': 'Menteşe yuvası derinliği',
        'Hinge plate pattern': 'Menteşe tablası delik düzeni',
        'Include second row (four holes per plate)': 'İkinci sıra (tablada dört delik)',
        'Hinge plate front hole offset': 'Tabla ön delik payı',
        'Hinge plate rear hole offset': 'Tabla arka delik payı',
        'Hinge plate hole spacing': 'Tabla delik aralığı',
        'South Africa (2 × 2)': 'Güney Afrika (2 × 2)',
        'Blum frameless (2 × 1)': 'Blum çerçevesiz (2 × 1)',
        'Other / Custom': 'Diğer / özel',
        'Shelf pin spacing': 'Raf pimi aralığı',
        'Shelf pin setback': 'Raf pimi geri payı',
        'Shelf pin diameter': 'Raf pimi çapı',
        'Shelf pin bore depth': 'Raf pimi delik derinliği',
        'Adjust these only if you want to change the shelf pin pattern.': 'Raf pimi düzenini değiştirmek istemiyorsanız bunlara dokunmayın.',
        'Handle hole spacing': 'Kulp delik aralığı',
        'Handle hole diameter': 'Kulp delik çapı',
        'Door handle edge offset': 'Kapak kulpu kenar payı',
        'Door handle centre height from bottom': 'Kapak kulpu alt merkez yüksekliği',
        'Drawer handle centre offset down from top': 'Çekmece kulpu üstten aşağı payı',
        'Handle drilling': 'Kulp deliği',
        'Pull (two holes)': 'Kulp (iki delik)',
        'Knob (one hole)': 'Düğme (tek delik)',
        'None': 'Yok',
        'Drawer runner clearance per side': 'Çekmece rayı yan boşluğu',
        'Runner length': 'Ray boyu',
        'Runner output': 'Ray çıktısı',
        'Custom clearance / reference holes': 'Özel boşluk / referans delik',
        'Side mount / selected length (no mounting holes)': 'Yan montaj / seçilen boy (montaj deliği yok)',
        'Fit only. Mounting holes use the manufacturer drawing or jig.': 'Yalnızca oturma ölçüsü. Montaj delikleri üretici çizimine veya şablona göredir.',
        'Dowel diameter': 'Kavela çapı',
        'Dowel length': 'Kavela boyu',
        'Dowel face depth': 'Kavela yüzey derinliği',
        'Dowel edge depth': 'Kavela kenar derinliği',
        'Dowel end offset': 'Kavela uç payı',
        'Dowel maximum spacing': 'Kavela en fazla aralığı',
        'Connector end offset': 'Bağlantı uç payı',
        'Connector maximum spacing': 'Bağlantı en fazla aralığı',
        'Dado engagement depth': 'Kanal geçme derinliği',
        'Dado fit clearance': 'Kanal oturma boşluğu',
        'Plate bore diameter': 'Tabla delik çapı',
        'Plate bore depth': 'Tabla delik derinliği',
        'Entered height — click to unlock and size automatically': 'Girilen yükseklik — kilidi açıp otomatik boyutlamak için tıklayın',
        'Automatic height — click to lock this value': 'Otomatik yükseklik — bu değeri kilitlemek için tıklayın',
        'Calculate automatic heights now': 'Otomatik yükseklikleri şimdi hesapla',
        'Two holes per row. Blum frameless 37/32 uses one row, 37 mm from the front, with 32 mm vertical spacing. Verify your plate; face-frame and other plates differ.': 'Her satırda iki delik. Blum çerçevesiz 37/32 önde 37 mm, dikeyde 32 mm tek sıra kullanır. Tablanızı kontrol edin; ön çerçeve ve diğer tablalar farklıdır.',
        'Dowels: face bores in DXF; edge bores in the separate machining schedule.': 'Kavelalar: yüzey delikleri DXF içindedir; kenar delikleri ayrı işleme listesindedir.',
        'Global Stock Setup': 'Genel plaka ayarı',
        'Nesting Settings': 'Yerleşim ayarları',
        'Carcass Stock': 'Gövde plakası',
        'Rear Stock': 'Arkalık plakası',
        'Front Stock': 'Ön plaka',
        'Drawer Side / Back Stock': 'Çekmece yan / arka plakası',
        'Drawer Bottom Stock': 'Çekmece alt plakası',
        'Sheet length': 'Levha boyu',
        'Sheet width': 'Levha eni',
        'Nesting gap': 'Parça aralığı',
        'Allow part rotation': 'Parça döndürülebilsin',
        'Material': 'Malzeme',
        'Colour': 'Renk',
        'Thickness': 'Kalınlık',
        'Existing thickness': 'Mevcut kalınlık',
        'Sheets': 'Levhalar',
        'Utilization': 'Verim',
        'No panels computed': 'Hesaplanmış panel yok',
        'No nesting layout available': 'Yerleşim planı yok',
        'Sheet Stock': 'Levha',
        'Panel': 'Panel',
        'DXF': 'DXF',
        'Room Setup': 'Oda ayarı',
        'Kitchen Envelope': 'Mutfak ölçüsü',
        'Room Width': 'Oda genişliği',
        'Room Depth': 'Oda derinliği',
        'Room Height': 'Oda yüksekliği',
        'Wall Cabinet Bottom': 'Üst dolap alt hizası',
        'Project Summary': 'Proje özeti',
        'Cabinets': 'Dolaplar',
        'Placed Units': 'Yerleşen birimler',
        'Selected Cabinet': 'Seçilen dolap',
        'Name': 'Ad',
        'Position X': 'Konum X',
        'Position Z': 'Konum Z',
        'Rotation': 'Dönüş',
        'Size': 'Ölçü',
        'Rotate 90°': '90° döndür',
        'Cabinet Instances': 'Dolaplar',
        'Auto Arrange': 'Otomatik diz',
        'Drag to place': 'Yerleştirmek için sürükle',
        'Right click to rotate': 'Döndürmek için sağ tık',
        'No cabinet selected': 'Dolap seçilmedi',
        'Kitchen Project': 'Mutfak projesi',
        'Base and tall cabinets auto-start on the floor. Wall cabinets auto-start on the wall row at the configured wall height.': 'Alt ve boy dolaplar yerde başlar. Üst dolaplar ayarlanan yükseklikte duvar sırasında başlar.',
        'Select a cabinet in the scene or list to adjust its placement. Right click a cabinet in the viewport to rotate it 90°.': 'Yerini değiştirmek için sahneden veya listeden dolap seçin. 90° döndürmek için sağ tıklayın.',
        'Import a project JSON or start with a saved project from Cabinet Studio.': 'Bir proje JSON dosyası içe aktarın veya kayıtlı bir projeyle başlayın.',
        'Import a project JSON to start laying out cabinets in the room.': 'Dolapları odaya dizmek için bir proje JSON dosyası içe aktarın.',
        'Kitchen Project · drag cabinets to rearrange the room.': 'Mutfak projesi · odayı değiştirmek için dolapları sürükleyin.',
        'Drag cabinets to rearrange them. Rotation swaps the footprint so corner planning is easier, but collision checking is not enforced yet.': 'Dolapları sürükleyerek yerini değiştirin. Döndürünce taban ölçüsü değişir. Çarpışma kontrolü henüz yoktur.',
        'Hardware warnings — see settings / manifest': 'Donanım uyarısı — ayarlara ve listeye bakın',
        'Routed front detail': 'Ön yüz işlemi',
        'Left Side': 'Sol yan',
        'Right Side': 'Sağ yan',
        'Back Panel': 'Arkalık',
        'Bottom': 'Alt tabla',
        'Top': 'Üst tabla',
        'Rail Bottom': 'Alt kayıt',
        'Rail Top': 'Üst kayıt',
        'Stretcher Back': 'Arka kuşak',
        'Stretcher Front': 'Ön kuşak',
        'Stile Left': 'Sol dikme',
        'Stile Right': 'Sağ dikme',
        'Door Material': 'Kapak malzemesi',
        'Drawer Bottom Material': 'Çekmece alt malzemesi',
        'Drawer Box Material': 'Çekmece kutusu malzemesi',
        'Carcass': 'Gövde',
        'Unspecified': 'Belirtilmemiş',
        'Melamine White': 'Beyaz melamin',
        'Melamine Storm Grey': 'Fırtına grisi melamin',
        'Melamine Heavy Duty': 'Kalın melamin',
        'Carcass Melamine White': 'Beyaz melamin gövde',
        'Door/Front Melamine White': 'Beyaz melamin kapak',
        'Hardboard White': 'Beyaz sert levha',
        'Hardboard Raw': 'Ham sert levha',
        'Birch Ply': 'Huş kontrplak',
        'Birch Ply 12 mm': 'Huş kontrplak 12 mm',
        'MDF White': 'Beyaz MDF',
        'Oak Veneer': 'Meşe kaplama',
        'Painted MDF': 'Boyalı MDF',
        'Ply 6 mm': 'Kontrplak 6 mm',
        'Ply Utility': 'Kontrplak',
        'Super Gloss': 'Yüksek parlak',
        'left wall': 'sol duvar',
        'right wall': 'sağ duvar',
        'back wall': 'arka duvar',
        'front wall': 'ön duvar',
        'White': 'Beyaz',
        'Storm Grey': 'Fırtına grisi',
        'Black': 'Siyah',
        'Natural': 'Doğal',
        'Raw': 'Ham',
        'Melamine': 'Melamin',
        'Hardboard': 'Sert levha',
        'Plywood': 'Kontrplak',
        'Ply': 'Kontrplak',
        '35 mm concealed cup — custom fixing': '35 mm gizli menteşe — özel montaj',
        'South Africa — existing 20/32 four-hole pattern': 'Güney Afrika — 20/32 dört delik',
        'Blum cruciform — 37/32 two-hole pattern': 'Blum haç — 37/32 iki delik',
        '5 mm pins — 32 mm pitch / 37 mm setback': '5 mm pim — 32 mm aralık / 37 mm pay',
        '¼-inch pins — 32 mm pitch / 37 mm setback': '¼ inç pim — 32 mm aralık / 37 mm pay',
        'No handle drilling': 'Kulp deliği yok',
        'Knob — one fixing hole': 'Düğme — tek delik',
        'Pull — 3 in (76.2 mm) centres': 'Kulp — 76,2 mm ara',
        'Pull — 96 mm centres': 'Kulp — 96 mm ara',
        'Pull — 4 in (101.6 mm) centres': 'Kulp — 101,6 mm ara',
        'Pull — 128 mm centres': 'Kulp — 128 mm ara',
        'Lamello Cabineo 8 — surface mounted': 'Lamello Cabineo 8 — yüzeye montaj',
        'Lamello Cabineo 12 — surface mounted': 'Lamello Cabineo 12 — yüzeye montaj',
        'Through dados — carcass bottom': 'Geçme kanal — gövde altı',
        'Hettich Rastex 15 + Twister — 18 mm stock': 'Hettich Rastex 15 + Twister — 18 mm',
        '18 mm cam/bolt set implemented. Ø15 pocket plus edge passage and mating bolt pilot.': '18 mm kam/cıvata seti uygulanır. Ø15 yuva, kenar geçişi ve cıvata pilotu.',
        'Reference only. Cam depth depends on stock; 24/34 mm offset and edge drilling depend on the bolt.': 'Yalnızca referans. Kam derinliği plakaya, 24/34 mm pay ve kenar deliği cıvataya bağlıdır.',
        'Reference only. Ø20 open-edge pocket; housing, panel thickness and mating bolt must match.': 'Yalnızca referans. Ø20 açık kenar yuvası; gövde, kalınlık ve cıvata birbirine uymalıdır.',
        'Cabineo 8/12 surface-mounted corner joints implemented. Flush, cover-cap and M6 variants differ and are not included.': 'Cabineo 8/12 yüzey köşe birleşimi uygulanır. Sıfır, kapak ve M6 çeşitleri farklıdır ve dahil değildir.',
        'Choose the door layout that suits this cabinet.': 'Bu dolaba uygun kapak düzenini seçin.',
        'Choose how much shelf storage you want inside the cabinet.': 'Dolabın içinde kaç raf istediğinizi seçin.',
        'Set hinge, shelf pin, and handle positions for this cabinet.': 'Bu dolap için menteşe, raf pimi ve kulp yerlerini ayarlayın.',
    };

    function partName(name) {
        if (exact[name]) return exact[name];
        return String(name)
            .replace(/^Shelf (\d+)$/, 'Raf $1')
            .replace(/^Door (\d+)$/, 'Kapak $1')
            .replace(/^Drawer (\d+)$/, 'Çekmece $1')
            .replace(/^Cabinet (\d+)$/, 'Dolap $1');
    }

    const phrases = Object.entries(exact)
        .filter(([from]) => from.includes(' ') && from.length > 8)
        .sort((a, b) => b[0].length - a[0].length);

    const patterns = [
        [/^(\d+) cabinets in project$/, '$1 dolap'],
        [/^(\d+) cabinet in project$/, '$1 dolap'],
        [/^(\d+) parts$/, '$1 parça'],
        [/^(\d+) part$/, '$1 parça'],
        [/^(\d+) units$/, '$1 birim'],
        [/^(\d+) unit$/, '$1 birim'],
        [/^(\d+) sheets$/, '$1 levha'],
        [/^(\d+) sheet$/, '$1 levha'],
        [/^Sheet (\d+)$/, 'Levha $1'],
        [/^Delete "this cabinet" from the project\?$/, 'Bu dolap projeden silinsin mi?'],
        [/^Delete "([^"]+)" from the project\?$/, '"$1" projeden silinsin mi?'],
        [/^Invalid JSON file: (.+)$/, 'Geçersiz JSON dosyası: $1'],
        [/^Drawer fronts fit\. Target total: (.+)$/, 'Çekmece önleri oturuyor. Hedef toplam: $1'],
        [/^Add (.+) more across the drawer fronts\. Target total: (.+)$/, 'Çekmece önlerine toplam $1 daha ekleyin. Hedef: $2'],
        [/^Reduce drawer fronts by (.+) total\. Target total: (.+)$/, 'Çekmece önlerini toplam $1 azaltın. Hedef: $2'],
        [/^(.+) remaining\. Add another drawer\?$/, '$1 boşluk kaldı. Bir çekmece daha eklensin mi?'],
        [/^Entered drawer heights must be at least (.+)\.$/, 'Girilen çekmece yüksekliği en az $1 olmalı.'],
        [/^All drawer heights are entered\. Clear a height to let Auto adjust it\.$/, 'Bütün çekmece yükseklikleri girilmiş. Otomatik ayar için birini temizleyin.'],
        [/^Entered heights leave too little space for the remaining drawers\. Reduce or clear an entered height\.$/, 'Girilen yükseklikler kalan çekmecelere az yer bırakıyor. Bir yüksekliği azaltın veya temizleyin.'],
        [/^(\d+) sheets? · (\d+) parts · ([0-9.]+)% utilization$/, '$1 levha · $2 parça · %$3 verim'],
        [/^Enlarge (.+) preview$/, (_, name) => `${partName(name)} önizlemesini büyüt`],
        [/^Shelf (\d+)$/, 'Raf $1'],
        [/^Door (\d+)$/, 'Kapak $1'],
        [/^Drawer (\d+)$/, 'Çekmece $1'],
        [/^Cabinet (\d+)$/, 'Dolap $1'],
        [/^Pull — (.+) centres$/, 'Kulp — $1 ara'],
        [/^Dowels — (.+)$/, 'Kavela — $1'],
        [/^Accuride 3832E — side-mount fit$/, 'Accuride 3832E — yan montaj oturması'],
        [/^Pocket screws — manual jig assembly$/, 'Cep vidası — elde şablon'],
        [/(\d+) parts/g, '$1 parça'],
        [/ library$/g, ''],
        [/^Open larger review for sheet (\d+)$/, 'Levha $1 için büyük inceleme'],
        [/^Some parts could not be nested: (.+)$/, 'Bazı parçalar levhaya sığmadı: $1'],
        [/^Part does not fit on a (.+) sheet$/, 'Parça $1 levhaya sığmıyor'],
        [/^Gap increased to (.+) for open-edge connector machining\.$/, 'Açık kenar bağlantısı için aralık $1 yapıldı.'],
        [/^(Base|Wall|Tall) cabinet · (.+)$/, (_, kind, rest) => {
            const names = { Base: 'Alt', Wall: 'Üst', Tall: 'Boy' };
            return (names[kind] || kind) + ' dolap · ' + rest;
        }],
        [/(\d+) face holes/g, '$1 yüzey deliği'],
        [/(\d+) edge bores \(separate setup\)/g, '$1 kenar deliği (ayrı ayar)'],
        [/(\d+) holes/g, '$1 delik'],
        [/(\d+) notches/g, '$1 kertik'],
        [/(\d+) grooves/g, '$1 kanal'],
        [/(\d+) slat lines/g, '$1 çıta çizgisi'],
        [/Edge banding: ([a-z/]+)/g, (_, parts) => {
            const side = { top: 'üst', bottom: 'alt', left: 'sol', right: 'sağ' };
            return 'Kenar bandı: ' + parts.split('/').map((part) => side[part] || part).join('/');
        }],
        [/ · snap: ([a-z ]+)$/, (_, side) => ' · yaslama: ' + (exact[side] || side)],
    ];

    function translate(value) {
        if (typeof value !== 'string') return value;
        const lead = value.match(/^\s*/)?.[0] ?? '';
        const trail = value.match(/\s*$/)?.[0] ?? '';
        let text = value.trim();
        if (!text || !/[A-Za-z]/.test(text)) return value;
        if (exact[text]) return lead + exact[text] + trail;
        for (const [pattern, replacement] of patterns) {
            pattern.lastIndex = 0;
            if (typeof replacement === 'function') {
                if (!pattern.test(text)) continue;
                pattern.lastIndex = 0;
                text = text.replace(pattern, replacement);
            } else if (pattern.test(text)) {
                pattern.lastIndex = 0;
                text = text.replace(pattern, replacement);
            }
        }
        for (const [from, to] of phrases) {
            if (text.includes(from)) text = text.split(from).join(to);
        }
        return lead + text + trail;
    }

    if (typeof window === 'undefined') return;

    const nativeAlert = window.alert.bind(window);
    const nativeConfirm = window.confirm.bind(window);
    window.alert = (message) => nativeAlert(translate(String(message)));
    window.confirm = (message) => nativeConfirm(translate(String(message)));

    const attrs = ['title', 'aria-label', 'placeholder'];

    function translateAttrs(element) {
        for (const name of attrs) {
            if (!element.hasAttribute?.(name)) continue;
            const next = translate(element.getAttribute(name));
            if (next !== element.getAttribute(name)) element.setAttribute(name, next);
        }
    }

    function walk(node) {
        if (!node) return;
        if (node.nodeType === 3) {
            const next = translate(node.nodeValue);
            if (next !== node.nodeValue) node.nodeValue = next;
            return;
        }
        if (node.nodeType !== 1) return;
        const tag = node.tagName;
        if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'NOSCRIPT') return;
        translateAttrs(node);
        for (const child of [...node.childNodes]) walk(child);
    }

    function start() {
        walk(document.documentElement);
        const observer = new MutationObserver((records) => {
            for (const record of records) {
                if (record.type === 'characterData') walk(record.target);
                else if (record.type === 'attributes') translateAttrs(record.target);
                else record.addedNodes.forEach(walk);
            }
        });
        observer.observe(document.documentElement, {
            subtree: true,
            childList: true,
            characterData: true,
            attributes: true,
            attributeFilter: attrs,
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
