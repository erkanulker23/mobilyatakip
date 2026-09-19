@php
    $entries = $entries ?? \App\Support\DrawingFiles::existingEntries(
        \App\Support\DrawingFiles::entries($drawingFiles ?? [])
    );
    $pdfUrls = [];
    foreach ($entries as $entry) {
        if (\App\Support\DrawingFiles::isPdf($entry)) {
            $url = \App\Support\DrawingFiles::url($entry['path']);
            if ($url) {
                $pdfUrls[] = $url;
            }
        }
    }
@endphp
@if(count($entries) > 0)
    @foreach($entries as $entry)
        @php
            $fileUrl = \App\Support\DrawingFiles::url($entry['path']);
            $name = $entry['name'] ?? basename($entry['path'] ?? 'Dosya');
            $isImage = \App\Support\DrawingFiles::isImage($entry);
            $isPdf = \App\Support\DrawingFiles::isPdf($entry);
        @endphp
        @if(! $fileUrl)
            @continue
        @endif
        <section class="print-drawing-page" aria-label="Ek: {{ $name }}">
            <header class="print-drawing-page-header">
                <p class="print-drawing-page-label">Ek çizim dosyası</p>
                <p class="print-drawing-page-title">{{ $name }}</p>
            </header>
            @if($isImage)
                <div class="print-drawing-page-body print-drawing-page-body--image">
                    <img src="{{ $fileUrl }}" alt="{{ $name }}" class="print-drawing-image">
                </div>
            @elseif($isPdf)
                <div
                    class="print-drawing-page-body print-drawing-page-body--pdf"
                    data-drawing-pdf-url="{{ $fileUrl }}"
                    data-drawing-pdf-name="{{ $name }}"
                >
                    <p class="print-drawing-pdf-loading">PDF yükleniyor…</p>
                </div>
            @else
                <div class="print-drawing-page-body">
                    <p class="print-muted">{{ $name }}</p>
                    <p class="print-muted text-sm break-all">{{ $fileUrl }}</p>
                </div>
            @endif
        </section>
    @endforeach

    @if(count($pdfUrls) > 0)
        @push('print-scripts')
        <script type="module">
            import * as pdfjsLib from 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.10.38/build/pdf.min.mjs';

            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.10.38/build/pdf.worker.min.mjs';

            async function renderPdfPages(container) {
                const url = container.dataset.drawingPdfUrl;
                if (!url) {
                    return;
                }

                try {
                    const pdf = await pdfjsLib.getDocument({ url, withCredentials: true }).promise;
                    container.textContent = '';
                    container.classList.add('print-drawing-pdf-pages');

                    for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                        const page = await pdf.getPage(pageNum);
                        const viewport = page.getViewport({ scale: 1 });
                        const maxWidth = 794;
                        const scale = Math.min(2, maxWidth / viewport.width);
                        const scaled = page.getViewport({ scale });

                        const canvas = document.createElement('canvas');
                        canvas.width = scaled.width;
                        canvas.height = scaled.height;
                        canvas.className = 'print-drawing-pdf-canvas';
                        if (pdf.numPages > 1) {
                            canvas.dataset.pdfPage = String(pageNum);
                        }

                        await page.render({
                            canvasContext: canvas.getContext('2d'),
                            viewport: scaled,
                        }).promise;

                        container.appendChild(canvas);
                    }
                } catch (error) {
                    container.innerHTML = '<p class="print-drawing-pdf-error">PDF önizlemesi yüklenemedi. Yazdırmadan önce dosyayı tarayıcıda açıp ekleyebilirsiniz.</p>';
                    console.error('Drawing PDF print render failed', error);
                }
            }

            const containers = [...document.querySelectorAll('[data-drawing-pdf-url]')];
            window.__drawingPrintReadyPromise = Promise.all(containers.map(renderPdfPages));
        </script>
        @endpush
    @endif
@endif
