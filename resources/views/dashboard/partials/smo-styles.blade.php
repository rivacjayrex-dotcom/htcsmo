@pushOnce('styles', 'page-styles:dashboard/partials/smo-styles.blade.php')
<style>
    .dashboard-page.smo-dashboard { display:grid; grid-template-columns:minmax(0,1fr); gap:var(--space-24); }
    .smo-dashboard .dashboard-kicker { display:block; font-weight:700; text-transform:uppercase; }
    .smo-workspace-scroll { min-width:0; }
    .smo-dashboard .smo-workspace { display:grid; grid-template-columns:minmax(0,1fr) 300px; grid-template-rows:auto 1fr; align-items:start; gap:var(--space-24); min-height:calc(100dvh - 136px); }
    .smo-dashboard .smo-workspace > .dashboard-hero { grid-column:1; grid-row:1; }
    .smo-tab-workbook { grid-column:1; grid-row:2; min-width:0; overflow:hidden; border:1px solid #dce6df; border-radius:12px; background:#fff; }
    .smo-card-tabs { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:var(--space-4); padding:var(--space-8) var(--space-8) 0; border-bottom:1px solid #dce6df; background:#f0f5f2; }
    .smo-card-tab { --tab-color:#256044; --tab-soft:#eaf4ee; position:relative; display:grid; grid-template-columns:28px minmax(0,1fr) auto; align-items:center; gap:var(--space-8); min-width:0; min-height:56px; padding:var(--space-12); border:1px solid transparent; border-bottom:0; border-radius:8px 8px 0 0; background:transparent; color:#63796b; text-decoration:none; transition:border-color .15s,background-color .15s,color .15s; }
    .smo-card-tab.deadlines { --tab-color:#a04a27; --tab-soft:#fff1e8; }
    .smo-card-tab.inventory { --tab-color:#9a6b13; --tab-soft:#fff6df; }
    .smo-card-tab:hover { border-color:var(--tab-color); background:var(--tab-soft); color:#294234; }
    .smo-card-tab[aria-selected="true"] { z-index:1; border-color:#dce6df; background:#fff; color:#294234; box-shadow:inset 0 2px var(--tab-color); }
    .smo-card-tab[aria-selected="true"]::after { content:''; position:absolute; left:0; right:0; bottom:-1px; height:2px; background:#fff; }
    .smo-tab-icon { display:grid; place-items:center; width:28px; height:28px; border-radius:7px; background:var(--tab-soft); color:var(--tab-color); }
    .smo-card-tab[aria-selected="true"] .smo-tab-icon { background:#fff; }
    .smo-tab-icon svg { width:16px; height:16px; }
    .smo-tab-count { display:grid; place-items:center; min-width:24px; padding:2px var(--space-4); border-radius:6px; background:var(--tab-soft); color:var(--tab-color); font-size:var(--text-secondary); font-weight:800; font-variant-numeric:tabular-nums; }
    .smo-tab-label { font-size:var(--text-secondary); font-weight:750; line-height:1.4; }
    .smo-tab-content { min-width:0; }
    .smo-tab-content > [role="tabpanel"][hidden] { display:none !important; }
    .smo-tab-content > [role="tabpanel"] { min-width:0; }
    .smo-dashboard .queue-panel { overflow:hidden; }
    .smo-dashboard .smo-tab-content > [role="tabpanel"] > :is(.dashboard-panel,.priority-panel) { margin:0; border:0; border-radius:0; box-shadow:none; }
    .smo-dashboard .smo-tab-content .panel-heading { gap:var(--space-8); padding:var(--space-12) var(--space-16); }
    .smo-dashboard .smo-tab-content .panel-heading h3 { font-size:var(--text-heading-sm); }
    .smo-dashboard .smo-tab-content .panel-heading p { margin-top:var(--space-4); font-size:var(--text-caption); }
    .smo-dashboard .smo-tab-content .dashboard-table tbody td { padding:var(--space-12); }
    .smo-dashboard .dashboard-table { min-width:0; table-layout:fixed; }
    .smo-dashboard .dashboard-table :is(td,th) { overflow-wrap:anywhere; }
    .smo-dashboard .dashboard-table .status-pill { max-width:100%; white-space:normal; }
    .smo-dashboard .dashboard-table th:first-child { width:24%; }
    .smo-dashboard .dashboard-table th:nth-child(2) { width:24%; }
    .smo-dashboard .dashboard-table th:nth-child(3) { width:22%; }
    .smo-dashboard .dashboard-table th:last-child { width:52px; }
    .smo-workspace-summary { display:flex; justify-content:space-between; flex-wrap:wrap; gap:var(--space-12); padding:var(--space-12) var(--space-16); border-top:1px solid #e5ece7; background:#fafcfb; color:#6b7e71; font-size:var(--text-caption); }
    .smo-workspace-summary b { color:#315d43; }
    .smo-dashboard .activity-panel { grid-column:2; grid-row:1/3; position:sticky; top:88px; display:flex; flex-direction:column; width:100%; height:calc(100dvh - 136px); max-height:none; min-height:0; overflow:hidden; border-color:#dce6df; border-radius:14px; }
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
    .smo-tab-content .approval-deadline-watch { margin:0; border:0; border-top:1px solid #e7d8b4; border-radius:0; box-shadow:none; }
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
        .smo-card-tab { grid-template-columns:24px auto; gap:var(--space-4); padding:var(--space-8); }
        .smo-tab-icon { width:24px; height:24px; }
        .smo-tab-count { justify-self:end; }
        .smo-tab-label { grid-column:1/-1; grid-row:2; font-size:var(--text-caption); }
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
