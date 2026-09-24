@php
    $activePortal = $activePortal ?? null;
@endphp
<nav class="hero-portals rise rise-3" aria-label="Hızlı erişim">
    <div class="portals">
        <a class="portal @if($activePortal === 'takip') is-active @endif" href="{{ url('/takip') }}">
            <span class="portal-ico" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/><path d="M11 8v3l2 1"/></svg>
            </span>
            <strong>Takip kodu sorgula</strong>
            <span>Sipariş veya SSH kodunuzla durumu görün</span>
        </a>
        <a class="portal @if($activePortal === 'katalog') is-active @endif" href="{{ url('/katalog') }}">
            <span class="portal-ico" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="4" width="7" height="7" rx="1"/><rect x="14" y="4" width="7" height="7" rx="1"/><rect x="3" y="13" width="7" height="7" rx="1"/><rect x="14" y="13" width="7" height="7" rx="1"/></svg>
            </span>
            <strong>Kataloga göz at</strong>
            <span>Kastamonu Entegre renk ve kaplamalar</span>
        </a>
        <a class="portal @if($activePortal === 'tasarla') is-active @endif" href="{{ url('/tasarla') }}">
            <span class="portal-ico" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 18V6l8-3 8 3v12l-8 3-8-3z"/><path d="M12 3v18M4 6l8 3 8-3"/></svg>
            </span>
            <strong>3D tasarımcıya git</strong>
            <span>TV ünitesini sürükle-bırak ile kurun</span>
        </a>
    </div>
</nav>
