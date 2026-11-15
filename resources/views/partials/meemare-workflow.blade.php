@php
    $workflowStep = $workflowStep ?? '';
@endphp
<style>
    .meemare-workflow {
        display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
        padding: 6px 14px; background: #fffcf8;
        border-bottom: 1px solid #e6e2da; font-family: Montserrat, system-ui, sans-serif;
    }
    .meemare-workflow a, .meemare-workflow button {
        display: inline-flex; align-items: center; border: 1px solid #e6e2da; background: #fff;
        border-radius: 8px; padding: 7px 12px; font-size: 12px; font-weight: 600;
        color: #1c1c1c; text-decoration: none; cursor: pointer;
    }
    .meemare-workflow a.is-on, .meemare-workflow button.is-on {
        background: #1c1c1c; border-color: #1c1c1c; color: #fff;
    }
    .meemare-workflow .wf-sep { flex: 1; min-width: 8px; }
</style>
<nav class="meemare-workflow" aria-label="Tasarım adımları">
    <a href="{{ route('wardrobe.index') }}" @class(['is-on' => $workflowStep === 'projeler'])>Projeler</a>
    <a href="{{ route('wardrobe.design') }}" @class(['is-on' => $workflowStep === 'tasarla'])>3D dizim</a>
    <a href="{{ route('tools.plan') }}" @class(['is-on' => $workflowStep === 'plan'])>Oda planı</a>
    <a href="{{ route('tools.cabinet') }}" @class(['is-on' => $workflowStep === 'uretim'])>Üretim dolabı</a>
    @if($workflowStep === 'tasarla')
        <span class="wf-sep"></span>
        <button type="button" data-workspace="layout" class="is-on">Sahne</button>
        <button type="button" data-workspace="room">OpenPlan burada</button>
    @endif
</nav>