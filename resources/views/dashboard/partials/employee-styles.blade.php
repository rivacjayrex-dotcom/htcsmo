@pushOnce('styles', 'page-styles:dashboard/partials/employee-styles.blade.php')
<style>
    .employee-dashboard { --employee-border:#e0e8e3; --employee-muted:#607469; display:grid; grid-template-columns:minmax(0,1fr) clamp(250px,23vw,300px); gap:1.15rem; align-items:start; }
    .dashboard-page.employee-dashboard .dashboard-hero { grid-column:1; grid-row:1; min-width:0; min-height:0; margin:0; padding:1.5rem 1.7rem; gap:1.5rem; border:1px solid #225e43; border-radius:16px; background:linear-gradient(115deg,#123d2c,#1f5c41); color:#fff; box-shadow:0 6px 18px #163b2310; }
    .dashboard-page.employee-dashboard .dashboard-kicker { color:#bde1c9; font-size:.7rem; letter-spacing:.12em; }
    .dashboard-page.employee-dashboard .dashboard-hero h2 { margin:.35rem 0 .5rem; color:#fff; font-size:clamp(1.5rem,2.5vw,2rem); letter-spacing:-.035em; }
    .dashboard-page.employee-dashboard .dashboard-hero-copy > p { max-width:560px; color:#e1eee5; font-size:.88rem; line-height:1.65; }
    .dashboard-page.employee-dashboard .dashboard-hero-meta { gap:.65rem 1.2rem; margin-top:.85rem; }
    .dashboard-page.employee-dashboard .dashboard-hero-meta > span { color:#cee2d5; font-size:.77rem; }
    .dashboard-page.employee-dashboard .dashboard-hero-meta svg { width:14px; height:14px; color:#a8cfb7; }
    .dashboard-page.employee-dashboard .dashboard-hero-actions { min-width:174px; gap:.5rem; }
    .dashboard-page.employee-dashboard .hero-btn { min-height:38px; padding:.6rem .85rem; border-radius:8px; font-size:.8rem; }
    .dashboard-page.employee-dashboard .hero-btn.primary { border-color:#fff; background:#fff; color:#174c33; box-shadow:0 2px 5px #08251815; }
    .dashboard-page.employee-dashboard .hero-btn.primary:hover { border-color:#e4f1e8; background:#e4f1e8; color:#123d2c; }
    .dashboard-page.employee-dashboard .hero-btn.secondary { border-color:#ffffff42; background:#ffffff0b; color:#f0f7f2; }
    .dashboard-page.employee-dashboard .hero-btn.secondary:hover { border-color:#ffffff70; background:#ffffff18; color:#fff; }

    .employee-dashboard .personal-summary { grid-column:1; grid-row:2; min-width:0; gap:1rem; margin:0; }
    .employee-dashboard .personal-summary > a { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:.25rem .75rem; padding:1rem 1.2rem; border-top:3px solid #85aa93; border-radius:12px; background:linear-gradient(110deg,#f1f7f3,#fff 85%); box-shadow:0 2px 8px #163b2304; }
    .employee-dashboard .personal-summary > .personal-summary-clarification { border-top-color:#c9aa6b; background:linear-gradient(110deg,#fff8eb,#fff 85%); }
    .employee-dashboard .personal-summary > .personal-summary-pickup { border-top-color:#74aaa2; background:linear-gradient(110deg,#edf7f5,#fff 85%); }
    .employee-dashboard .personal-summary-top { grid-column:1/-1; font-size:.7rem; color:#617268; }
    .employee-dashboard .personal-summary-icon { width:30px; height:30px; border-radius:8px; }
    .employee-dashboard .personal-summary-icon svg { width:16px; height:16px; }
    .employee-dashboard .personal-summary strong { grid-column:2; grid-row:2/4; margin:0; padding-left:.5rem; font-size:2.1rem; }
    .employee-dashboard .personal-summary h3 { grid-column:1; margin-top:.25rem; font-size:.96rem; }
    .employee-dashboard .personal-summary p { grid-column:1; margin-top:0; font-size:.76rem; }
    .employee-dashboard .personal-workspace,.employee-dashboard .personal-columns { display:contents; }
    .employee-dashboard .personal-main-stack { grid-column:1; grid-row:3; min-width:0; }
    .employee-dashboard .personal-panel { margin-bottom:1.15rem; border-radius:12px; box-shadow:0 2px 8px #163b2303; }
    .employee-dashboard .personal-panel > header { padding:1rem 1.2rem; gap:.75rem; }
    .employee-dashboard .personal-attention-panel > header { background:#fffcf5; border-bottom-color:#eee6d6; }
    .employee-dashboard .personal-panel h2 { font-size:1.06rem; }
    .employee-dashboard .personal-eyebrow { color:#5a7062; font-size:.65rem; letter-spacing:.1em; margin-bottom:.3rem; }
    .employee-dashboard .personal-panel header p { max-width:650px; margin:.4rem 0 0; color:var(--employee-muted); font-size:.78rem; line-height:1.6; }
    .employee-dashboard .personal-panel footer { padding:.75rem 1.2rem; font-size:.77rem; }
    .employee-dashboard .personal-action-grid { gap:.75rem; padding:.85rem; }
    .employee-dashboard .personal-action-card { padding:.9rem 1rem; border-radius:9px; box-shadow:none; }
    .employee-dashboard .personal-action-icon { width:28px; height:28px; border-radius:7px; }
    .employee-dashboard .personal-action-icon svg { width:16px; height:16px; }
    .employee-dashboard .personal-action-card h3 { margin:.6rem 0 .2rem; font-size:.92rem; }
    .employee-dashboard .personal-action-card p { margin:.3rem 0 .65rem; font-size:.77rem; line-height:1.55; }
    .employee-dashboard .personal-action-card blockquote { padding:.5rem .65rem; margin:.3rem 0 .75rem; font-size:.78rem; line-height:1.55; }
    .employee-dashboard .personal-button { min-height:34px; padding:.45rem .7rem; font-size:.76rem; }
    .employee-dashboard .personal-all-clear { grid-column:1/-1; padding:.55rem .4rem; }
    .employee-dashboard .personal-all-clear .personal-empty-icon { width:34px; height:34px; flex-basis:34px; border-radius:10px; }

    .employee-table-wrap { width:100%; overflow-x:auto; }
    .employee-dashboard .employee-request-table { width:100%; min-width:650px; margin:0; border-collapse:collapse; }
    .employee-dashboard .employee-request-table thead th { padding:.65rem 1rem; color:#5a6e61; background:#f5f8f6; border-top:0; border-bottom:1px solid var(--employee-border); font-size:.68rem; letter-spacing:.05em; font-weight:750; text-transform:uppercase; white-space:nowrap; }
    .employee-dashboard .employee-request-table tbody :is(th,td) { padding:.65rem 1rem; border-bottom:1px solid #eaf0ec; background:transparent; color:#354b3c; font-size:.85rem; font-weight:400; vertical-align:top; }
    .employee-dashboard .employee-request-table tbody tr:last-child :is(th,td) { border-bottom:0; }
    .employee-request-table tbody tr:hover { background:#f8fbf9; }
    .employee-request-table tbody tr:focus-within { background:#f3f8f4; }
    .employee-request-row.is-clarification .employee-request-cell { box-shadow:inset 3px 0 #c7aa70; }
    .employee-request-row.is-ready .employee-request-cell { box-shadow:inset 3px 0 #7cae99; }
    .employee-request-cell { width:31%; }
    .employee-request-code { display:inline-flex; align-items:center; gap:.3rem; font-size:.84rem; font-weight:750; text-decoration:none; overflow-wrap:anywhere; }
    .employee-request-code svg { width:12px; height:12px; color:#708d7a; }
    .employee-item-name { display:block; max-width:28rem; margin-top:.2rem; color:#405948; font-size:.82rem; line-height:1.5; overflow-wrap:anywhere; }
    .employee-item-meta { display:block; margin-top:.15rem; color:var(--employee-muted); font-size:.73rem; }
    .employee-status-cell { width:29%; }
    .employee-dashboard .personal-status { padding:.22rem .45rem; font-size:.74rem; border-radius:5px; font-weight:700; }
    .employee-dashboard .personal-status svg { width:12px; height:12px; }
    .employee-deadline { display:flex; align-items:center; flex-wrap:wrap; gap:.2rem .3rem; margin-top:.4rem; color:#855d16; font-size:.71rem; line-height:1.5; }
    .employee-deadline.is-overdue { color:#a13b36; }
    .employee-deadline svg { width:12px; height:12px; }
    .employee-deadline :is(time,span) { flex-basis:100%; padding-left:1.15rem; }
    .employee-next-step { max-width:28rem; margin-top:.2rem; font-size:.74rem; color:var(--employee-muted); }
    .employee-next-step summary { display:list-item; width:fit-content; min-height:24px; padding:.15rem 0; cursor:pointer; color:#436c53; font-weight:600; }
    .employee-next-step summary:focus-visible { outline:3px solid #b38328; outline-offset:3px; border-radius:2px; }
    .employee-next-step p { margin:.3rem 0 0; padding:.5rem .6rem; border-left:2px solid #bacfc1; background:#f4f8f5; font-size:.78rem; line-height:1.6; overflow-wrap:anywhere; }
    .employee-dashboard .employee-request-table .employee-date { color:var(--employee-muted); font-size:.8rem; white-space:nowrap; }
    .employee-dashboard .employee-request-table .employee-value { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
    .employee-dashboard .employee-request-table td.employee-value { color:#294533; font-weight:650; }
    .employee-actions-heading { text-align:right; }
    .employee-row-actions { display:flex; align-items:center; justify-content:flex-end; gap:.4rem; }
    .employee-row-actions a { display:inline-flex; align-items:center; justify-content:center; gap:.35rem; min-height:30px; padding:.3rem .55rem; border-radius:6px; font-size:.77rem; font-weight:650; text-decoration:none; white-space:nowrap; }
    .employee-row-actions .employee-track-link { border:1px solid #dce8df; background:#f0f6f2; color:#245d3b; }
    .employee-track-link:hover { background:#e4f0e8; }
    .employee-row-actions .employee-edit-link { color:#526b5b; }
    .employee-row-actions svg { width:13px; height:13px; }

    .employee-dashboard .personal-side-stack { grid-column:2; grid-row:1/4; display:block; position:sticky; top:88px; max-height:calc(100dvh - 104px); overflow-y:auto; scrollbar-width:thin; border-radius:12px; }
    .employee-dashboard .personal-side-stack:focus-visible { outline:3px solid #7da0bf; outline-offset:3px; }
    .employee-dashboard .personal-side-stack > * { min-width:0; margin:0; }
    :is(.employee-dashboard,.employee-notification-drawer) .personal-notifications { border-color:#d8e4ef; }
    :is(.employee-dashboard,.employee-notification-drawer) .personal-notifications > header { background:#eef4fa; border-bottom-color:#dce6ef; }
    :is(.employee-dashboard,.employee-notification-drawer) .personal-notifications .personal-eyebrow { color:#526f88; }
    :is(.employee-dashboard,.employee-notification-drawer) .personal-notifications .personal-section-icon { color:#3f6b92; border-color:#d6e2ee; background:#e5eef7; }
    :is(.employee-dashboard,.employee-notification-drawer) .personal-notifications .is-unread { background:#f5f9fd; }
    :is(.employee-dashboard,.employee-notification-drawer) .personal-notifications .is-unread::before { background:#6c96ba; }
    :is(.employee-dashboard,.employee-notification-drawer) .personal-notifications .personal-unread { color:#335f84; background:#e5eef8; }
    :is(.employee-dashboard,.employee-notification-drawer) .personal-notifications :is(a,button) { color:#355f83; }
    :is(.employee-dashboard,.employee-notification-drawer) .personal-notifications footer { background:#f4f8fc; border-top-color:#e1e9f1; }
    .employee-guidance-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; align-items:start; }
    .employee-guidance-grid > * { min-width:0; margin:0; }
    .employee-dashboard .personal-sla,.employee-dashboard .personal-archive-card { padding:1.1rem 1.2rem; border-radius:12px; }
    .employee-dashboard .personal-sla { background:#f1f6f2; }
    .employee-dashboard .personal-sla > strong { font-size:1.15rem; }
    .employee-dashboard .personal-sla p { font-size:.78rem; line-height:1.65; }
    .employee-dashboard .personal-archive-card { border-style:solid; }
    .employee-dashboard .personal-archive-card h3 { font-size:.9rem; }
    .employee-dashboard .personal-archive-card p { margin:.4rem 0 .65rem; font-size:.78rem; line-height:1.6; }
    .employee-dashboard .personal-updates > li { padding:.85rem 1.2rem; }
    .employee-dashboard .personal-updates p { margin:.35rem 0 .5rem; line-height:1.6; }
    .employee-dashboard .personal-updates time { color:#64766b; margin-bottom:.4rem; }
    .employee-dashboard .personal-updates :is(a,button) { display:inline-flex; align-items:center; min-height:28px; }

    .employee-notifications-launcher { display:none; }
    .employee-notification-drawer { inset:0 0 0 auto; width:min(360px,calc(100vw - 1rem)); max-width:none; height:100dvh; max-height:100dvh; margin:0; padding:0; overflow-y:auto; border:0; border-left:1px solid #d8e4ef; border-radius:14px 0 0 14px; background:#fff; color:#20392d; font:inherit; }
    .employee-notification-drawer::backdrop { background:rgb(10 29 18 / 42%); }
    .employee-notification-drawer .personal-side-stack { display:block; position:static; max-height:none; overflow:visible; }
    .employee-notification-drawer .personal-panel { margin:0; border:0; border-radius:0; }
    .employee-notification-drawer .personal-panel > header { padding:1rem 1.2rem; }
    .employee-notification-drawer .personal-panel h2 { font-size:1.1rem; }
    .employee-notification-drawer .personal-updates > li { padding:1rem 1.2rem; }
    .employee-notification-drawer .personal-updates :is(a,button) { min-height:32px; display:inline-flex; align-items:center; }
    .employee-notifications-close { display:block; width:100%; min-height:44px; padding:.7rem 1.2rem; border:0; border-bottom:1px solid #dce6ef; background:#f4f8fc; color:#355f83; text-align:right; font:inherit; font-weight:650; }
    .employee-notification-drawer :is(a,button):focus-visible { outline:3px solid #7da0bf; outline-offset:2px; }
    @media screen and (min-width:768px) and (max-width:1199px) {
        .dashboard-page.employee-dashboard .dashboard-hero { flex-direction:column; align-items:stretch; padding:1.2rem; }
        .dashboard-page.employee-dashboard .dashboard-hero-actions { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); min-width:0; }
        .dashboard-page.employee-dashboard .hero-btn.primary { grid-column:1/-1; }
        .employee-dashboard .personal-summary { grid-template-columns:minmax(0,1fr); }
        .employee-dashboard .personal-action-grid,.employee-guidance-grid { grid-template-columns:minmax(0,1fr); }
    }
    @media screen and (min-width:768px) and (max-width:991px) {
        .employee-dashboard .personal-side-stack { position:static; max-height:none; overflow:visible; }
    }
    @media screen and (max-width:767px) {
        .employee-dashboard { display:block; }
        .dashboard-page.employee-dashboard .dashboard-hero { margin-bottom:1.15rem; }
        .employee-dashboard .personal-summary { margin-bottom:1.15rem; }
        .employee-dashboard .personal-side-stack { display:none; }
        .employee-notifications-launcher { position:fixed; right:1rem; bottom:1rem; z-index:1040; display:inline-flex; align-items:center; gap:.5rem; min-height:44px; padding:.7rem 1rem; border:1px solid #b7cce0; border-radius:10px; background:#eaf2fa; color:#284f72 !important; font-size:.85rem; font-weight:700; text-decoration:none; box-shadow:0 4px 16px #163b2320; }
        .dashboard-page.employee-dashboard .dashboard-hero { padding:1.25rem; flex-direction:column; align-items:stretch; gap:1.1rem; }
        .dashboard-page.employee-dashboard .dashboard-hero-actions { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); min-width:0; }
        .dashboard-page.employee-dashboard .hero-btn { min-height:42px; padding:.55rem .4rem; font-size:.76rem; }
        .dashboard-page.employee-dashboard .hero-btn.primary { grid-column:auto; }
        .employee-dashboard .personal-summary { gap:.65rem; }
        .employee-dashboard .personal-summary > a { padding:.85rem; gap:.3rem; }
        .employee-dashboard .personal-summary-top { font-size:.65rem; }
        .employee-dashboard .personal-summary strong { font-size:1.8rem; }
        .employee-dashboard .personal-summary h3 { font-size:.85rem; }
        .employee-dashboard .personal-summary p { font-size:.72rem; }
        .employee-dashboard .personal-panel > header { padding:.9rem 1rem; }
        .employee-table-wrap { overflow:visible; }
        .employee-dashboard .employee-request-table { min-width:0 !important; display:block; }
        .employee-request-table thead { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip-path:inset(50%); white-space:nowrap; }
        .employee-request-table tbody { display:grid; grid-template-columns:minmax(0,1fr); gap:.65rem; padding:.75rem; background:#f5f8f6; }
        .employee-request-table tbody tr { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); border:1px solid var(--employee-border); border-radius:9px; background:#fff; overflow:hidden; }
        .employee-dashboard .employee-request-table tbody :is(th,td) { display:block; width:auto; padding:.65rem .8rem; border:0; min-width:0; }
        .employee-request-table .employee-request-cell { grid-column:1/-1; border-bottom:1px solid #edf1ee !important; }
        .employee-request-table .employee-status-cell { grid-column:1/-1; }
        .employee-request-table td[data-label]::before { display:block; margin-bottom:.35rem; content:attr(data-label); color:#65796c; font-size:.67rem; font-weight:750; letter-spacing:.04em; text-transform:uppercase; }
        .employee-dashboard .employee-request-table .employee-date { white-space:normal; }
        .employee-request-table .employee-actions-cell { grid-column:1/-1; border-top:1px solid #edf1ee !important; background:#fbfcfb !important; }
        .employee-request-table .employee-actions-cell::before { display:none; }
        .employee-row-actions { justify-content:flex-start; }
        .employee-row-actions a { min-height:38px; padding:.45rem .75rem; }
        .employee-request-table td[colspan] { grid-column:1/-1; }
        .employee-dashboard .personal-start-state { padding:1.5rem 1rem !important; }
        .employee-guidance-grid { grid-template-columns:minmax(0,1fr); }
    }
    @media screen and (max-width:575px) {
        .employee-dashboard .personal-summary > a { grid-template-columns:36px minmax(0,1fr) auto; gap:.2rem .8rem; padding:.9rem 1rem; }
        .employee-dashboard .personal-summary-top { grid-column:1; grid-row:1/3; }
        .employee-dashboard .personal-summary-icon { width:36px; height:36px; }
        .employee-dashboard .personal-summary strong { grid-column:3; grid-row:1/3; }
        .employee-dashboard .personal-summary h3 { grid-column:2; margin:0; font-size:.94rem; }
        .employee-dashboard .personal-summary p { grid-column:2; }
        .dashboard-page.employee-dashboard .dashboard-hero-actions { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .dashboard-page.employee-dashboard .hero-btn.primary { grid-column:1/-1; }
    }
    @media print {
        .employee-dashboard { display:block; }
        .employee-notifications-launcher,.employee-notification-drawer { display:none !important; }
        .employee-dashboard .personal-side-stack,.employee-dashboard .employee-actions-cell,.employee-dashboard .employee-actions-heading { display:none !important; }
        .employee-dashboard .dashboard-hero { color:#173b27 !important; background:#fff !important; box-shadow:none !important; }
        .employee-dashboard .dashboard-hero :is(h2,p,span) { color:#173b27 !important; }
        .employee-table-wrap { overflow:visible; }
        .employee-dashboard .employee-request-table { min-width:0; }
        .employee-dashboard .personal-panel { break-inside:auto; }
    }
</style>
@endPushOnce
