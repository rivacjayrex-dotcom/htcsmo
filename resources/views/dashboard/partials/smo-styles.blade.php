@pushOnce('styles', 'page-styles:dashboard/partials/smo-styles.blade.php')
<style>
    .dashboard-page.smo-dashboard { display:grid; grid-template-columns:minmax(0,1fr); gap:var(--space-24); }
    .smo-dashboard .dashboard-kicker { display:block; font-weight:700; text-transform:uppercase; }
    .smo-workspace-scroll { min-width:0; }
    .smo-dashboard .smo-workspace { display:grid; grid-template-columns:minmax(0,1fr) 300px; grid-template-rows:auto auto 1fr; align-items:start; gap:var(--space-24); min-height:calc(100dvh - 136px); }
    .smo-dashboard .smo-workspace > .dashboard-hero { grid-column:1; grid-row:1; }
    .smo-card-tabs { display:grid; grid-column:1; grid-row:2; grid-template-columns:repeat(3,minmax(0,1fr)); gap:var(--space-12); }
    .smo-card-tab { --tab-color:#256044; --tab-soft:#eaf4ee; position:relative; display:flex; min-width:0; flex-direction:column; padding:var(--space-24); border:1px solid #dce6df; border-radius:14px; background:#fff; color:#294234; text-decoration:none; transition:border-color .15s,box-shadow .15s,background .15s; }
    .smo-card-tab.deadlines { --tab-color:#a04a27; --tab-soft:#fff1e8; }
    .smo-card-tab.inventory { --tab-color:#9a6b13; --tab-soft:#fff6df; }
    .smo-card-tab:hover { border-color:var(--tab-color); background:var(--tab-soft); color:#294234; }
    .smo-card-tab[aria-selected="true"] { border-color:var(--tab-color); background:var(--tab-soft); box-shadow:inset 0 -4px var(--tab-color),0 4px 12px #163b2308; }
    .smo-tab-top { display:flex; align-items:center; justify-content:space-between; gap:var(--space-8); margin-bottom:var(--space-16); }
    .smo-tab-icon { display:grid; place-items:center; width:40px; height:40px; border-radius:11px; background:var(--tab-soft); color:var(--tab-color); }
    .smo-card-tab[aria-selected="true"] .smo-tab-icon { background:#fff; }
    .smo-tab-icon svg { width:20px; height:20px; }
    .smo-tab-top strong { color:var(--tab-color); font-size:var(--text-heading-lg); font-weight:800; font-variant-numeric:tabular-nums; }
    .smo-tab-label { font-size:var(--text-body); font-weight:800; line-height:1.4; }
    .smo-tab-description { margin-top:var(--space-8); color:#687b6e; font-size:var(--text-secondary); line-height:1.5; }
    .smo-tab-hint { display:flex; align-items:center; justify-content:space-between; gap:var(--space-8); margin-top:auto; padding-top:var(--space-16); color:var(--tab-color); font-size:var(--text-caption); font-weight:750; }
    .smo-tab-hint svg { width:15px; height:15px; }
    .smo-tab-content { min-width:0; grid-column:1; grid-row:3; }
    .smo-tab-content > [role="tabpanel"][hidden] { display:none !important; }
    .smo-tab-content > [role="tabpanel"] { min-width:0; }
    .smo-dashboard .queue-panel { overflow:hidden; }
    .smo-dashboard .dashboard-table { min-width:0; table-layout:fixed; }
    .smo-dashboard .dashboard-table :is(td,th) { overflow-wrap:anywhere; }
    .smo-dashboard .dashboard-table .status-pill { max-width:100%; white-space:normal; }
    .smo-dashboard .dashboard-table th:first-child { width:24%; }
    .smo-dashboard .dashboard-table th:nth-child(2) { width:24%; }
    .smo-dashboard .dashboard-table th:nth-child(3) { width:22%; }
    .smo-dashboard .dashboard-table th:last-child { width:52px; }
    .smo-workspace-summary { display:flex; justify-content:space-between; flex-wrap:wrap; gap:var(--space-12); padding:var(--space-16) var(--space-4); color:#6b7e71; font-size:var(--text-secondary); }
    .smo-workspace-summary b { color:#315d43; }
    .smo-dashboard .activity-panel { grid-column:2; grid-row:1/4; position:sticky; top:88px; display:flex; flex-direction:column; width:100%; height:calc(100dvh - 136px); max-height:none; min-height:0; overflow:hidden; border-color:#dce6df; border-radius:14px; }
    .smo-dashboard .activity-panel .panel-heading { flex-shrink:0; }
    .smo-dashboard .activity-panel .activity-list { flex:1; min-height:0; overflow-y:auto; scrollbar-width:thin; }
    .smo-dashboard .activity-panel .activity-footer { flex-shrink:0; }
    .smo-dashboard .activity-panel .panel-heading { background:#f5f8f6; border-color:#e3ebe6; }
    .smo-dashboard .activity-panel .panel-heading h3 { margin-top:var(--space-4); }
    .smo-dashboard .activity-panel .panel-eyebrow { color:#6b7d70; font-size:var(--text-caption); }
    .smo-dashboard .activity-item { position:relative; gap:var(--space-12); padding:var(--space-16); }
    .smo-dashboard .activity-item.is-unread { background:#f4faf6; }
    .smo-dashboard .activity-item:hover { background:#eaf3ed; }
    .smo-dashboard .activity-item h4 { padding-right:var(--space-8); }
    .smo-dashboard .activity-item form { position:absolute; inset:0; margin:0; }
    .smo-notification-action { position:absolute; inset:0; width:100%; height:100%; padding:0; border:0; background:transparent; cursor:pointer; border-radius:inherit; }
    .smo-dashboard .smo-notification-action:focus-visible { outline-offset:-3px; }
    .smo-unread-dot { position:absolute; top:20px; right:12px; width:6px; height:6px; border-radius:50%; background:#26774a; pointer-events:none; }
    .smo-dashboard .activity-panel .activity-footer { color:#286142; background:#edf5ef; border-color:#cbded1; }
    .smo-tab-content .priority-panel { overflow:hidden; border-radius:14px; }
    .smo-tab-content .priority-heading { grid-template-columns:40px minmax(0,1fr) auto; align-items:center; }
    .smo-tab-content .priority-heading > a { grid-column:auto; }
    .smo-tab-content .priority-row { grid-template-columns:auto minmax(0,1fr) auto; padding:var(--space-16); }
    .smo-tab-content .priority-value { grid-column:auto; }
    .smo-tab-content .approval-deadline-watch { margin-top:var(--space-24); }
    /* Keep named grid areas consistent with the number of columns to prevent overlap. */
    .smo-dashboard .approval-deadline-item { grid-template-columns:auto minmax(0,1fr) minmax(0,1fr); grid-template-areas:"state request owner" ". age action"; gap:var(--space-12); }
    .smo-dashboard .approval-deadline-action { justify-content:flex-start; flex-wrap:wrap; }
    .smo-dashboard .approval-deadline-details { grid-template-columns:repeat(2,minmax(0,1fr)); }
    @media(max-width:1199px) {
        .smo-workspace-scroll { overflow-x:auto; }
        .smo-dashboard .smo-workspace { grid-template-columns:minmax(360px,1fr) 240px; gap:var(--space-16); }
        .smo-card-tab { padding:var(--space-12); }
        .smo-tab-content .priority-heading { grid-template-columns:34px minmax(0,1fr); }
        .smo-tab-content .priority-heading > a { grid-column:1/-1; }
        .smo-dashboard .approval-deadline-item { grid-template-columns:repeat(2,minmax(0,1fr)); grid-template-areas:"state state" "request owner" "age age" "action action"; }
    }
    @media(max-width:767px) {
        .dashboard-page.smo-dashboard { gap:var(--space-16); }
        .smo-dashboard .activity-panel { height:calc(100dvh - 104px); }
        .dashboard-page.smo-dashboard .dashboard-hero-actions { grid-template-columns:minmax(0,1fr); }
        .smo-dashboard .hero-btn { width:100%; }
        .smo-card-tabs { gap:var(--space-8); }
        .smo-card-tab { padding:var(--space-12) var(--space-8); border-radius:10px; }
        .smo-tab-top { flex-direction:column; align-items:flex-start; gap:var(--space-8); margin-bottom:var(--space-8); }
        .smo-tab-icon { width:32px; height:32px; }
        .smo-tab-top strong { font-size:var(--text-heading-md); }
        .smo-tab-label { font-size:var(--text-secondary); }
        .smo-tab-description,.smo-tab-hint { display:none; }
        .smo-tab-content .priority-row { grid-template-columns:minmax(0,1fr) auto; gap:var(--space-8); }
        .smo-tab-content .priority-state { grid-column:1/-1; width:fit-content; }
        .smo-dashboard .responsive-dashboard-table tbody tr { margin:var(--space-12); border:1px solid #e1e9e4; overflow:hidden; }
        .smo-dashboard .approval-deadline-details { grid-template-columns:minmax(0,1fr); }
    }
    @media(max-width:400px) {
        .smo-dashboard .approval-deadline-item { grid-template-columns:minmax(0,1fr); grid-template-areas:"state" "request" "owner" "age" "action"; }
    }
    @media(prefers-reduced-motion:reduce) { .smo-card-tab { transition:none; } }
    @media print { .smo-dashboard .activity-panel,.smo-card-tabs { display:none; }.smo-dashboard .smo-workspace { display:block; } }
</style>
@endPushOnce
