<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.site-meta', [
        'company' => $company,
        'pageTitle' => '3D Dolap Tasarla',
        'metaDescription' => 'Meemare hazır dolap modüllerini odanın duvarına dizin. Kapak, raf ve renk canlı değişir.',
    ])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.meemare-shell-styles')
    <style>
        :root {
            --ink: #1c1c1c;
            --muted: #6d6a64;
            --line: #e6e2da;
            --bg: #f4f3f0;
            --panel: #fffcf8;
            --accent: #1f6b4a;
            --danger: #9f2d2d;
            --shadow: 0 12px 32px rgba(28, 28, 28, .08);
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; height: 100dvh; overflow: hidden; font-family: Montserrat, system-ui, sans-serif; color: var(--ink); background: var(--bg); }
        button, input, select { font-family: inherit; }
        button { cursor: pointer; }
        #studio {
            display: grid; grid-template-rows: auto minmax(0, 1fr) auto;
            height: 100dvh; min-height: 0;
            padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom);
        }
        .studio-top {
            display: flex; align-items: center; gap: 8px; flex-wrap: nowrap;
            padding: 6px 10px; background: var(--panel); border-bottom: 1px solid var(--line);
            min-height: 40px; height: 40px;
        }
        .studio-top .brand { flex: 0 0 auto; }
        .studio-top .brand span { display: none; }
        .studio-top .name-input {
            flex: 1 1 120px; min-width: 88px; max-width: 180px;
            border: 1px solid var(--line); border-radius: 8px; padding: 5px 8px; background: #fff;
            font-size: 13px;
        }
        .studio-top__nav { flex: 0 0 auto; min-width: 0; margin-left: auto; }
        .studio-top__actions { display: flex; gap: 5px; flex-wrap: nowrap; align-items: center; }
        .studio-top__actions .btn { padding: 5px 8px; }
        .studio-top__actions .btn-quiet { color: var(--muted); }
        footer.bar, .series {
            display: flex; align-items: center; gap: 8px; padding: 0 14px; background: var(--panel);
        }
        #room-plan-panel {
            display: none; position: fixed; inset: 0; z-index: 25; max-width: none; margin: 0;
            grid-template-rows: auto minmax(0, 1fr) auto; background: #eceae6; border: 0; border-radius: 0;
            box-shadow: none; min-height: 0; overflow: hidden;
            padding-top: env(safe-area-inset-top);
        }
        body.show-room-plan #room-plan-panel { display: grid; visibility: visible; }
        body.show-room-plan .workspace,
        body.show-room-plan .studio-top,
        body.show-room-plan footer.bar { visibility: hidden; }
        /* Panel .workspace içinde; visibility miras alınır — açıkça görünür tut */
        body.show-room-plan .room-plan-backdrop,
        body.show-room-plan #room-plan-panel,
        body.show-room-plan #room-plan-panel * { visibility: visible; }
        .room-sketch-head {
            display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; padding: 8px 12px;
            background: #fffcf8; border-bottom: 1px solid var(--line); min-height: 48px;
        }
        .room-sketch-head strong { font-size: 14px; letter-spacing: .02em; white-space: nowrap; }
        .room-sketch-head .grow-h { display: none; }
        .room-sketch-body {
            display: grid; grid-template-columns: 196px minmax(0, 1fr); min-height: 0; min-width: 0; height: 100%; overflow: hidden;
        }
        .room-sketch-wrap.room-sketch-body { grid-template-rows: minmax(0, 1fr); }
        @media (max-width: 720px) {
            .room-sketch-body,
            .room-sketch-wrap.room-sketch-body {
                grid-template-columns: 1fr;
                grid-template-rows: minmax(0, 1fr) auto;
            }
            .room-sketch-wrap .room-sketch-stage { grid-row: 1; min-height: 240px; }
            .room-sketch-wrap .room-sketch-tools {
                grid-row: 2; flex-direction: row !important; flex-wrap: nowrap; align-items: stretch;
                border-right: 0 !important; border-top: 1px solid var(--line); border-bottom: 0;
                max-height: 148px; overflow: auto; padding: 8px; gap: 8px;
            }
            .room-sketch-wrap .sketch-kicker { display: none; }
            .room-sketch-wrap .room-sketch-hint { display: none; }
            .room-sketch-wrap .room-sketch-presets { display: flex; flex-wrap: nowrap; gap: 6px; }
            .room-sketch-wrap .room-opening-presets button { min-height: 72px; min-width: 72px; padding: 6px; }
            .room-sketch-wrap .room-sketch-tools > button { flex: 0 0 auto; white-space: nowrap; }
        }
        .room-sketch-tools {
            display: flex; flex-direction: column; gap: 6px; padding: 10px; background: #fff; border-right: 1px solid var(--line); overflow: auto; min-height: 0;
        }
        .room-sketch-tools .sketch-kicker {
            margin: 8px 0 2px; font-size: 10px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--muted);
        }
        .room-sketch-tools .sketch-kicker:first-child { margin-top: 0; }
        .room-sketch-tools button {
            border: 1px solid var(--line); background: #fff; border-radius: 10px; padding: 8px 10px; font-size: 12px; font-weight: 700; text-align: left; cursor: pointer;
        }
        .room-sketch-tools button.is-on { background: var(--ink); color: #fff; border-color: var(--ink); }
        .room-sketch-tools button.is-accent { background: var(--accent); color: #fff; border-color: var(--accent); }
        .room-sketch-presets {
            display: grid; grid-template-columns: 1fr 1fr; gap: 6px;
        }
        .room-sketch-presets button {
            display: flex; flex-direction: column; align-items: center; gap: 4px; padding: 8px 6px; text-align: center; font-size: 11px;
        }
        .room-sketch-presets svg { width: 36px; height: 28px; display: block; }
        .room-opening-presets { grid-template-columns: 1fr 1fr; gap: 8px; }
        .room-opening-presets button {
            border: 1px solid #d7dbe3; background: #fff; border-radius: 12px; padding: 10px 8px 8px;
            display: flex; flex-direction: column; align-items: center; gap: 6px; min-height: 92px;
        }
        .room-opening-presets button .op-ico {
            width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center;
            background: #eef6fb; color: #3a6f9a;
        }
        .room-opening-presets button[data-opening-preset^="door_"] .op-ico { background: #f7f0e4; color: #8a6a3b; }
        .room-opening-presets button .op-name { font-size: 12px; font-weight: 700; color: #243038; line-height: 1.1; }
        .room-opening-presets button .op-sub { font-size: 10px; font-weight: 600; color: #8a929a; line-height: 1.2; }
        .room-opening-presets button.is-on { background: #1c1c1c; border-color: #1c1c1c; }
        .room-opening-presets button.is-on .op-name,
        .room-opening-presets button.is-on .op-sub { color: #fff; }
        .room-opening-presets button.is-on .op-ico { background: rgba(255,255,255,.12); color: #fff; }
        .room-sketch-hint {
            margin: 4px 0 0; font-size: 10px; line-height: 1.35; color: var(--muted); font-weight: 600;
        }
        .room-sketch-stage { position: relative; min-height: 0; min-width: 0; height: 100%; background: #ebe8e2; overflow: hidden; }
        #room-sketch-selbar {
            position: absolute; z-index: 6; display: none; align-items: center; gap: 0;
            padding: 4px; border-radius: 12px; background: #fff; border: 1px solid #e4e2dc;
            box-shadow: 0 8px 24px rgba(28,28,28,.14); transform: translateZ(0);
        }
        #room-sketch-selbar.is-visible { display: flex; }
        #room-sketch-selbar button {
            width: 34px; height: 34px; border: 0; background: transparent; border-radius: 8px;
            display: grid; place-items: center; cursor: pointer; color: #2c2a27;
        }
        #room-sketch-selbar button:hover { background: #f3f1ec; }
        #room-sketch-selbar button[data-sketch-sel="delete"] { color: #b42318; }
        #room-sketch-selbar .sel-div { width: 1px; height: 18px; background: #e4e2dc; margin: 0 2px; }
        #room-sketch-selbar svg { width: 16px; height: 16px; display: block; }
        #room-sketch-canvas { width: 100%; height: 100%; min-height: 0; display: block; touch-action: none; cursor: crosshair; }
        #room-sketch-status {
            position: absolute; left: 10px; right: 10px; bottom: 10px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
            padding: 6px 10px; border-radius: 10px; background: rgba(255,252,248,.94); border: 1px solid var(--line); font-size: 11px; color: #5e5953;
        }
        #room-sketch-status .room-sketch-zoom { margin-left: auto; display: flex; align-items: center; gap: 6px; }
        #room-sketch-status .room-sketch-zoom button {
            width: 26px; height: 26px; border-radius: 8px; border: 1px solid var(--line); background: #fff; font-weight: 700; cursor: pointer;
        }
        .room-plan-backdrop { display: none; position: fixed; inset: 0; z-index: 24; background: #1c1c1c; }
        body.show-room-plan .room-plan-backdrop { display: block; }
        .room-handoff {
            display: none; gap: 8px; align-items: flex-end; flex-wrap: wrap; padding: 8px 12px;
            background: #fffcf8; border-top: 1px solid var(--line); font-size: 12px;
        }
        .room-handoff.is-open { display: flex; }
        .room-handoff label { display: flex; flex-direction: column; gap: 3px; font-weight: 600; color: #3d5247; }
        .room-handoff input[type="number"] { width: 72px; border: 1px solid #cfe0d6; border-radius: 8px; padding: 6px 8px; font-size: 12px; }
        .room-handoff button { border: 0; background: var(--accent); color: #fff; border-radius: 999px; padding: 7px 14px; font-weight: 700; cursor: pointer; font-size: 12px; }
        #openplan-frame, #openplan-studio-frame {
            width: 100%; height: 100%; border: 0; background: #1a2332; min-height: 0;
        }
        .room-plan-openplan { min-height: 0; height: 100%; }
        .room-plan-head-actions { margin-left: auto; display: flex; align-items: center; gap: 6px; flex-wrap: nowrap; }
        .room-plan-head-actions .btn-apply-plan {
            border: 0; background: var(--accent); color: #fff; border-radius: 999px; padding: 7px 14px;
            font-weight: 700; font-size: 12px; cursor: pointer; white-space: nowrap;
        }
        body.room-plan-openplan-mode #btn-room-redraw { display: none; }
        .room-plan-mode { margin-right: 0; }
        .room-plan-mode button { opacity: .72; }
        .room-plan-mode button.is-on { opacity: 1; }
        #studio-plan-first {
            display: none; position: fixed; left: 0; right: 0; bottom: 0; top: 0;
            z-index: 28; grid-template-rows: auto minmax(0, 1fr) auto; background: #eceae6;
            padding-top: max(40px, calc(40px + env(safe-area-inset-top)));
        }
        body.studio-plan-first #studio-plan-first { display: grid; }
        body.studio-plan-first #room-plan-panel {
            position: fixed; left: 0; right: 0; bottom: 0; z-index: 29;
            top: max(48px, calc(48px + env(safe-area-inset-top)));
            width: auto; max-width: none; height: auto; max-height: none;
            border-radius: 0; transform: none; box-shadow: none;
            display: grid !important;
            grid-template-rows: auto minmax(0, 1fr) auto;
            overflow: hidden;
            padding-top: 0;
        }
        body.studio-plan-first .workspace,
        body.studio-plan-first .studio-top { visibility: hidden; }
        body.studio-plan-first #studio-plan-first,
        body.studio-plan-first #studio-plan-first *,
        body.studio-plan-first #room-plan-panel,
        body.studio-plan-first #room-plan-panel * { visibility: visible; }
        body.studio-plan-first .room-sketch-body {
            min-height: 0;
            height: 100%;
            overflow: hidden;
        }
        body.studio-plan-first .room-sketch-tools {
            overflow: auto;
            max-height: 100%;
        }
        body.studio-plan-first .room-plan-backdrop { display: none !important; }
        body.studio-plan-first #btn-close-room-plan { display: none; }
        body.studio-plan-first .room-sketch-head > strong,
        body.studio-plan-first .room-sketch-head > .grow-h { display: none; }
        .plan-first-head {
            display: flex; flex-wrap: nowrap; align-items: center; gap: 8px; padding: 6px 10px;
            background: #fffcf8; border-bottom: 1px solid var(--line); font-size: 12px;
            min-height: 44px;
        }
        .plan-first-head > strong { font-size: 13px; white-space: nowrap; }
        .plan-first-head p { margin: 0; flex: 1; min-width: 0; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .plan-first-head .btn { padding: 6px 10px; font-size: 11px; }
        .plan-first-head .btn-primary { background: var(--accent); color: #fff; border: 0; border-radius: 999px; padding: 7px 14px; font-weight: 700; white-space: nowrap; }
        @media (max-width: 900px) {
            .plan-first-head { flex-wrap: wrap; }
            .plan-first-hint { display: none; }
        }
        .plan-first-frame { min-height: 0; position: relative; display: none; }
        body.studio-plan-first .plan-first-frame { display: block; }
        .plan-first-foot.is-collapsed { display: none; }
        .plan-first-hint { margin: 0; font-size: 11px; color: var(--muted); max-width: 58ch; line-height: 1.45; }
        body.walk-mode #viewport { cursor: crosshair; }
        body.walk-locked #viewport { cursor: none; }
        body.space-pan #viewport canvas { cursor: grab !important; }
        body.space-pan #viewport canvas:active { cursor: grabbing !important; }
        body.walk-mode #walk-hud { display: flex; }
        #walk-hud {
            display: none; position: absolute; left: 12px; top: 12px; z-index: 12;
            padding: 8px 12px; border-radius: 10px; background: rgba(20, 24, 22, .78); color: #fff;
            font-size: 11px; font-weight: 600; gap: 8px; align-items: center; pointer-events: none;
        }
        .room-plan-mode { display: flex; gap: 6px; }
        .room-plan-mode button { border: 1px solid var(--line); background: #fff; border-radius: 999px; padding: 6px 12px; font-size: 11px; font-weight: 700; }
        .room-plan-mode button.is-on { background: var(--ink); color: #fff; border-color: var(--ink); }
        .room-plan-openplan { display: grid !important; grid-template-columns: 1fr !important; grid-template-rows: 1fr; min-height: 0; height: 100%; }
        .room-plan-openplan.hidden, .room-sketch-wrap.hidden { display: none !important; }
        .toolbar-spacer { flex: 1; min-width: 12px; }
        @media (max-width: 900px) {
            header.bar .extra { display: none; }
        }
        .series { display: none; }
        footer.bar { border-top: 1px solid var(--line); justify-content: space-between; }
        .brand { display: flex; flex-direction: column; line-height: 1.1; text-decoration: none; color: inherit; }
        .brand strong { font-size: 13px; letter-spacing: .08em; }
        .brand span { font-size: 11px; color: var(--muted); }
        .name-input { border: 0; background: transparent; font-weight: 600; font-size: 14px; width: 160px; }
        .name-input:focus { outline: none; }
        .grow { flex: 1; }
        .btn, .btn-primary { border-radius: 8px; border: 1px solid var(--line); background: #fff; padding: 7px 10px; font-size: 12px; font-weight: 600; }
        .btn-primary { background: var(--accent); color: #fff; border-color: var(--accent); }
        .btn:disabled { opacity: .4; cursor: default; }
        .series button { border: 1px solid var(--line); background: #fff; border-radius: 999px; padding: 6px 12px; font-size: 12px; font-weight: 600; white-space: nowrap; }
        .series button.is-on { background: var(--ink); color: #fff; border-color: var(--ink); }
        .seg { display: flex; gap: 6px; flex-wrap: wrap; }
        .seg button { border: 1px solid var(--line); background: #fff; border-radius: 8px; padding: 7px 10px; font-size: 12px; font-weight: 600; }
        .seg button.is-on { background: var(--ink); color: #fff; border-color: var(--ink); }
        .seg-compact { gap: 4px; }
        .seg-compact button { padding: 5px 8px; font-size: 11px; border-radius: 7px; }
        .workspace {
            display: grid; min-height: 0; position: relative;
            grid-template-columns: minmax(0, 1fr);
            grid-template-rows: minmax(0, 1fr);
        }
        @media (min-width: 901px) {
            .workspace {
                grid-template-columns: minmax(0, 1fr) min(320px, 30vw);
            }
        }
        .stage { position: relative; min-width: 0; min-height: 0; background: #eceae6; grid-column: 1 / -1; grid-row: 1; }
        @media (min-width: 901px) {
            .stage { grid-column: 1; }
        }
        .stage-layout { position: absolute; inset: 0; min-height: 0; }
        aside { background: var(--panel); min-height: 0; display: flex; flex-direction: column; z-index: 6; }
        @media (min-width: 901px) {
            /* Modül ekle: sağdan açılan çekmece — sahne alanı genişler */
            aside.left {
                position: absolute; top: 0; right: 0; bottom: 0;
                width: min(320px, 30vw); grid-column: unset; grid-row: 1;
                border-left: 1px solid var(--line); border-right: none;
                box-shadow: -10px 0 28px rgba(28, 28, 28, .1);
                transform: translateX(105%);
                transition: transform .2s ease;
                z-index: 8;
            }
            aside.left.is-open,
            body.show-library aside.left { transform: none; }
            aside.left .sheet-close { display: block; margin: 10px 12px 0 auto; border: 1px solid var(--line); background: #fff; border-radius: 999px; padding: 6px 12px; font-size: 12px; font-weight: 700; }
            aside.right {
                grid-column: 2; grid-row: 1; border-left: 1px solid var(--line);
                transform: none; position: relative; width: auto;
                display: flex;
            }
            body.show-library aside.right { opacity: .35; pointer-events: none; }
            aside.right .sheet-close { display: none; }
        }
        .side-head { padding: 12px 14px 0; font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); }
        .side-head .side-sub { display: block; font-size: 11px; font-weight: 500; text-transform: none; color: var(--muted); margin-top: 4px; }
        .room-panel details { margin-bottom: 12px; border: 1px solid var(--line); border-radius: 10px; padding: 8px 10px; background: #fff; }
        .room-panel summary { cursor: pointer; font-weight: 700; font-size: 13px; }
        .library-search { padding: 10px 12px 0; }
        .library-search input { width: 100%; border: 1px solid var(--line); border-radius: 8px; padding: 8px 10px; background: #fff; font-size: 13px; }
        .side-body { padding: 12px; overflow: auto; min-height: 0; flex: 1; }
        .side-foot { border-top: 1px solid var(--line); padding: 12px; flex: 0 0 auto; }
        .side-foot .btn, .side-foot .btn-primary { width: 100%; }
        .side-foot-stack { display: flex; flex-direction: column; gap: 8px; }
        .btn-add-module {
            border-radius: 8px; border: 1px solid var(--accent); background: var(--accent); color: #fff;
            padding: 7px 12px; font-size: 12px; font-weight: 700; margin-right: 8px;
        }
        @media (max-width: 900px) {
            .btn-add-module, #open-library-desk { display: none !important; }
        }
        .catalog { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .card { display: flex; flex-direction: column; border: 1px solid var(--line); border-radius: 12px; background: #fff; padding: 6px; text-align: left; cursor: grab; touch-action: none; }
        .card-add { align-self: flex-start; margin-top: 6px; border: 1px solid var(--line); background: #fff; border-radius: 999px; padding: 4px 10px; font-size: 11px; font-weight: 700; cursor: pointer; }
        .card:hover { border-color: #c8c2b6; }
        .card img { width: 100%; height: 168px; object-fit: contain; background: #fff; border-radius: 8px; }
        .card-zoom { position: fixed; z-index: 40; width: min(440px, 48vw); padding: 10px; background: #fff; border: 1px solid var(--line); border-radius: 16px; box-shadow: 0 18px 48px rgba(28, 28, 28, .18); pointer-events: none; }
        .card-zoom img { display: block; width: 100%; height: min(72vh, 760px); object-fit: contain; background: #f7f5f2; border-radius: 12px; }
        .card-zoom strong { display: block; margin-top: 8px; font-size: 14px; }
        .card-zoom span { display: block; margin-top: 2px; color: var(--muted); font-size: 12px; }
        .catalog-switch { display: flex; gap: 6px; margin: 0 0 8px; }
        .catalog-switch button { flex: 1; border: 1px solid var(--line); background: #fff; border-radius: 8px; padding: 7px 6px; font-size: 12px; font-weight: 700; }
        .catalog-switch button.is-on { background: #149152; color: #fff; border-color: #149152; }
        .catalog-kicker { margin: 12px 0 6px; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); }
        .card.is-broken { outline: 2px solid #c0392b; }
        .card strong { display: block; margin-top: 6px; font-size: 12px; font-weight: 600; }
        .card span, .set-card span { color: var(--muted); font-size: 11px; }
        .sets { display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px; }
        .set-card { display: flex; align-items: center; gap: 10px; text-align: left; border: 1px solid var(--line); border-radius: 12px; background: #fff; padding: 8px; }
        .set-card img { width: 64px; height: 64px; object-fit: contain; background: #f7f6f4; border-radius: 8px; flex: none; }
        .set-card strong { display: block; font-size: 13px; }
        .parts { margin: 0 0 12px; padding-left: 18px; font-size: 12px; }
        .parts-panel { margin: 10px 0 14px; padding: 10px; border: 1px solid var(--line); border-radius: 10px; background: #fff; }
        .parts-panel h3 { margin: 0 0 8px; font-size: 13px; }
        .parts-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .parts-table th, .parts-table td { border-bottom: 1px solid var(--line); padding: 4px 3px; text-align: left; vertical-align: top; }
        .parts-table th { color: var(--muted); font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
        .insp-mod-head { margin: 0 0 8px; }
        .insp-mod-head h2 { margin: 0 0 4px; font-size: 15px; }
        .insp-mod-head .hint { margin: 0; font-size: 11px; line-height: 1.35; }
        .insp-mod-head .picked { margin: 0 0 4px; }
        .insp-tech { margin: 8px 0 0; border: 1px solid var(--line); border-radius: 10px; padding: 6px 10px; background: #fff; }
        .insp-tech summary { cursor: pointer; font-size: 11px; font-weight: 700; color: var(--muted); }
        .insp-tech p { margin: 6px 0 0; font-size: 11px; color: var(--muted); line-height: 1.4; }
        .insp-tabs {
            position: sticky; top: 0; z-index: 2; margin: 0 0 10px; padding: 6px 0 8px;
            background: var(--panel);
            flex-wrap: nowrap; overflow-x: auto; gap: 4px; scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }
        .insp-tabs::-webkit-scrollbar { display: none; }
        .insp-tabs button { flex: 0 0 auto; font-size: 11px; padding: 7px 10px; white-space: nowrap; }
        .insp-pane[hidden] { display: none !important; }
        .insp-pane { min-height: 80px; padding-bottom: 12px; }
        .opacity-field input[type="range"] { width: 100%; }
        .parts li { margin: 0 0 8px; }
        .parts li.is-on { background: #e5f6ec; border: 2px solid #149152; border-radius: 8px; padding: 6px 8px; list-style-position: inside; }
        #viewport { position: absolute; inset: 0; }
        #viewport canvas { display: block; }
        #stage-toolbar {
            position: absolute; top: 10px; left: 50%; transform: translateX(-50%);
            z-index: 5; display: flex; align-items: center; gap: 4px; flex-wrap: wrap; justify-content: center;
            max-width: calc(100% - 24px); padding: 4px; border-radius: 999px;
            background: rgba(255,252,248,.92); border: 1px solid var(--line); box-shadow: var(--shadow);
        }
        #stage-toolbar .seg { gap: 4px; }
        #stage-toolbar button { border: 0; background: transparent; border-radius: 999px; padding: 7px 12px; font-size: 12px; font-weight: 700; }
        #stage-toolbar button.is-on { background: var(--ink); color: #fff; }
        #stage-toolbar .tb-sep { width: 1px; height: 22px; background: var(--line); margin: 0 2px; }
        #view-bar { position: absolute; left: 12px; bottom: 12px; z-index: 4; display: flex; flex-wrap: wrap; gap: 4px; max-width: min(360px, calc(100% - 24px)); }
        #view-bar button { border: 1px solid var(--line); background: rgba(255,252,248,.94); border-radius: 999px; padding: 6px 10px; font-size: 11px; font-weight: 700; }
        body:not(.show-extra-views) #view-bar [data-view-extra] { display: none; }
        #plan-tools.inline { position: static; display: none; gap: 4px; }
        body.plan-2d #plan-tools.inline { display: flex; }
        #plan-board { position: absolute; inset: 0; z-index: 3; background: #f3f1ec; }
        #plan-board.hidden { display: none; }
        #plan-canvas { width: 100%; height: 100%; display: block; touch-action: none; }
        body.plan-2d #view-bar, body.plan-2d #selection-ui, body.plan-2d #height-handle { display: none; }
        #measure-layer { position: absolute; inset: 0; z-index: 2; pointer-events: none; overflow: hidden; }
        .measure { position: absolute; transform: translate(-50%, -50%); background: #1f6b4a; color: #fff; font-size: 11px; font-weight: 700; line-height: 1.2; padding: 2px 6px; border-radius: 999px; white-space: nowrap; }
        .btn.is-on { background: var(--ink); color: #fff; border-color: var(--ink); }
        #selection-ui, #height-handle, #quick-rail, #selection-bar { position: absolute; z-index: 9; }
        #selection-ui { display: none; }
        #selection-bar {
            left: 50%; bottom: 72px; transform: translateX(-50%);
            display: flex; align-items: center; gap: 5px; flex-wrap: nowrap; justify-content: center;
            max-width: min(360px, calc(100% - 160px)); padding: 5px 8px; border-radius: 999px;
            background: rgba(28, 28, 28, .9); box-shadow: var(--shadow); pointer-events: auto;
        }
        #selection-bar.hidden { display: none !important; }
        body.plan-2d #selection-bar { display: none !important; }
        #selection-bar-name {
            font-size: 11px; font-weight: 700; color: #fff; max-width: 140px; white-space: nowrap;
            overflow: hidden; text-overflow: ellipsis; margin-right: 4px;
        }
        #selection-bar button {
            border: 0; border-radius: 999px; padding: 7px 11px; font-size: 11px; font-weight: 700;
            background: rgba(255,255,255,.14); color: #fff;
        }
        #selection-bar button.is-on { background: var(--accent); }
        #selection-bar button.sb-danger { background: #9f2d2d; }
        @media (max-width: 900px) {
            /* stage-toolbar ~120px; selection bar onun üstünde — çakışmasın */
            #selection-bar { bottom: calc(168px + env(safe-area-inset-bottom)); max-width: calc(100% - 16px); }
            #selection-bar-name { display: none; }
        }
        #quick-rail {
            display: flex; flex-direction: column; gap: 4px; min-width: 44px; max-width: 52px;
            padding: 6px; border-radius: 12px; background: rgba(28,28,28,.88); box-shadow: var(--shadow);
            transform: translate(8px, -50%); pointer-events: auto;
        }
        #quick-rail.hidden { display: none !important; }
        #quick-rail button {
            border: 0; border-radius: 8px; padding: 8px 6px; font-size: 11px; font-weight: 700;
            background: rgba(255,255,255,.12); color: #fff; line-height: 1.1;
        }
        #quick-rail button.is-on { background: var(--accent); }
        #quick-rail button.qr-danger { background: #9f2d2d; }
        #quick-rail .qr-mini { display: flex; flex-wrap: wrap; gap: 3px; justify-content: center; }
        #quick-rail .qr-mini button { width: 18px; height: 18px; padding: 0; border-radius: 4px; border: 1px solid rgba(255,255,255,.35); }
        #hold-select { position: absolute; left: 50%; bottom: 18px; transform: translateX(-50%); z-index: 4; background: #1f6b4a; color: #fff; border-radius: 999px; padding: 8px 14px; font-size: 13px; font-weight: 600; pointer-events: none; }
        .picked { display: inline-block; margin: 0 0 6px; background: #149152; color: #fff; font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; padding: 3px 8px; border-radius: 999px; }
        #height-handle {
            width: 32px; height: 32px; border-radius: 50%; border: 1px solid #d5d0c6; background: #fff;
            transform: translate(-50%, -50%); font-size: 16px; box-shadow: var(--shadow);
        }
        .warn { position: absolute; left: 12px; right: 12px; bottom: 12px; background: #fff6f4; color: var(--danger); border: 1px solid #f0c8c4; border-radius: 10px; padding: 8px 10px; font-size: 12px; z-index: 2; }
        .field { margin-bottom: 12px; }
        .field label { display: block; font-size: 11px; color: var(--muted); margin-bottom: 4px; }
        .field-key { margin-bottom: 14px; padding: 10px; background: #fff; border: 1px solid var(--line); border-radius: 12px; }
        .field-key > label { font-size: 15px; font-weight: 700; color: var(--ink); margin-bottom: 8px; }
        .field input, .field select { width: 100%; border: 1px solid var(--line); border-radius: 8px; padding: 8px 10px; background: #fff; font-size: 13px; }
        .swatches { display: flex; flex-wrap: wrap; gap: 6px; }
        .swatch { width: 28px; height: 28px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px #d5d0c8; }
        .field-key .swatch { width: 36px; height: 36px; }
        .swatch.is-on { box-shadow: 0 0 0 2px var(--accent); }
        .decors { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; max-height: 168px; overflow: auto; }
        .decor-kicker { grid-column: 1 / -1; margin: 4px 0 0; font-size: 11px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--muted); }
        .decor { display: flex; gap: 6px; align-items: center; border: 1px solid var(--line); background: #fff; border-radius: 8px; padding: 4px; text-align: left; }
        .decor.is-on { border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }
        .decor img { width: 28px; height: 28px; object-fit: cover; border-radius: 4px; background: #eee; }
        .decor b { display: block; font-size: 10px; font-weight: 600; line-height: 1.2; }
        .decor span { display: block; font-size: 10px; color: var(--muted); }
        .hint { font-size: 12px; color: var(--muted); margin: 0 0 8px; }
        .stat { font-size: 12px; color: var(--muted); }
        .stat b { color: var(--ink); }
        h2 { margin: 0 0 8px; font-size: 16px; }
        .overlay { position: fixed; inset: 0; background: rgba(18, 24, 21, .55); z-index: 80; display: flex; align-items: center; justify-content: center; padding: 18px; }
        #config-modal .sheet { width: min(680px, 100%); }
        .sheet { width: min(520px, 100%); max-height: min(86vh, 720px); overflow: auto; background: var(--panel); border-radius: 16px; padding: 22px; box-shadow: var(--shadow); }
        .choice { display: block; width: 100%; text-align: left; border: 1px solid var(--line); background: #fff; border-radius: 12px; padding: 12px; margin-bottom: 8px; }
        .choice span { display: block; color: var(--muted); font-size: 12px; margin-top: 2px; }
        .hidden { display: none !important; }
        .toast { position: fixed; bottom: 64px; left: 50%; transform: translateX(-50%); background: var(--ink); color: #fff; padding: 10px 14px; border-radius: 999px; font-size: 12px; z-index: 50; }
        .check { display: flex; align-items: center; gap: 8px; margin: 0 0 12px; font-size: 13px; font-weight: 600; }
        .check input { width: auto; }
        .mobile-nav { display: none; }
        .sheet-close, #project-sheet { display: none; }
        @media (max-width: 900px) {
            .studio-top__actions .extra, .studio-top__actions .btn-quiet { display: none; }
            #stage-toolbar { top: auto; bottom: calc(120px + env(safe-area-inset-bottom)); left: 8px; right: 8px; transform: none; border-radius: 12px; }
            .workspace { display: flex; flex-direction: column; min-height: 0; grid-template-columns: 1fr; }
            .stage { position: relative; inset: auto; flex: 1 1 auto; min-height: 0; width: 100%; grid-column: 1; }
            aside.left, aside.right { grid-column: 1; }
            #viewport { min-height: 0; }
            aside.right {
                position: fixed; left: 0; right: 0; bottom: calc(72px + env(safe-area-inset-bottom));
                height: min(42dvh, 380px); z-index: 20; transform: translateY(120%);
                transition: transform .2s ease; border-radius: 16px 16px 0 0; box-shadow: var(--shadow);
            }
            aside.right.is-open { transform: none; }
            /* Modül ekle sağdan kayar — 3D alanı açık kalır */
            aside.left {
                position: fixed; top: 0; right: 0; left: auto;
                bottom: calc(72px + env(safe-area-inset-bottom));
                width: min(92vw, 380px); height: auto;
                max-height: calc(100dvh - 72px - env(safe-area-inset-bottom));
                z-index: 21; transform: translateX(110%);
                transition: transform .2s ease; border-radius: 16px 0 0 16px; box-shadow: var(--shadow);
            }
            aside.left.is-open,
            body.show-library aside.left { transform: none; }
            /* Modül ayar paneli: sekmeler + içerik görünsün */
            body.show-custom:not(.show-library) aside.right {
                transform: none;
                height: min(62dvh, 560px);
            }
            body.show-library:not(.show-custom) aside.right { transform: translateY(120%); }
            body:not(.show-library):not(.show-custom) aside.right { transform: translateY(120%); }
            aside .sheet-close { display: block; margin: 8px 12px 0 auto; border: 1px solid var(--line); background: #fff; border-radius: 999px; padding: 6px 12px; font-size: 12px; font-weight: 700; }
            #project-sheet {
                display: none; position: fixed; left: 0; right: 0; bottom: calc(72px + env(safe-area-inset-bottom));
                z-index: 20; background: var(--panel); border-radius: 16px 16px 0 0; box-shadow: var(--shadow); padding: 12px;
            }
            #project-sheet.is-open { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
            .mobile-nav { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 6px; width: 100%; }
            .mobile-nav .btn { padding: 8px 4px; font-size: 11px; }
            footer.bar { display: block; padding: 8px 8px calc(8px + env(safe-area-inset-bottom)); }
            footer.bar .stat, footer.bar #btn-quote { display: none; }
            #view-bar { top: auto; bottom: calc(58px + env(safe-area-inset-bottom)); left: 8px; right: 8px; max-width: none; flex-wrap: nowrap; overflow-x: auto; gap: 4px; scrollbar-width: none; -webkit-mask-image: linear-gradient(90deg, #000 calc(100% - 22px), transparent); mask-image: linear-gradient(90deg, #000 calc(100% - 22px), transparent); }
            #view-bar::-webkit-scrollbar { display: none; }
            #view-bar button { padding: 6px 8px; font-size: 11px; }
            .name-input { width: min(42vw, 180px); }
            /* Mobil alt menü yalnızca sheet’i açar; sekme geçişini CSS ile kilitleme (Donanım/İç boş kalıyordu). */
        }
    </style>
</head>
<body class="studio-layout">
<div id="studio">
    <div class="studio-top">
        <a href="{{ route('wardrobe.design') }}" class="brand" title="3D tasarla"><strong>Meemare</strong></a>
        <input id="project-name" class="name-input" maxlength="80" aria-label="Proje adı" value="Dolabım">
        <div class="studio-top__actions">
            <button type="button" class="btn" id="btn-undo" disabled title="Geri">↶</button>
            <button type="button" class="btn" id="btn-redo" disabled title="İleri">↷</button>
            <button type="button" class="btn btn-primary" id="btn-save">Kaydet</button>
            <a class="btn btn-quiet extra" id="btn-saved" href="{{ route('wardrobe.index') }}">Projeler</a>
            <button type="button" class="btn btn-quiet extra" id="btn-share">Paylaş</button>
            <button type="button" class="btn btn-quiet extra" id="btn-measures">Ölçü</button>
            <button type="button" class="btn btn-quiet is-on extra" id="btn-lights" title="Işık">💡</button>
            <button type="button" class="btn btn-quiet extra" id="btn-cabinet">Üretim</button>
            <button type="button" class="btn btn-quiet extra" id="btn-shot">Render</button>
        </div>
        <div class="studio-top__nav">@include('partials.meemare-apps-nav', ['activeApp' => 'tasarla', 'compact' => true])</div>
    </div>
    <script>
        document.addEventListener('click', (event) => {
            document.querySelectorAll('details.meemare-apps--compact[open]').forEach((node) => {
                if (!node.contains(event.target)) node.removeAttribute('open');
            });
        });
    </script>
    <div id="studio-plan-first" aria-label="Oda planı — dolap tasarla">
        <div class="plan-first-head">
            <strong>2. Oda planı</strong>
            <p class="plan-first-hint">/plan ile aynı editör: duvar, kapı ve pencere. Seçince kopyala · ayna · sil. Bitince yeşil düğme → modül dizimi.</p>
            <button type="button" class="btn" id="btn-plan-fullscreen" title="Tam ekran">Tam ekran</button>
            <button type="button" class="btn" id="btn-plan-toggle-handoff">Ölçü / JSON</button>
            <button type="button" class="btn" id="btn-plan-skip">Ölçüyü atla</button>
            <button type="button" class="btn-primary" id="btn-plan-to-3d">Modül dizimine geç →</button>
        </div>
        <div class="plan-first-frame" aria-hidden="true"></div>
        <form class="room-handoff plan-first-foot is-collapsed" id="studio-plan-first-handoff" style="border-top:1px solid var(--line)">
            <p style="margin:0;flex:1;font-size:12px;color:var(--muted)">Export veya ölçü girip geçebilirsiniz.</p>
            <label>JSON<input id="studio-plan-first-json" type="file" accept="application/json,.json"></label>
            <label>Ön<input name="onduvar" type="number" min="80" max="800" value="300"></label>
            <label>Sol<input name="solduvar" type="number" min="80" max="800" value="300"></label>
            <label>Sağ<input name="sagduvar" type="number" min="80" max="800" value="300"></label>
            <label>Tavan<input name="odaboy" type="number" min="160" max="320" value="320"></label>
            <button type="submit">3D odaya uygula</button>
        </form>
    </div>
    <div class="workspace">
        <aside class="left" id="library">
            <button type="button" class="sheet-close" id="close-library">Kapat</button>
            <div class="side-head">Modül ekle<span class="side-sub">Tıkla veya sahneye sürükle</span></div>
            <div class="library-search"><input id="module-search" type="search" placeholder="Ara…" autocomplete="off"></div>
            <div class="side-body" id="library-body"></div>
        </aside>
        <div id="card-zoom" class="card-zoom hidden" aria-hidden="true">
            <img alt="">
            <strong></strong>
            <span></span>
        </div>
        <div class="room-plan-backdrop" id="room-plan-backdrop" aria-hidden="true"></div>
        <section class="stage">
            <div id="room-plan-panel" aria-label="Oda planı" role="dialog">
                <div class="room-sketch-head">
                    <strong>Oda planı</strong>
                    <div class="room-plan-mode" id="room-plan-mode">
                        <button type="button" data-room-plan-mode="openplan" class="is-on">Plan</button>
                        <button type="button" data-room-plan-mode="sketch">Basit</button>
                    </div>
                    <div class="room-plan-head-actions">
                        <button type="button" class="btn" id="btn-room-measures" title="Ölçü / JSON">Ölçü</button>
                        <button type="button" class="btn" id="btn-room-redraw">Yeniden çiz</button>
                        <button type="button" class="btn-apply-plan" id="btn-apply-plan-header">3D’ye uygula</button>
                        <button type="button" class="btn" id="btn-close-room-plan">Kapat</button>
                    </div>
                </div>
                <div id="room-plan-openplan" class="room-plan-openplan room-sketch-body">
                    <iframe id="openplan-frame" title="OpenPlan" referrerpolicy="no-referrer-when-downgrade" allow="fullscreen; clipboard-read; clipboard-write"></iframe>
                </div>
                <div id="room-plan-sketch-wrap" class="room-sketch-wrap room-sketch-body hidden">
                    <aside class="room-sketch-tools" aria-label="Plan araçları">
                        <p class="sketch-kicker">Build</p>
                        <button type="button" data-sketch-tool="select" class="is-on">Seç (V)</button>
                        <button type="button" data-sketch-tool="wall">Duvar çiz (W)</button>
                        <button type="button" id="btn-sketch-finish-draw">Çizimi bitir (Esc)</button>
                        <p class="sketch-kicker">Oda şablonları</p>
                        <div class="room-sketch-presets">
                            <button type="button" id="btn-sketch-rect" title="Dikdörtgen oda">
                                <svg viewBox="0 0 40 30" aria-hidden="true"><rect x="6" y="5" width="28" height="20" fill="none" stroke="currentColor" stroke-width="2.5"/></svg>
                                Dikdörtgen
                            </button>
                            <button type="button" id="btn-sketch-l" title="L oda">
                                <svg viewBox="0 0 40 30" aria-hidden="true"><path d="M8 5h14v12h10v8H8z" fill="none" stroke="currentColor" stroke-width="2.5"/></svg>
                                L oda
                            </button>
                            <button type="button" id="btn-sketch-u" title="U oda">
                                <svg viewBox="0 0 40 30" aria-hidden="true"><path d="M8 5h8v14h8V5h8v20H8z" fill="none" stroke="currentColor" stroke-width="2.5"/></svg>
                                U oda
                            </button>
                            <button type="button" id="btn-sketch-t" title="T oda">
                                <svg viewBox="0 0 40 30" aria-hidden="true"><path d="M6 5h28v8H26v12H14V13H6z" fill="none" stroke="currentColor" stroke-width="2.5"/></svg>
                                T oda
                            </button>
                        </div>
                        <button type="button" id="btn-sketch-clear">Temizle</button>
                        <p class="sketch-kicker">Doors</p>
                        <div class="room-sketch-presets room-opening-presets">
                            <button type="button" data-opening-preset="door_single" class="is-on" title="Tek kanat 90 cm">
                                <span class="op-ico"><svg viewBox="0 0 40 30" aria-hidden="true"><path d="M8 6h4v18H8zM12 6v18" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 6a14 14 0 0 1 14 14" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></span>
                                <span class="op-name">Single</span><span class="op-sub">90cm swing</span>
                            </button>
                            <button type="button" data-opening-preset="door_double" title="Çift kanat 150 cm">
                                <span class="op-ico"><svg viewBox="0 0 40 30" aria-hidden="true"><path d="M6 6h4v18H6zM30 6h4v18h-4z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M10 6a10 10 0 0 1 10 10M30 6a10 10 0 0 0-10 10" fill="none" stroke="currentColor" stroke-width="1.5"/></svg></span>
                                <span class="op-name">Double</span><span class="op-sub">150cm swing</span>
                            </button>
                            <button type="button" data-opening-preset="door_sliding" title="Sürgülü 180 cm">
                                <span class="op-ico"><svg viewBox="0 0 40 30" aria-hidden="true"><rect x="6" y="8" width="16" height="14" fill="none" stroke="currentColor" stroke-width="2"/><rect x="18" y="10" width="14" height="10" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M10 15h18" stroke="currentColor" stroke-width="1.4"/></svg></span>
                                <span class="op-name">Sliding</span><span class="op-sub">180cm slide</span>
                            </button>
                            <button type="button" data-opening-preset="door_french" title="French 150 cm">
                                <span class="op-ico"><svg viewBox="0 0 40 30" aria-hidden="true"><path d="M8 6h8v18H8zM24 6h8v18h-8z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 12h8M8 18h8M24 12h8M24 18h8" stroke="currentColor" stroke-width="1.2"/></svg></span>
                                <span class="op-name">French</span><span class="op-sub">150cm glass</span>
                            </button>
                            <button type="button" data-opening-preset="door_pocket" title="Pocket 90 cm">
                                <span class="op-ico"><svg viewBox="0 0 40 30" aria-hidden="true"><path d="M6 6v18M12 6h4v18h-4z" fill="none" stroke="currentColor" stroke-width="2"/></svg></span>
                                <span class="op-name">Pocket</span><span class="op-sub">90cm recess</span>
                            </button>
                            <button type="button" data-opening-preset="door_bifold" title="Bifold 180 cm">
                                <span class="op-ico"><svg viewBox="0 0 40 30" aria-hidden="true"><path d="M6 6h5v18H6zM16 6h5v18h-5zM26 6h5v18h-5z" fill="none" stroke="currentColor" stroke-width="2"/></svg></span>
                                <span class="op-name">Bifold</span><span class="op-sub">180cm fold</span>
                            </button>
                            <button type="button" data-opening-preset="door_opening" title="Doorway 100 cm">
                                <span class="op-ico"><svg viewBox="0 0 40 30" aria-hidden="true"><path d="M12 6v18M28 6v18" fill="none" stroke="currentColor" stroke-width="2.5"/></svg></span>
                                <span class="op-name">Doorway</span><span class="op-sub">100cm open</span>
                            </button>
                            <button type="button" data-opening-preset="door_garage" title="Garage 240 cm">
                                <span class="op-ico"><svg viewBox="0 0 40 30" aria-hidden="true"><rect x="8" y="7" width="24" height="16" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 11h24M8 15h24M8 19h24" stroke="currentColor" stroke-width="1.3"/></svg></span>
                                <span class="op-name">Garage</span><span class="op-sub">240cm overhead</span>
                            </button>
                        </div>
                        <p class="sketch-kicker">Windows</p>
                        <div class="room-sketch-presets room-opening-presets">
                            <button type="button" data-opening-preset="window_std" title="Standard 120 cm">
                                <span class="op-ico"><svg viewBox="0 0 40 30" aria-hidden="true"><rect x="8" y="8" width="24" height="14" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 12h24M8 18h24M20 8v14" stroke="currentColor" stroke-width="1.4"/></svg></span>
                                <span class="op-name">Standard</span><span class="op-sub">120×120cm</span>
                            </button>
                            <button type="button" data-opening-preset="window_fixed" title="Fixed 100 cm">
                                <span class="op-ico"><svg viewBox="0 0 40 30" aria-hidden="true"><rect x="10" y="9" width="20" height="12" fill="none" stroke="currentColor" stroke-width="2"/><path d="M10 15h20M20 9v12" stroke="currentColor" stroke-width="1.4"/></svg></span>
                                <span class="op-name">Fixed</span><span class="op-sub">100×100cm</span>
                            </button>
                            <button type="button" data-opening-preset="window_casement" title="Casement 80 cm">
                                <span class="op-ico"><svg viewBox="0 0 40 30" aria-hidden="true"><rect x="12" y="6" width="16" height="18" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 15h16M20 6v18" stroke="currentColor" stroke-width="1.4"/></svg></span>
                                <span class="op-name">Casement</span><span class="op-sub">80×130cm</span>
                            </button>
                            <button type="button" data-opening-preset="window_sliding" title="Sliding 180 cm">
                                <span class="op-ico"><svg viewBox="0 0 40 30" aria-hidden="true"><rect x="4" y="9" width="32" height="12" fill="none" stroke="currentColor" stroke-width="2"/><path d="M4 15h32M20 9v12" stroke="currentColor" stroke-width="1.4"/></svg></span>
                                <span class="op-name">Sliding</span><span class="op-sub">180×120cm</span>
                            </button>
                            <button type="button" data-opening-preset="window_bay" title="Bay 200 cm">
                                <span class="op-ico"><svg viewBox="0 0 40 30" aria-hidden="true"><path d="M6 20h28M10 20l4-10h12l4 10" fill="none" stroke="currentColor" stroke-width="2"/><path d="M14 10v10M26 10v10M20 10v10" stroke="currentColor" stroke-width="1.2"/></svg></span>
                                <span class="op-name">Bay</span><span class="op-sub">200×120cm</span>
                            </button>
                        </div>
                        <p class="room-sketch-hint" id="room-sketch-tool-hint">Kapı/pencere kartı seçip duvara tıklayın. Seçimde üstte kopyala · ayna · sil.</p>
                    </aside>
                    <div class="room-sketch-stage">
                        <canvas id="room-sketch-canvas" aria-label="Oda planı tuvali"></canvas>
                        <div id="room-sketch-selbar" aria-hidden="true">
                            <button type="button" data-sketch-sel="copy" title="Kopyala">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="9" y="9" width="10" height="10" rx="1.5"/><path d="M5 15V5h10"/></svg>
                            </button>
                            <button type="button" data-sketch-sel="flip" title="Ayna">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 4v16"/><path d="M8 8H5l3 4-3 4h3M16 8h3l-3 4 3 4h-3" stroke-dasharray="0"/><path d="M12 4v16" stroke-dasharray="2 2"/></svg>
                            </button>
                            <span class="sel-div" aria-hidden="true"></span>
                            <button type="button" data-sketch-sel="delete" title="Sil">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M9 7V5h6v2M8 7l1 12h6l1-12"/></svg>
                            </button>
                        </div>
                        <div id="room-sketch-status"></div>
                    </div>
                </div>
                <form id="studio-plan-handoff" class="room-handoff" aria-label="Ölçü ayarı">
                    <label>JSON<input id="studio-plan-json" type="file" accept="application/json,.json"></label>
                    <label>Ön (cm)<input name="onduvar" type="number" min="80" max="800" value="300" required></label>
                    <label>Sol (cm)<input name="solduvar" type="number" min="80" max="800" value="300"></label>
                    <label>Sağ (cm)<input name="sagduvar" type="number" min="80" max="800" value="300"></label>
                    <label>Yükseklik<input name="odaboy" type="number" min="160" max="320" value="320"></label>
                    <button type="button" id="btn-sketch-apply" class="btn-mini" style="background:#1c1c1c">Çizimi uygula</button>
                    <button type="button" class="btn-mini" id="btn-apply-room-handoff" style="background:#444">Kayıtlı ölçü</button>
                    <button type="submit" class="is-accent" style="background:var(--accent);color:#fff;border:0;border-radius:999px;padding:7px 14px;font-weight:700;margin-left:auto">3D odaya uygula</button>
                </form>
            </div>
            <div class="stage-layout">
            <div id="viewport"></div>
            <div id="walk-hud" aria-hidden="true">WASD · Shift koş · Esc çıkış</div>
            <div id="plan-board" class="hidden">
                <canvas id="plan-canvas"></canvas>
            </div>
            <div id="stage-toolbar" aria-label="Sahne araçları">
                <div class="seg" id="plan-switch">
                    <button type="button" data-plan="3d" class="is-on">3D</button>
                    <button type="button" data-plan="2d">Yerleşim</button>
                </div>
                <span class="tb-sep" aria-hidden="true"></span>
                <div id="plan-tools" class="inline">
                    <button type="button" data-plan-tool="move" class="is-on">Taşı</button>
                    <button type="button" data-plan-tool="door">Kapı</button>
                    <button type="button" data-plan-tool="window">Pencere</button>
                </div>
                <span class="tb-sep" aria-hidden="true"></span>
                <button type="button" id="btn-modules" title="Modül kataloğu">Modül Ekle</button>
                <button type="button" id="btn-room-plan">Oda</button>
                <button type="button" id="btn-guides" class="is-on" title="Kılavuz çizgileri">Izgara</button>
                <button type="button" id="btn-demo-dressing" title="Örnek L giyinme">Örnek</button>
            </div>
            <div id="selection-bar" class="hidden" aria-label="Seçili modül">
                <span id="selection-bar-name"></span>
                <button type="button" data-sb="open" title="Kapağı aç/kapat">Aç</button>
                <button type="button" data-sb="clone">Klon</button>
                <button type="button" data-sb="delete" class="sb-danger">Sil</button>
            </div>
            <div id="view-bar" aria-label="Kamera">
                <button type="button" data-view="perspective" class="is-on">3D</button>
                <button type="button" data-view="walkthrough" id="btn-walk" title="Oda içi gezinti">Gezinti</button>
                <button type="button" id="cam-orbit-left" title="Sola dön">‹</button>
                <button type="button" id="cam-orbit-right" title="Sağa dön">›</button>
                <button type="button" data-view="top" title="Kuş bakışı">Üst</button>
                <button type="button" data-view="drawer" title="Çekmece yakın">Çekmece</button>
            </div>
            <div id="measure-layer" class="hidden"></div>
            <div id="selection-ui" class="hidden" aria-hidden="true"><span id="selection-name"></span></div>
            <div id="quick-rail" class="hidden" aria-label="Hızlı işlemler">
                <div class="qr-mini" id="qr-wood-sw"></div>
                <div class="qr-mini" id="qr-glass-sw"></div>
                <div class="qr-mini" id="qr-alum-sw"></div>
                <button type="button" data-qr="open" title="Kapak aç/kapa">Kapak</button>
                <button type="button" data-qr="clone" title="Klonla">Klon</button>
                <button type="button" data-qr="delete" class="qr-danger" title="Sil">Sil</button>
            </div>
            <button type="button" id="height-handle" class="hidden" aria-label="Yükseklik">↕</button>
            <div id="hold-select" class="hidden"></div>
            <div id="warn" class="warn hidden"></div>
            </div>
        </section>
        <aside class="right" id="inspector">
            <button type="button" class="sheet-close" id="close-inspector">Kapat</button>
            <div class="side-head"><span id="inspector-title">Oda</span><span class="side-sub" id="inspector-sub">Duvar ölçüsü · Modül Ekle ile katalog açılır</span></div>
            <div class="side-body" id="inspector-body"></div>
            <div class="side-foot side-foot-stack">
                <button type="button" class="btn-primary" id="open-library-desk">Modül Ekle</button>
                <button type="button" class="btn" id="btn-production">Üretim listesi</button>
            </div>
        </aside>
    </div>
    <footer class="bar">
        <div class="stat" id="summary"></div>
        <button type="button" class="btn btn-add-module" id="open-library-bar">Modül Ekle</button>
        <div class="mobile-nav">
            <button type="button" class="btn" id="open-library">Modül Ekle</button>
            <button type="button" class="btn" id="open-material">Malzeme</button>
            <button type="button" class="btn" id="open-doors">Kapaklar</button>
            <button type="button" class="btn" id="open-measures">Ölçüler</button>
            <button type="button" class="btn" id="open-project">Proje</button>
        </div>
        <button type="button" class="btn-primary" id="btn-quote">Fiyat teklifi al</button>
    </footer>
</div>
<div id="project-sheet">
    <button type="button" class="btn" id="sheet-new">Yeni proje</button>
    <button type="button" class="btn" id="sheet-undo">Geri</button>
    <button type="button" class="btn" id="sheet-redo">İleri</button>
    <button type="button" class="btn" id="sheet-saved">Kayıtlar</button>
    <button type="button" class="btn" id="sheet-share">Paylaş</button>
    <button type="button" class="btn" id="sheet-quote">Fiyat teklifi</button>
    <button type="button" class="btn" id="sheet-shot">Render al</button>
    <button type="button" class="sheet-close" id="close-project">Kapat</button>
</div>

<div id="customer-modal" class="overlay hidden">
    <form class="sheet" id="customer-form">
        <h2>Kayıtlı projeler</h2>
        <p class="hint">Daha önce kaydedilen projeler. Birine basınca açılır.</p>
        <div id="resume-list"></div>
        <div class="field" style="margin-top:10px"><p class="hint">Yalnızca bu hesaba ait projeler listelenir.</p></div>
        <button type="button" class="btn" id="resume-search">Listeyi yenile</button>
        <h2 style="margin-top:18px">Yeni proje</h2>
        <p class="hint">Dolabı tasarlamadan önce müşteri kayda geçer.</p>
        <div class="field"><label>Ad</label><input name="customerName" required maxlength="120" autocomplete="name"></div>
        <div class="field"><label>Proje adı</label><input name="projectName" required maxlength="80" value="Dolabım"></div>
        <div class="field"><label>Sol duvar genişliği (cm)</label><input name="leftWall" type="number" required min="80" max="800" value="300" inputmode="numeric"></div>
        <div class="field"><label>Ön duvar genişliği (cm)</label><input name="frontWall" type="number" required min="80" max="800" value="300" inputmode="numeric"></div>
        <div class="field"><label>Sağ duvar genişliği (cm)</label><input name="rightWall" type="number" required min="80" max="800" value="300" inputmode="numeric"></div>
        <div class="field"><label>Oda yüksekliği (cm)</label><input name="roomHeight" type="number" required min="160" max="320" value="320" inputmode="numeric"></div>
        <p class="hint">Standart oda yüksekliği 320 cm. 160 ile 320 arasında değiştirilebilir. Dolaplar duvara dizilir.</p>
        <div class="field"><label>Not</label><input name="note" maxlength="2000"></div>
        <div id="customer-msg" class="hint"></div>
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="submit" class="btn-primary">Dolabı tasarla</button>
        </div>
    </form>
</div>

<div id="saved-modal" class="overlay hidden">
    <div class="sheet">
        <h2>Kayıtlı projeler</h2>
        <div class="field"><label>Telefon</label><input id="saved-phone" maxlength="40" inputmode="tel"></div>
        <button type="button" class="btn" id="saved-search">Ara</button>
        <div id="saved-list" style="margin-top:8px"></div>
        <div style="display:flex;justify-content:flex-end;margin-top:8px">
            <button type="button" class="btn" id="saved-close">Kapat</button>
        </div>
    </div>
</div>

<div id="config-modal" class="overlay hidden">
    <div class="sheet">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px">
            <h2 id="config-title" style="margin:0">Yapılandır</h2>
            <button type="button" class="btn" id="config-close">Kapat</button>
        </div>
        <div id="config-body"></div>
    </div>
</div>

<div id="production-modal" class="overlay hidden">
    <div class="sheet">
        <h2>Üretim listesi</h2>
        <p class="hint" id="production-meta"></p>
        <ol class="parts" id="production"></ol>
        <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap">
            <button type="button" class="btn-primary" id="production-cabinet">Üretim dolabında aç</button>
            <button type="button" class="btn" id="production-close">Kapat</button>
        </div>
    </div>
</div>

<div id="quote-modal" class="overlay hidden">
    <form class="sheet" id="quote-form">
        <h2>Fiyat teklifi</h2>
        <p class="hint">Bu bir hazır fiyat değildir. Tasarım atölyeye gider, size dönüş yapılır.</p>
        <div class="field"><label>Ad</label><input name="name" required maxlength="120"></div>
        <div class="field"><label>Telefon</label><input name="phone" required maxlength="40"></div>
        <div class="field"><label>Not</label><input name="note" maxlength="2000"></div>
        <div id="quote-msg" class="hint"></div>
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn" id="quote-close">Vazgeç</button>
            <button type="submit" class="btn-primary">Gönder</button>
        </div>
    </form>
</div>
<div id="toast" class="toast hidden"></div>

<script type="importmap">
{
  "imports": {
    "three": "https://cdn.jsdelivr.net/npm/three@0.170.0/build/three.module.js",
    "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.170.0/examples/jsm/",
    "three/examples/jsm/": "https://cdn.jsdelivr.net/npm/three@0.170.0/examples/jsm/",
    "three-mesh-bvh": "https://cdn.jsdelivr.net/npm/three-mesh-bvh@0.7.8/src/index.js",
    "three-gpu-pathtracer": "https://cdn.jsdelivr.net/npm/three-gpu-pathtracer@0.0.23/src/index.js"
  }
}
</script>
<script>
    window.WARDROBE = {
        quoteUrl: @json($quoteUrl),
        materialsUrl: @json($materialsUrl),
        projectStoreUrl: @json($projectStoreUrl),
        projectIndexUrl: @json($projectIndexUrl),
        projectBase: @json(url('/dolap')),
        modelsUrl: @json(asset('models/wardrobe')),
        photosUrl: @json(asset('images/wardrobe')),
        csrf: @json(csrf_token()),
        openplanSrc: @json($openplanSrc ?? ''),
    };
</script>
<script src="{{ asset('js/meemare-plan-bridge.js') }}?v=6" defer></script>
<script src="{{ asset('js/openplan-import.js') }}?v=2" defer></script>
<script type="module" src="{{ asset('js/wardrobe-studio.js') }}?v=273"></script>
</body>
</html>
