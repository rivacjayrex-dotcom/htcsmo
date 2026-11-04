@pushOnce('styles', 'operational-analytics-styles')
<style>
    .analytics-page { max-width:1600px; margin:0 auto; color:#263e31; }
    .analytics-page :is(h1,h2,p,figure) { margin:0; }
    .analytics-page a,.analytics-page a:hover { color:inherit; text-decoration:none; }
    .analytics-page svg { width:18px; height:18px; flex-shrink:0; }
    .analytics-page :is(a,button,select,input,summary):focus-visible { outline:3px solid #77a78b; outline-offset:3px; }
    .analytics-header { display:flex; align-items:center; justify-content:space-between; gap:var(--space-24); margin-bottom:var(--space-24); }
    .analytics-eyebrow { display:block; color:#667b6d; font-size:var(--text-caption); font-weight:750; letter-spacing:var(--tracking-label); text-transform:uppercase; }
    .analytics-header h1 { margin:var(--space-8) 0; color:#203c2c; font-size:var(--text-heading-lg); font-weight:800; letter-spacing:-.03em; }
    .analytics-header p { color:#75867b; font-size:var(--text-secondary); }
    .analytics-button { display:inline-flex; align-items:center; justify-content:center; gap:var(--space-8); min-height:42px; padding:var(--space-8) var(--space-16); border:1px solid #dce6df; border-radius:9px; background:#fff; font-size:var(--text-secondary); font-weight:700; white-space:nowrap; }
    .analytics-button:hover { background:#f0f6f2; border-color:#a2c1ae; }
    .analytics-page .analytics-button.is-primary { background:#1b6540; border-color:#1b6540; color:#fff; }
    .analytics-page .analytics-button.is-primary:hover { background:#144f31; }
    .analytics-toolbar { display:flex; align-items:center; flex-wrap:wrap; gap:var(--space-16); padding:var(--space-16); border:1px solid #dce6df; border-radius:12px; background:#f5f8f6; }
    .analytics-toolbar-title { display:flex; align-items:center; gap:var(--space-8); color:#526d5c; font-size:var(--text-secondary); font-weight:700; }
    .analytics-period-form { display:flex; flex:1; align-items:flex-end; flex-wrap:wrap; gap:var(--space-8); }
    .analytics-period-form > div { flex:1 1 165px; max-width:240px; min-width:0; }
    .analytics-period-form label { display:block; margin-bottom:var(--space-4); font-size:var(--text-caption); color:#6a8072; font-weight:650; }
    .analytics-period-form :is(.form-select,.form-control) { min-height:42px; background-color:#fff; border-radius:8px; font-size:var(--text-secondary); }
    .analytics-period-form .is-reset { margin-left:auto; }
    .analytics-period-heading { display:flex; align-items:center; justify-content:space-between; gap:var(--space-16); margin:var(--space-24) 0 var(--space-16); }
    .analytics-period-heading h2 { font-size:var(--text-heading-sm); font-weight:750; }
    .analytics-period-heading p { margin-top:var(--space-4); color:#7a8b80; font-size:var(--text-caption); }
    .analytics-tag { display:inline-flex; align-items:center; flex-shrink:0; padding:var(--space-4) var(--space-8); border:1px solid #dfe9e2; border-radius:6px; background:#f5f9f6; color:#708777; font-size:var(--text-caption); font-weight:650; white-space:nowrap; }
    .analytics-summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:var(--space-12); margin-bottom:var(--space-24); }
    .analytics-summary-card { position:relative; display:flex; align-items:flex-start; gap:var(--space-12); min-width:0; padding:var(--space-24); border:1px solid #dce6df; border-radius:13px; background:#fff; }
    .analytics-summary-card > div { min-width:0; }
    .analytics-summary-card > div > span { display:block; color:#647b6c; font-size:var(--text-secondary); font-weight:650; }
    .analytics-summary-card strong { display:block; margin:var(--space-8) 0; color:#254e36; font-size:var(--text-heading-lg); line-height:var(--line-heading); font-weight:800; letter-spacing:-.03em; }
    .analytics-summary-card small { display:block; color:#86948b; font-size:var(--text-caption); line-height:var(--line-body); }
    .analytics-summary-icon,.analytics-panel-symbol { display:grid; place-items:center; flex:0 0 36px; width:36px; height:36px; border:1px solid #e0ece4; border-radius:10px; background:#f0f6f2; color:#4d8061; }
    .analytics-summary-card.is-featured { border-color:#a8cbb5; background:linear-gradient(125deg,#eef7f0,#fff); }
    .analytics-summary-card.is-featured:hover { border-color:#579a70; background:#eaf5ee; }
    .analytics-summary-arrow { position:absolute; top:var(--space-16); right:var(--space-16); color:#70927c; }
    .analytics-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); align-items:start; gap:var(--space-24); }
    .analytics-panel { min-width:0; overflow:hidden; border:1px solid #dce6df; border-radius:14px; background:#fff; }
    .analytics-panel-heading { display:flex; align-items:center; justify-content:space-between; gap:var(--space-12); padding:var(--space-24); border-bottom:1px solid #edf2ee; }
    .analytics-panel-heading h2 { margin-top:var(--space-4); color:#294433; font-size:var(--text-heading-sm); font-weight:800; }
    .analytics-panel-heading p { margin-top:var(--space-8); color:#819187; font-size:var(--text-caption); }
    .analytics-trend { display:flex; align-items:flex-end; gap:var(--space-8); overflow-x:auto; padding:var(--space-24) var(--space-16) var(--space-8); scrollbar-width:thin; }
    .analytics-trend-column { display:block; flex:1 0 64px; min-width:64px; padding:var(--space-8); border:1px solid transparent; border-radius:8px; text-align:center; }
    .analytics-trend-column:hover { border-color:#dce9e0; background:#f3f8f4; }
    .analytics-trend-column strong { color:#3a6449; font-size:var(--text-secondary); font-weight:750; }
    .analytics-bar-track { display:flex; align-items:flex-end; justify-content:center; height:140px; margin:var(--space-8) 0; border-bottom:1px solid #dce8df; }
    .analytics-bar-track > span { display:block; width:72%; max-width:44px; background:linear-gradient(#7fb795,#2a7146); border-radius:5px 5px 0 0; }
    .analytics-month-label { display:block; color:#85938a; font-size:var(--text-caption); white-space:nowrap; }
    .analytics-caption { padding:var(--space-16) var(--space-24); color:#86958b; font-size:var(--text-caption); line-height:var(--line-body); }
    .analytics-rows { display:grid; gap:var(--space-8); padding:var(--space-16); }
    .analytics-row { display:flex; align-items:center; gap:var(--space-12); min-width:0; min-height:66px; padding:var(--space-12) var(--space-16); border:1px solid #e5ece7; border-radius:9px; background:#fff; transition:background-color .16s ease,border-color .16s ease; }
    .analytics-row:hover { border-color:#aacbb6; background:#f2f8f4; }
    .analytics-row > svg { width:15px; height:15px; color:#a3b3a8; }
    .analytics-row:hover > svg { color:#39744d; }
    .analytics-rank { display:grid; place-items:center; flex:0 0 32px; width:32px; height:32px; border-radius:8px; background:#f2f6f3; color:#94a59a; font-size:var(--text-caption); font-weight:650; font-variant-numeric:tabular-nums; }
    .analytics-row-copy { display:block; flex:1; min-width:0; }
    .analytics-row-copy strong { display:block; color:#3c5846; font-size:var(--text-secondary); line-height:var(--line-body); font-weight:700; overflow-wrap:anywhere; }
    .analytics-row-copy small { display:block; margin-top:var(--space-4); color:#94a297; font-size:var(--text-caption); }
    .analytics-row-value { flex-shrink:0; text-align:right; }
    .analytics-row-value strong { display:block; color:#376748; font-size:var(--text-body); font-weight:800; font-variant-numeric:tabular-nums; }
    .analytics-row-value small { display:block; color:#94a297; font-size:var(--text-caption); }
    .analytics-stage-dot { flex:0 0 8px; width:8px; height:8px; border-radius:50%; background:#e1e8e3; }
    .analytics-stage-dot.is-active { background:#72a884; box-shadow:0 0 0 4px #f0f6f2; }
    .analytics-workflow .analytics-row { min-height:58px; }
    .analytics-workflow .analytics-row-value { display:grid; place-items:center; min-width:32px; min-height:28px; border-radius:6px; background:#f2f7f3; }
    .analytics-panel-footer { display:flex; align-items:center; justify-content:space-between; gap:var(--space-12); min-height:50px; padding:var(--space-16) var(--space-24); border-top:1px solid #e4ece6; background:#f7faf8; font-size:var(--text-secondary); font-weight:650; }
    .analytics-panel-footer:hover { background:#edf5ef; }
    .analytics-empty { padding:var(--space-24); color:#92a196; font-size:var(--text-secondary); line-height:var(--line-prose); text-align:center; }
    .analytics-month-data { border-top:1px solid #edf2ee; }
    .analytics-month-data summary { padding:var(--space-16) var(--space-24); color:#6a8171; font-size:var(--text-caption); font-weight:650; cursor:pointer; }
    .analytics-month-data .analytics-rows { padding-top:0; }
    .analytics-definitions { margin-top:var(--space-24); border:1px solid #e3ebe5; border-radius:10px; background:#f7faf8; color:#819287; font-size:var(--text-caption); }
    .analytics-definitions summary { padding:var(--space-16); cursor:pointer; }
    .analytics-definitions > div { display:grid; gap:var(--space-12); padding:0 var(--space-16) var(--space-16); line-height:var(--line-prose); }
    .analytics-page :is(.analytics-caption,.analytics-month-label,.analytics-summary-card small,.analytics-row-copy small,.analytics-row-value small,.analytics-panel-heading p,.analytics-period-heading p) { color:#6d8073; }
    @media(max-width:1199px) { .analytics-summary-card { padding:var(--space-16); }.analytics-summary-icon { display:none; }.analytics-grid { gap:var(--space-16); }.analytics-panel-heading { padding:var(--space-16); } }
    @media(max-width:767px) { .analytics-header { align-items:flex-start; flex-direction:column; gap:var(--space-16); }.analytics-header > a { width:100%; }.analytics-toolbar { gap:var(--space-12); }.analytics-toolbar-title { width:100%; }.analytics-period-form { flex-basis:100%; }.analytics-period-form > div { max-width:none; }.analytics-summary { grid-template-columns:1fr; gap:var(--space-8); }.analytics-summary-card { align-items:center; }.analytics-summary-card > div { flex:1; display:grid; grid-template-columns:1fr auto; column-gap:var(--space-16); }.analytics-summary-card > div > span { align-self:center; }.analytics-summary-card strong { grid-column:2; grid-row:1/3; align-self:center; margin:0; font-size:var(--text-heading-md); }.analytics-summary-card small { grid-column:1; margin-top:var(--space-4); }.analytics-summary-arrow { display:none; }.analytics-grid { grid-template-columns:1fr; }.analytics-period-heading { align-items:flex-start; }.analytics-period-heading .analytics-tag { display:none; }.analytics-panel-heading p { max-width:230px; }.analytics-panel-heading .analytics-tag { font-size:var(--text-caption); }.analytics-row { gap:var(--space-8); padding:var(--space-12); }.analytics-caption { padding:var(--space-12) var(--space-16); }.analytics-row-value small { max-width:70px; overflow-wrap:anywhere; }.analytics-rank { flex-basis:28px; width:28px; height:28px; } }
    @media(prefers-reduced-motion:reduce) { .analytics-row { transition:none; } }
    @media print { .analytics-header > a,.analytics-toolbar { display:none; }.analytics-grid { display:block; }.analytics-panel { break-inside:avoid; margin-bottom:var(--space-16); } }
</style>
@endPushOnce
