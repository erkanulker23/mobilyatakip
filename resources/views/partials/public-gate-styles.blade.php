<style>
    :root {
        --ink: #141816;
        --muted: #5c6560;
        --line: rgba(20, 24, 22, 0.1);
        --accent: #1f6b4a;
        --accent-soft: #e8f3ed;
        --panel: #f7f5f1;
        --wood: #2a332e;
    }
    * { box-sizing: border-box; }
    html, body {
        margin: 0; min-height: 100%;
        font-family: Montserrat, system-ui, sans-serif;
        -webkit-font-smoothing: antialiased;
        color: var(--ink);
    }

    .gate {
        min-height: 100vh;
        display: grid;
        grid-template-columns: 1.15fr 0.85fr;
        background: var(--wood);
    }
    @media (max-width: 960px) {
        .gate { grid-template-columns: 1fr; }
    }

    .pub-page {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        background: var(--panel);
    }

    .hero {
        position: relative;
        overflow: hidden;
        padding: clamp(1.5rem, 4vw, 3rem);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 52vh;
        background:
            radial-gradient(120% 80% at 10% 0%, rgba(61, 110, 84, 0.45), transparent 55%),
            radial-gradient(90% 70% at 90% 100%, rgba(20, 24, 22, 0.55), transparent 50%),
            linear-gradient(145deg, #1c2621 0%, #2f3d35 42%, #1a221e 100%);
        color: #f4f1ea;
    }
    .hero--page {
        min-height: 0;
        padding-bottom: clamp(1.25rem, 3vw, 2rem);
    }
    .hero::before {
        content: '';
        position: absolute; inset: 0;
        background-image:
            linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px),
            linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px);
        background-size: 48px 48px;
        mask-image: linear-gradient(180deg, rgba(0,0,0,.55), transparent 85%);
        pointer-events: none;
    }
    .hero::after {
        content: '';
        position: absolute;
        right: -12%;
        bottom: -18%;
        width: min(70vw, 520px);
        height: min(70vw, 520px);
        border-radius: 50%;
        background: radial-gradient(circle, rgba(232, 243, 237, 0.12), transparent 68%);
        pointer-events: none;
        animation: drift 12s ease-in-out infinite alternate;
    }
    @keyframes drift {
        from { transform: translate(0, 0) scale(1); }
        to { transform: translate(-4%, -3%) scale(1.06); }
    }
    @keyframes rise {
        from { opacity: 0; transform: translateY(14px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .rise { animation: rise .7s ease both; }
    .rise-2 { animation-delay: .12s; }
    .rise-3 { animation-delay: .22s; }
    .rise-4 { animation-delay: .32s; }

    .hero-top, .hero-main, .hero-portals { position: relative; z-index: 1; }
    .hero-top-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .hero-top-links {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        flex-wrap: wrap;
    }
    .hero-top-links a {
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        text-decoration: none;
        color: rgba(244, 241, 234, 0.65);
        padding: 0.35rem 0.55rem;
        border-radius: 8px;
        transition: color .15s, background .15s;
    }
    .hero-top-links a:hover {
        color: #f4f1ea;
        background: rgba(255,255,255,0.08);
    }
    .hero-top-links a.is-accent {
        color: #b7d4c4;
        background: rgba(255,255,255,0.1);
    }

    .brand-lockup {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        text-decoration: none;
        color: inherit;
    }
    .brand-lockup img {
        height: clamp(2.75rem, 6vw, 3.75rem);
        width: auto;
        max-width: 13rem;
        object-fit: contain;
        filter: drop-shadow(0 8px 24px rgba(0,0,0,.25));
    }
    .brand-word {
        font-family: Montserrat, system-ui, sans-serif;
        font-size: clamp(1.35rem, 3.5vw, 1.85rem);
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        line-height: 1.1;
    }
    .hero-kicker {
        margin: 0 0 0.75rem;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: rgba(244, 241, 234, 0.55);
    }
    .hero-title {
        margin: 0;
        font-family: Montserrat, system-ui, sans-serif;
        font-size: clamp(1.85rem, 4.5vw, 2.75rem);
        font-weight: 700;
        line-height: 1.08;
        letter-spacing: -0.03em;
        max-width: 18ch;
    }
    .hero--page .hero-title { max-width: 24ch; }
    .hero-lead {
        margin: 1rem 0 0;
        max-width: 42ch;
        font-size: 0.95rem;
        line-height: 1.55;
        color: rgba(244, 241, 234, 0.72);
        font-weight: 400;
    }

    .portals {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
        margin-top: clamp(1.75rem, 4vw, 2.5rem);
    }
    .hero--page .portals {
        margin-top: 1.25rem;
        grid-template-columns: repeat(3, 1fr);
    }
    @media (max-width: 640px) {
        .portals { grid-template-columns: 1fr; }
    }
    .portal {
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
        padding: 1.05rem 1.1rem 1.15rem;
        border: 1px solid rgba(255,255,255,0.14);
        background: rgba(255,255,255,0.06);
        backdrop-filter: blur(10px);
        text-decoration: none;
        color: #f4f1ea;
        border-radius: 4px;
        transition: background .2s ease, border-color .2s ease, transform .2s ease;
    }
    .portal:hover {
        background: rgba(255,255,255,0.12);
        border-color: rgba(255,255,255,0.28);
        transform: translateY(-2px);
    }
    .portal.is-active {
        background: rgba(255,255,255,0.14);
        border-color: rgba(183, 212, 196, 0.55);
        pointer-events: none;
    }
    .portal-ico {
        width: 2rem; height: 2rem;
        display: grid; place-items: center;
        color: #b7d4c4;
    }
    .portal strong {
        font-size: 0.92rem;
        font-weight: 600;
        letter-spacing: -0.01em;
    }
    .portal span {
        font-size: 0.75rem;
        line-height: 1.4;
        color: rgba(244, 241, 234, 0.58);
        font-weight: 500;
    }

    .panel {
        background: var(--panel);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: clamp(1.5rem, 4vw, 3rem);
    }
    .panel-inner { width: 100%; max-width: 380px; }
    .panel-head { margin-bottom: 1.75rem; }
    .panel-head h2 {
        margin: 0;
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: var(--ink);
    }
    .panel-head p {
        margin: 0.4rem 0 0;
        font-size: 0.875rem;
        color: var(--muted);
        line-height: 1.45;
    }
    .field { margin-bottom: 1.1rem; }
    .field-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.45rem;
    }
    .field label {
        display: block;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--muted);
        margin-bottom: 0.45rem;
        letter-spacing: 0.02em;
    }
    .field-row label { margin-bottom: 0; }
    .field input[type="email"],
    .field input[type="password"],
    .field input[type="text"] {
        width: 100%;
        border: 1px solid var(--line);
        background: #fff;
        border-radius: 12px;
        padding: 0.8rem 0.95rem;
        font: inherit;
        font-size: 0.9rem;
        color: var(--ink);
        transition: border-color .15s, box-shadow .15s;
    }
    .field input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(31, 107, 74, 0.15);
    }
    .field input.is-error { border-color: #dc2626; }
    .err { margin: 0.4rem 0 0; font-size: 0.8rem; color: #dc2626; }
    .flash {
        margin-bottom: 1rem;
        padding: 0.75rem 0.9rem;
        border-radius: 12px;
        font-size: 0.85rem;
    }
    .flash-ok { background: #e8f3ed; color: #14532d; }
    .flash-err { background: #fff7ed; color: #9a3412; }
    .link-quiet {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--accent);
        text-decoration: none;
    }
    .link-quiet:hover { text-decoration: underline; }
    .remember {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0.25rem 0 1.25rem;
        font-size: 0.85rem;
        color: var(--muted);
    }
    .remember input { width: 1rem; height: 1rem; accent-color: var(--accent); }
    .btn-login,
    .pub-btn {
        border: 0;
        border-radius: 12px;
        padding: 0.95rem 1.25rem;
        background: var(--accent);
        color: #fff;
        font: inherit;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: background .15s ease, transform .15s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-login { width: 100%; }
    .btn-login:hover, .pub-btn:hover { background: #185a3e; transform: translateY(-1px); }
    .btn-login:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
    .panel-note {
        margin: 1.5rem 0 0;
        padding-top: 1.25rem;
        border-top: 1px solid var(--line);
        font-size: 0.78rem;
        color: var(--muted);
        line-height: 1.5;
        text-align: center;
    }

    .pub-main {
        flex: 1;
        padding: clamp(1.5rem, 4vw, 2.5rem) clamp(1rem, 3vw, 1.5rem) 3rem;
    }
    .pub-wrap { margin: 0 auto; width: 100%; }
    .pub-wrap--md { max-width: 40rem; }
    .pub-wrap--lg { max-width: 72rem; }

    .pub-section-head { margin-bottom: 1.75rem; }
    .pub-section-head h1 {
        margin: 0;
        font-size: clamp(1.35rem, 2.5vw, 1.65rem);
        font-weight: 700;
        letter-spacing: -0.02em;
        color: var(--ink);
    }
    .pub-section-head p {
        margin: 0.5rem 0 0;
        font-size: 0.9rem;
        line-height: 1.55;
        color: var(--muted);
        max-width: 42rem;
    }

    .pub-crumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem;
        margin-bottom: 1.25rem;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--muted);
    }
    .pub-crumb a {
        color: var(--accent);
        text-decoration: none;
    }
    .pub-crumb a:hover { text-decoration: underline; }

    .pub-box {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 16px;
        padding: 1.25rem 1.35rem;
        box-shadow: 0 1px 0 rgba(20, 24, 22, 0.04);
    }
    .pub-form-row {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    @media (min-width: 640px) {
        .pub-form-row { flex-direction: row; }
        .pub-form-row .field { flex: 1; margin-bottom: 0; }
    }
    .pub-hint {
        margin: 0.75rem 0 0;
        font-size: 0.75rem;
        color: var(--muted);
    }
    .pub-hint code {
        font-family: ui-monospace, monospace;
        font-size: 0.85em;
        color: var(--ink);
    }

    .pub-alert {
        border-radius: 14px;
        padding: 1rem 1.15rem;
        font-size: 0.875rem;
        margin-top: 1rem;
    }
    .pub-alert-warn {
        background: #fff7ed;
        border: 1px solid rgba(234, 88, 12, 0.2);
        color: #9a3412;
    }
    .pub-alert-warn strong { display: block; font-weight: 600; margin-bottom: 0.25rem; }

    .pub-grid {
        display: grid;
        gap: 1rem;
    }
    @media (min-width: 640px) {
        .pub-grid--2 { grid-template-columns: repeat(2, 1fr); }
        .pub-grid--3 { grid-template-columns: repeat(3, 1fr); }
    }

    .pub-card {
        display: block;
        text-decoration: none;
        color: inherit;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 16px;
        overflow: hidden;
        transition: border-color .2s, box-shadow .2s, transform .2s;
    }
    .pub-card:hover {
        border-color: rgba(31, 107, 74, 0.35);
        box-shadow: 0 14px 40px rgba(20, 24, 22, 0.08);
        transform: translateY(-2px);
    }
    .pub-card-body { padding: 1.25rem 1.35rem; }
    .pub-card-kicker {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: var(--accent);
        margin-bottom: 0.35rem;
    }
    .pub-card-title {
        margin: 0;
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: -0.02em;
        color: var(--ink);
    }
    .pub-card-text {
        margin: 0.45rem 0 0;
        font-size: 0.85rem;
        line-height: 1.45;
        color: var(--muted);
    }
    .pub-card-link {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        margin-top: 0.85rem;
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--accent);
    }
    .pub-card-media {
        aspect-ratio: 16 / 9;
        background: #e8e4df;
        overflow: hidden;
    }
    .pub-card-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .45s ease;
    }
    .pub-card:hover .pub-card-media img { transform: scale(1.03); }
    .pub-card-media--placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--muted);
    }

    .pub-card--featured {
        display: flex;
        flex-direction: column;
        border-color: rgba(31, 107, 74, 0.25);
    }
    @media (min-width: 640px) {
        .pub-card--featured { flex-direction: row; }
        .pub-card--featured .pub-card-media {
            width: 14rem;
            aspect-ratio: auto;
            min-height: 9rem;
            flex-shrink: 0;
        }
    }

    .pub-empty {
        text-align: center;
        padding: 2.5rem 1.5rem;
        color: var(--muted);
        font-size: 0.9rem;
    }

    .pub-footer {
        padding: 1.25rem 1rem 1.75rem;
        text-align: center;
        font-size: 0.72rem;
        font-weight: 500;
        color: var(--muted);
        border-top: 1px solid var(--line);
    }
    .pub-footer a {
        color: var(--accent);
        text-decoration: none;
        font-weight: 600;
    }
    .pub-footer a:hover { text-decoration: underline; }

    .pub-result .bg-white { background: #fff !important; }
    .pub-result .dark\:bg-neutral-900 { background: #fff !important; }
    .pub-result .border-neutral-200 { border-color: var(--line) !important; }
    .pub-result .dark\:border-neutral-800 { border-color: var(--line) !important; }
    .pub-result .text-neutral-900 { color: var(--ink) !important; }
    .pub-result .text-neutral-500 { color: var(--muted) !important; }
    .pub-result .text-neutral-600 { color: var(--muted) !important; }
    .pub-result .text-neutral-400 { color: #8a928d !important; }
    .pub-result .rounded-2xl { border-radius: 16px !important; }

    @media (max-width: 960px) {
        .hero { min-height: auto; padding-bottom: 2rem; }
        .hero-title { max-width: none; }
    }
</style>
