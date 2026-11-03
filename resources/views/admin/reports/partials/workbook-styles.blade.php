@pushOnce('styles', 'report-workbook-styles')
<style>
    .reports-workspace { color:#24392e; }
    .report-page-heading { display:flex; align-items:center; justify-content:space-between; gap:var(--space-16); margin-bottom:var(--space-24); }
    .report-page-heading h1 { margin:0 0 var(--space-4); font-size:var(--text-heading-lg); font-weight:800; letter-spacing:-.03em; }
    .report-page-heading p { margin:0; color:#728078; font-size:var(--text-secondary); }
    .report-analytics-link { display:inline-flex; align-items:center; gap:var(--space-8); color:#536b5c; font-size:var(--text-secondary); font-weight:650; text-decoration:none; }
    .report-analytics-link svg { width:16px; height:16px; }
    .report-workbook { min-width:0; overflow:hidden; border:1px solid #d6e2da; border-radius:14px; background:#fff; box-shadow:0 4px 20px #173b2607; }
    .report-control-panel { margin:0; }
    .report-tab-strip { display:flex; flex-wrap:nowrap; gap:var(--space-4); overflow-x:auto; padding:var(--space-8) var(--space-12) 0; background:#e9efeb; border-bottom:1px solid #d6e2da; scrollbar-width:thin; }
    .report-tab { position:relative; flex:0 0 auto; display:inline-flex; align-items:center; gap:var(--space-8); min-height:44px; padding:var(--space-12) var(--space-16); margin:0; border:1px solid transparent; border-bottom:0; border-radius:10px 10px 0 0; color:#64756a; background:transparent; font-size:var(--text-secondary); font-weight:650; white-space:nowrap; cursor:pointer; transition:background-color .15s ease,color .15s ease; }
    .report-tab svg { width:16px; height:16px; flex-shrink:0; }
    .report-tab:hover { color:#164f32; background:#f3f7f4; }
    .report-tab.is-selected { color:#164f32; background:#fff; border-color:#d6e2da; font-weight:800; box-shadow:inset 0 3px #217547; }
    .report-tab:has(input:focus-visible) { outline:3px solid #8fbca0; outline-offset:-3px; }
    .report-sheet-toolbar { display:flex; align-items:center; justify-content:space-between; gap:var(--space-16); padding:var(--space-16) var(--space-24); border-bottom:1px solid #e3eae5; }
    .report-sheet-title { display:flex; align-items:center; gap:var(--space-12); min-width:0; }
    .report-sheet-title > svg { width:32px; height:32px; padding:var(--space-8); box-sizing:content-box; border:1px solid #dce8df; border-radius:10px; background:#f2f8f4; color:#217547; flex-shrink:0; }
    .report-sheet-title strong { display:block; margin-bottom:var(--space-4); font-size:var(--text-heading-sm); font-weight:800; }
    .report-sheet-title small { display:block; max-width:620px; color:#718178; font-size:var(--text-caption); line-height:var(--line-body); }
    .report-export-actions { display:flex; align-items:center; flex:0 0 auto; gap:var(--space-8); }
    .report-tool-button { display:inline-flex; align-items:center; justify-content:center; gap:var(--space-8); min-height:40px; padding:var(--space-8) var(--space-12); border:1px solid #d5dfd8; border-radius:7px; color:#415b4b; background:#fff; font-size:var(--text-secondary); font-weight:700; white-space:nowrap; }
    .report-tool-button svg { width:16px; height:16px; }
    .report-tool-button:hover { border-color:#8bb49a; background:#f3f8f5; }
    .report-tool-button.is-primary { border-color:#1c6841; background:#1c6841; color:#fff; }
    .report-tool-button.is-primary:hover { background:#154f32; }
    .report-tool-button:disabled { cursor:wait; }
    .report-filter-bar { display:flex; flex-wrap:wrap; align-items:flex-end; gap:var(--space-12); padding:var(--space-16) var(--space-24); background:#fbfcfb; border-bottom:1px solid #dce6df; }
    .report-filter { flex:1 1 155px; max-width:240px; min-width:0; }
    .report-filter.is-short { flex-basis:115px; max-width:145px; }
    .report-filter > label { display:block; margin-bottom:var(--space-4); color:#6b7b70; font-size:var(--text-caption); font-weight:750; }
    #reportForm :is(.form-select,.form-control) { min-height:40px; padding-block:var(--space-8); border:1px solid #d6e1d9; border-radius:6px; background-color:#fff; font-size:var(--text-secondary); color:#294333; }
    #reportForm :is(.form-select,.form-control):focus { border-color:#549770; box-shadow:0 0 0 3px #28774b14; }
    .report-reset { display:inline-flex; align-items:center; justify-content:center; gap:var(--space-4); min-height:40px; padding:var(--space-8); color:#708175; text-decoration:none; font-size:var(--text-caption); }
    .report-reset svg { width:14px; height:14px; }
    .report-fallback { padding:var(--space-12); }
    .report-preview-header { display:flex; align-items:center; justify-content:space-between; gap:var(--space-16); padding:var(--space-16) var(--space-24); }
    .report-preview-header h2 { margin:0 0 var(--space-4); color:#263c2f; font-size:var(--text-heading-sm); font-weight:800; }
    .report-preview-header p { margin:0; color:#7b897f; font-size:var(--text-caption); }
    .report-preview-header p > span { padding-inline:var(--space-8); color:#b1beb5; }
    .report-summary { display:flex; flex-direction:column; align-items:flex-end; gap:var(--space-4); flex-shrink:0; }
    .report-summary span { color:#7b8b80; font-size:var(--text-caption); }
    .report-summary strong { color:#216540; font-size:var(--text-heading-md); font-weight:800; font-variant-numeric:tabular-nums; }
    .report-sheet-grid { max-height:65dvh; overflow:auto; border-block:1px solid #d6e2da; scrollbar-width:thin; }
    .report-sheet-grid:focus-visible { outline:2px solid #74a487; outline-offset:-2px; }
    .report-table { width:100%; min-width:680px; border-collapse:separate; border-spacing:0; color:#34493c; font-size:var(--text-secondary); }
    .report-table th,.report-table td { min-width:128px; padding:var(--space-8) var(--space-12); border-right:1px solid #e2e8e3; border-bottom:1px solid #e2e8e3; vertical-align:top; line-height:var(--line-body); white-space:nowrap; overflow-wrap:normal; word-break:normal; }
    .report-table thead { position:sticky; top:0; z-index:3; }
    .report-table thead th { background:#eef3ef; color:#526959; font-size:var(--text-caption); font-weight:750; white-space:nowrap; }
    .report-table tbody tr:nth-child(even) td { background:#fbfdfb; }
    .report-table tbody tr:hover td { background:#edf6ef; }
    .report-table .report-row-number { position:sticky; left:0; z-index:1; width:42px; min-width:42px; padding-inline:var(--space-8); background:#f1f5f2; color:#8b9b90; text-align:center; font-size:var(--text-caption); font-weight:500; }
    .report-table thead .report-row-number { z-index:4; }
    .report-table .is-numeric { text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .report-table :is(th,td):is([data-column="tier"],[data-column="unit"]) { min-width:96px; }
    .report-table td:is([data-column="purpose"],[data-column="item"],[data-column="specifications"],[data-column="remarks"],[data-column="device"],[data-column="action"]) { width:280px; min-width:240px; max-width:380px; white-space:normal; overflow-wrap:anywhere; }
    .report-table td[data-column="specifications"] { width:360px; min-width:320px; }
    .report-table .report-empty { padding:var(--space-32) var(--space-16); text-align:center; color:#8c9c90; }
    .report-empty > svg { display:block; width:32px; height:32px; margin:0 auto var(--space-12); color:#a6b8ac; }
    .report-empty strong,.report-empty span { display:block; margin-block:var(--space-4); }
    .report-empty strong { color:#59715f; font-weight:700; }
    .report-sheet-footer { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:var(--space-12); padding:var(--space-12) var(--space-16); color:#849489; font-size:var(--text-caption); }
    .report-sheet-footer > span:first-child { color:#5d7665; font-weight:650; }
    .report-sheet-footer .pagination { margin:0; }
    .report-sheet-footer nav > .d-none { gap:var(--space-12); }
    .report-sheet-footer nav p { display:none; }
    .report-data-notes { padding:var(--space-12) var(--space-16); border-top:1px solid #e6ede8; color:#7a8d7f; font-size:var(--text-caption); }
    .report-data-notes summary { width:fit-content; cursor:pointer; }
    .report-data-notes p { margin:var(--space-8) 0 0; }
    .report-tool-button:focus-visible,.report-reset:focus-visible,.report-analytics-link:focus-visible { outline:3px solid #89b39a; outline-offset:2px; }
    .pdf-generation-modal { border-radius:18px!important; }
    .pdf-generation-modal .modal-body { padding:var(--space-32) var(--space-24); }
    .pdf-generation-spinner { width:56px; height:56px; margin:0 auto var(--space-16); border:5px solid #dcebe2; border-top-color:#187044; border-radius:50%; animation:pdf-generation-spin .72s linear infinite; }
    .pdf-generation-error { width:56px; height:56px; display:grid; place-items:center; margin:0 auto var(--space-16); border-radius:17px; background:#feeceb; color:#b42318; }
    .pdf-generation-error svg { width:27px; height:27px; }
    .pdf-generation-eyebrow { color:#218653; font-size:var(--text-caption); font-weight:800; letter-spacing:var(--tracking-label); text-transform:uppercase; }
    .pdf-generation-modal h5 { margin:var(--space-4) 0 var(--space-8); color:#1d3025; font-weight:800; }
    .pdf-generation-modal p { margin:0; color:#6d7d74; font-size:var(--text-body); line-height:var(--line-body); }
    @keyframes pdf-generation-spin { to { transform:rotate(360deg); } }
    @media(max-width:767px) { .report-page-heading { align-items:flex-start; margin-bottom:var(--space-16); }.report-analytics-link { font-size:var(--text-caption); }.report-analytics-link svg { display:none; }.report-sheet-toolbar { align-items:flex-start; flex-direction:column; padding:var(--space-16); }.report-sheet-title > svg { display:none; }.report-export-actions { width:100%; }.report-export-actions .report-tool-button { flex:1; }.report-filter-bar { padding:var(--space-12) var(--space-16); gap:var(--space-8); }.report-filter,.report-filter.is-short { flex:1 1 140px; max-width:none; }.report-preview-header { align-items:flex-start; padding:var(--space-16); }.report-summary { max-width:40%; }.report-summary span { text-align:right; }.report-tab { padding:var(--space-12); }.report-page-summary { display:none; } }
    @media(prefers-reduced-motion:reduce) { .report-tab { transition:none; }.pdf-generation-spinner { animation-duration:1.6s; } }
</style>
@endPushOnce
