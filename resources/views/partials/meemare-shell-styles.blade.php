<style>
    .meemare-shell {
        --ink: #1c1c1c;
        --muted: #6d6a64;
        --line: #e6e2da;
        --bg: #f4f3f0;
        --panel: #fffcf8;
        --accent: #1f6b4a;
    }
    .meemare-tool-bar {
        display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
        padding: 10px 14px; background: var(--panel, #fffcf8);
        border-bottom: 1px solid var(--line, #e6e2da);
    }
    .meemare-tool-bar strong { font-size: 15px; }
    .meemare-tool-bar .lead {
        color: #5e6a64; font-size: 12px; flex: 1 1 200px; min-width: 0;
    }
    .meemare-nav-row {
        padding: 8px 14px; background: #f7f5f1;
        border-bottom: 1px solid var(--line, #e6e2da); overflow-x: auto;
    }
    .meemare-handoff-row {
        display: flex; gap: 8px; align-items: flex-end; flex-wrap: wrap;
        padding: 8px 14px; background: #fffcf8;
        border-bottom: 1px solid var(--line, #e6e2da); font-size: 12px;
    }
    .meemare-handoff-row label {
        display: flex; flex-direction: column; gap: 3px; font-weight: 600; color: #3d5247;
    }
    .meemare-handoff-row input[type="number"] {
        width: 76px; border: 1px solid #cfe0d6; border-radius: 8px; padding: 6px 8px; font-size: 12px;
    }
    .meemare-handoff-row button[type="submit"],
    .meemare-handoff-row .btn-apply {
        border: 0; background: var(--accent, #1f6b4a); color: #fff; border-radius: 8px;
        padding: 7px 14px; font-weight: 700; cursor: pointer; font-size: 12px;
    }
    .meemare-handoff-row p { margin: 0; color: #5e6a64; flex: 1 1 220px; font-size: 12px; }
</style>