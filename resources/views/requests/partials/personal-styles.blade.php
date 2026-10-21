@pushOnce('styles', 'page-styles:requests/partials/personal-styles.blade.php')
<style>
    /* Employee workspace: restrained surfaces, readable type, and consistent actions. */
    .personal-workspace,.personal-list,.personal-guidance { --personal-ink:#20392d; --personal-muted:#617469; --personal-green:#1b6843; --personal-line:#e0e8e3; --personal-soft:#f4f7f5; color:var(--personal-ink); }
    .personal-workspace svg,.personal-guidance svg,.personal-navigation svg { width:17px; height:17px; flex-shrink:0; vertical-align:middle; }
    .personal-workspace :is(a,button):focus-visible,.personal-guidance a:focus-visible,.personal-list :is(a,button,input,select):focus-visible { outline:3px solid #b38328; outline-offset:4px; }
    .personal-workspace a,.personal-guidance a { color:#1a643e; text-underline-offset:3px; }
    .personal-workspace a:hover,.personal-guidance a:hover { color:#10472b; }
    .dashboard-page:has(.personal-workspace) { padding:0; }
    .dashboard-page:has(.personal-workspace) .dashboard-hero { min-height:160px; margin:0 0 1.5rem; padding:.8rem .15rem 1.5rem; gap:1.5rem; border:0; border-bottom:1px solid #dfe7e2; border-radius:0; background:none; box-shadow:none; color:#20392d; overflow:visible; }
    .dashboard-page:has(.personal-workspace) .dashboard-hero::after { display:none; }
    .dashboard-page:has(.personal-workspace) .dashboard-kicker { color:#547563; letter-spacing:.13em; font-size:.72rem; }
    .dashboard-page:has(.personal-workspace) .dashboard-hero h2 { margin:.4rem 0; color:#183b29; font-size:clamp(1.75rem,2.8vw,2.35rem); letter-spacing:-.04em; }
    .dashboard-page:has(.personal-workspace) .dashboard-hero-copy > p { color:#617569; font-size:.9rem; line-height:1.65; }
    .dashboard-page:has(.personal-workspace) .dashboard-hero-meta { margin-top:.9rem; gap:1rem; }
    .dashboard-page:has(.personal-workspace) .dashboard-hero-meta > span { color:#5e7566; font-size:.76rem; }
    .dashboard-page:has(.personal-workspace) .dashboard-hero-meta svg { color:#578369; }
    .dashboard-page:has(.personal-workspace) .dashboard-hero-actions { min-width:180px; gap:.5rem; }
    .dashboard-page:has(.personal-workspace) .hero-btn { min-height:41px; padding:.65rem 1rem; border-radius:9px; font-size:.82rem; box-shadow:none; }
    .dashboard-page:has(.personal-workspace) .hero-btn.primary { background:#1c6944; border-color:#1c6944; color:#fff; box-shadow:0 4px 10px #1c694419; }
    .dashboard-page:has(.personal-workspace) .hero-btn.secondary { background:#fff; color:#355b43; border-color:#dbe5de; }
    .dashboard-page:has(.personal-workspace) .hero-btn:hover { transform:none; background:#edf4ef; color:#164f31; }
    .dashboard-page:has(.personal-workspace) .hero-btn.primary:hover { background:#145333; color:#fff; }

    .personal-summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; margin-bottom:1.5rem; }
    .personal-summary > a { --summary-tone:#2c6244; --summary-soft:#edf4ef; min-width:0; position:relative; display:block; padding:1.2rem 1.35rem; border:1px solid var(--personal-line); border-radius:14px; background:#fff; text-decoration:none; box-shadow:0 3px 12px #163b2305; transition:border-color .18s,box-shadow .18s; }
    .personal-summary > a:hover { border-color:#9bb5a3; box-shadow:0 7px 20px #163b230a; }
    .personal-summary .personal-summary-clarification { --summary-tone:#926315; --summary-soft:#fcf3df; }
    .personal-summary .personal-summary-pickup { --summary-tone:#1c7853; --summary-soft:#e6f5ee; }
    .personal-summary-top { display:flex; align-items:center; gap:.6rem; color:#6a7c70; font-size:.72rem; font-weight:650; }
    .personal-summary-top > svg { margin-left:auto; width:15px; color:#91a095; }
    .personal-summary-icon { display:grid; place-items:center; width:34px; height:34px; border-radius:10px; color:var(--summary-tone); background:var(--summary-soft); }
    .personal-summary strong { display:block; margin:.7rem 0 .4rem; color:var(--summary-tone); font-size:2.5rem; font-weight:750; line-height:1; letter-spacing:-.04em; font-variant-numeric:tabular-nums; }
    .personal-summary h3 { margin:0; color:#2b4334; font-size:.95rem; font-weight:750; }
    .personal-summary p { margin:.4rem 0 0; color:#677b6d; font-size:.8rem; line-height:1.5; }

    .personal-columns { display:grid; grid-template-columns:minmax(0,1fr) 310px; align-items:start; gap:1.25rem; }
    .personal-main-stack,.personal-side-stack { min-width:0; }
    .personal-panel { min-width:0; margin-bottom:1.2rem; overflow:hidden; border:1px solid var(--personal-line); border-radius:14px; background:#fff; box-shadow:0 3px 12px #163b2304; }
    .personal-panel > header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.8rem; padding:1.25rem 1.3rem; border-bottom:1px solid #eaf0ec; }
    .personal-eyebrow { display:block; margin-bottom:.35rem; color:#708577; font-size:.67rem; letter-spacing:.12em; text-transform:uppercase; font-weight:750; }
    .personal-panel h2 { margin:0; color:#243e2f; font-size:1.08rem; letter-spacing:-.015em; font-weight:750; }
    .personal-panel h2 > span { display:inline-grid; place-items:center; min-width:24px; height:24px; margin-left:.3rem; padding:0 .45rem; border-radius:7px; color:#3f6850; background:#edf4ef; font-size:.75rem; vertical-align:middle; }
    .personal-panel header p { max-width:490px; margin:.45rem 0 0; color:#708074; font-size:.78rem; line-height:1.6; }
    .personal-panel header > a { display:inline-flex; align-items:center; gap:.3rem; font-size:.75rem; font-weight:650; text-decoration:none; }
    .personal-panel header > a:hover { text-decoration:underline; }
    .personal-panel footer { padding:.9rem 1.3rem; border-top:1px solid #eaf0ec; background:#fbfcfb; font-size:.78rem; font-weight:650; }
    .personal-section-icon { display:grid; place-items:center; flex:0 0 32px; width:32px; height:32px; border:1px solid #e1e9e4; border-radius:9px; color:#588169; background:#f7faf8; }

    .personal-action-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.85rem; padding:1rem; }
    .personal-action-card { --action-tone:#927025; --action-soft:#fbf5e9; min-width:0; display:flex; flex-direction:column; padding:1.1rem; border:1px solid #e8e0cf; border-radius:11px; background:linear-gradient(140deg,#fff,#fffdf8); }
    .personal-action-card.is-ready { --action-tone:#28744b; --action-soft:#edf6ef; border-color:#d8e7dd; background:linear-gradient(140deg,#fff,#f8fcf9); }
    .personal-action-heading { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem; }
    .personal-action-icon { display:grid; place-items:center; width:34px; height:34px; border-radius:9px; color:var(--action-tone); background:var(--action-soft); }
    .personal-action-icon svg { width:19px; height:19px; }
    .personal-action-card h3 { margin:.8rem 0 .3rem; color:#2e4536; font-size:1.02rem; font-weight:750; overflow-wrap:anywhere; }
    .personal-action-card p { margin:.4rem 0 .8rem; color:#63776a; font-size:.8rem; line-height:1.65; }
    .personal-action-card .personal-reference { color:#6c7f71; font-family:ui-monospace,SFMono-Regular,monospace; font-size:.7rem; }
    .personal-reference > span { font-family:Inter,sans-serif; }
    .personal-action-card blockquote,.personal-guidance blockquote { padding:.8rem .9rem; margin:.4rem 0 1rem; border-left:3px solid #c8a65a; border-radius:0 7px 7px 0; background:#fbf5e9; color:#71592c; font-size:.81rem; line-height:1.7; white-space:pre-wrap; overflow-wrap:anywhere; }
    .personal-workspace .personal-button { display:inline-flex; align-items:center; justify-content:center; gap:.45rem; min-height:39px; padding:.6rem .8rem; border:1px solid #226e46; border-radius:8px; background:#226e46; color:#fff; font-size:.78rem; font-weight:700; text-decoration:none; }
    .personal-action-card > .personal-button { margin-top:auto; align-self:flex-start; }
    .personal-workspace .personal-button:hover { color:#fff; background:#175332; border-color:#175332; }
    .personal-status { display:inline-flex; align-items:center; gap:.35rem; flex-wrap:wrap; max-width:100%; padding:.3rem .5rem; border:1px solid #e3eae5; border-radius:6px; color:#5d7464; background:#f2f6f3; font-size:.7rem; font-weight:700; line-height:1.45; }
    .personal-status.is-clarification { color:#8c5d13; background:#fcf1d9; border-color:#f0dfb7; }.personal-status.is-ready,.personal-status.is-success { color:#226b46; background:#eaf6ef; border-color:#d5eadc; }.personal-status.is-danger { color:#aa3a44; background:#fceef0; border-color:#f3dade; }
    .personal-request-list,.personal-updates { margin:0; padding:0; list-style:none; }
    .personal-request-list > li { padding:1.2rem 1.3rem; border-bottom:1px solid #eaf0ec; }
    .personal-request-list > li:last-child,.personal-updates > li:last-child { border-bottom:0; }
    .personal-request-top { display:flex; align-items:center; justify-content:space-between; gap:.7rem; flex-wrap:wrap; }
    .personal-request-identity { min-width:0; display:flex; gap:.65rem; align-items:center; }
    .personal-request-identity > div { min-width:0; }
    .personal-request-icon { display:grid; place-items:center; flex:0 0 34px; width:34px; height:34px; border-radius:9px; color:#648170; background:#f0f5f2; }
    .personal-request-identity a { color:#2a4936; font-size:.92rem; font-weight:750; text-decoration:none; overflow-wrap:anywhere; }
    .personal-request-identity a:hover { text-decoration:underline; }
    .personal-request-reference { display:block; margin-top:.25rem; color:#7a8b7f; font-size:.69rem; overflow-wrap:anywhere; }
    .personal-submitted { display:block; margin:.6rem 0 0 3.1rem; color:#7b897f; font-size:.72rem; }
    .personal-request-list p { margin:.6rem 0; font-size:.8rem; line-height:1.65; }
    .personal-request-list .personal-next-step { margin-left:3.1rem; color:#667b6d; font-size:.78rem; }
    .personal-request-row .personal-deadline { margin-left:3.1rem; }
    .personal-deadline { display:flex; align-items:flex-start; gap:.35rem; font-weight:650; }.personal-deadline.overdue { color:#a53a45; }.personal-deadline.near { color:#946718; }
    .personal-row-actions { display:flex; align-items:center; flex-wrap:wrap; gap:.6rem; margin:.7rem 0 0 3.1rem; }
    .personal-row-actions a { display:inline-flex; align-items:center; gap:.3rem; padding:.35rem .6rem; border:1px solid #dce7e0; border-radius:6px; color:#39684b; background:#f8fbf9; font-size:.72rem; font-weight:650; text-decoration:none; }
    .personal-row-actions a:hover { background:#edf5ef; border-color:#a2bfae; }
    .personal-empty { grid-column:1/-1; padding:1.5rem; color:#677e6d; font-size:.83rem; line-height:1.6; }
    .personal-empty-icon { display:grid; place-items:center; flex:0 0 40px; width:40px; height:40px; border-radius:12px; color:#518669; background:#edf6ef; }
    .personal-empty-icon svg { width:22px; height:22px; }
    .personal-all-clear { display:flex; align-items:center; gap:.8rem; padding:.9rem; }.personal-all-clear p { margin:.25rem 0 0; }.personal-all-clear strong { color:#3d5c48; font-size:.85rem; font-weight:650; }
    .personal-start-state { display:flex; align-items:center; flex-direction:column; text-align:center; padding:2.4rem 1.2rem !important; }.personal-start-state h3 { margin:.9rem 0 .2rem; font-size:1.08rem; font-weight:700; color:#34543e; }.personal-start-state p { max-width:360px; }.personal-start-state > a { margin-top:.8rem; font-size:.78rem; }

    .personal-updates > li { position:relative; padding:1rem 1.2rem; border-bottom:1px solid #eaf0ec; }
    .personal-updates > li.is-unread { background:#f8fbf9; }
    .personal-updates > li.is-unread::before { content:''; position:absolute; left:0; top:1.2rem; height:24px; width:3px; background:#4f9570; border-radius:0 3px 3px 0; }
    .personal-update-heading { display:flex; align-items:flex-start; gap:.45rem; flex-wrap:wrap; }
    .personal-update-heading > svg { width:15px; height:15px; color:#6c9279; margin-top:2px; }
    .personal-updates h3 { flex:1; min-width:100px; margin:0; color:#35503e; font-size:.82rem; font-weight:700; line-height:1.55; }
    .personal-unread { padding:.15rem .35rem; border-radius:5px; background:#e7f2ea; color:#2b7549; font-size:.63rem; font-weight:750; }
    .personal-updates p { color:#6b7d70; font-size:.77rem; line-height:1.7; margin:.5rem 0; overflow-wrap:anywhere; }
    .personal-updates time { display:block; color:#869389; font-size:.69rem; margin-bottom:.65rem; }
    .personal-updates a,.personal-link-button { font-size:.74rem; }
    .personal-link-button { display:inline-flex; gap:.3rem; align-items:center; border:0; padding:0; color:#2a6843; background:transparent; font-weight:650; text-align:left; text-decoration:underline; text-underline-offset:3px; }
    .personal-sla,.personal-archive-card { margin:0 0 1.2rem; padding:1.25rem; border:1px solid #e0e9e3; border-radius:14px; background:#f7faf8; }
    .personal-sla { display:block; }.personal-sla-heading { display:flex; align-items:center; gap:.6rem; margin-bottom:.8rem; }.personal-sla h3,.personal-archive-card h3 { margin:0; font-size:.93rem; font-weight:700; color:#3e5d48; }
    .personal-sla > strong { display:block; color:#3f634c; font-size:1.2rem; font-weight:700; letter-spacing:-.015em; }
    .personal-sla p,.personal-archive-card p { margin:.5rem 0 .9rem; color:#6b7f71; font-size:.78rem; line-height:1.75; }
    .personal-sla > a { display:block; padding-top:.85rem; border-top:1px solid #e1eae4; font-size:.75rem; line-height:1.8; text-decoration:none; }
    .personal-sla > a > span { display:inline-block; min-width:21px; padding:0 .35rem; margin-right:.15rem; border-radius:5px; color:#86601e; background:#faf0da; text-align:center; font-weight:750; }
    .personal-archive-card { background:#fff; border-style:dashed; }.personal-archive-card h3 { margin:.8rem 0 .4rem; }.personal-archive-card > a { font-size:.77rem; font-weight:650; }

    .personal-guidance { margin:1.25rem 0; padding:1.4rem; border:1px solid #dce7df; border-left:4px solid #5b9875; border-radius:12px; color:#304c39; background:#fff; scroll-margin-top:90px; box-shadow:0 4px 16px #173d2305; }
    .personal-guidance.is-clarification { border-left-color:#c4a05c; }.personal-guidance h2 { margin:.8rem 0 .5rem; font-size:1.15rem; font-weight:750; letter-spacing:-.02em; }.personal-guidance p { max-width:850px; margin:.55rem 0; font-size:.88rem; line-height:1.75; }.personal-guidance blockquote { padding:1rem; margin:1rem 0; font-size:.9rem; }.personal-guidance a { font-weight:650; font-size:.84rem; }
    .personal-list .personal-list-heading { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; padding:.4rem 0 1.2rem; }.personal-list-heading h2 { margin:.3rem 0; font-size:1.7rem; color:#244532; font-weight:750; letter-spacing:-.035em; }.personal-list-heading p { margin:0; color:#6d7e72; font-size:.85rem; }
    .personal-navigation { display:flex; align-items:center; flex-wrap:wrap; gap:.5rem; margin-bottom:1rem; }.personal-list-heading .personal-navigation { margin:0; }.personal-navigation .btn { display:inline-flex; align-items:center; gap:.4rem; min-height:39px; border-radius:8px; padding:.55rem .8rem; font-size:.8rem; font-weight:650; }
    .personal-list .request-metrics { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:.7rem; margin-bottom:1rem; }
    .personal-list .request-metric { align-items:flex-start; gap:.6rem; padding:1rem; border-radius:12px; border:1px solid #e0e8e3; box-shadow:none; animation:none; background:#fff; }.personal-list .request-metric > span { width:30px; height:30px; flex:0 0 30px; border-radius:9px; }.personal-list .request-metric > span svg { width:16px; height:16px; }.personal-list .request-metric strong { font-size:1.65rem; font-weight:750; letter-spacing:-.03em; }.personal-list .request-metric h3 { margin-top:.4rem; font-size:.8rem; }.personal-list .request-metric p { margin-top:.25rem; color:#78887b; font-size:.71rem; line-height:1.5; white-space:normal; }
    .personal-filter-tabs { display:flex; flex-wrap:wrap; gap:.4rem; padding:.35rem; margin:1rem 0; width:fit-content; max-width:100%; border:1px solid #dfe7e2; border-radius:10px; background:#f2f6f3; }
    .personal-filter-tabs a { display:inline-flex; align-items:center; gap:.45rem; padding:.55rem .8rem; border:1px solid transparent; border-radius:7px; color:#647a6a; font-size:.78rem; font-weight:650; text-decoration:none; }.personal-filter-tabs a:hover { color:#285339; background:#e9f0ec; }.personal-filter-tabs a[aria-current=page] { color:#245a37; background:#fff; border-color:#dfe8e2; box-shadow:0 1px 4px #21422c09; }.personal-filter-tabs a > span { padding:.05rem .35rem; color:#497357; background:#e6efe9; border-radius:5px; font-size:.67rem; }
    .personal-list .request-panel { border-radius:14px; border-color:#e0e8e3; box-shadow:0 3px 12px #163b2304; }.personal-list .request-panel-heading { padding:1.2rem; }.personal-list .request-panel-heading h3 { font-size:1.05rem; }.personal-list .request-panel-heading p { font-size:.8rem; color:#748579; }.personal-list .request-panel-heading .request-eyebrow { font-size:.65rem; letter-spacing:.1em; }
    .personal-list .request-filters { grid-template-columns:minmax(170px,1.5fr) minmax(160px,1.3fr) repeat(3,minmax(110px,.8fr)) auto; padding:1rem; gap:.65rem; background:#f8faf9; }
    .personal-list .request-filters :is(input,select),.personal-list .request-filters > button { min-height:41px; font-size:.82rem; border-radius:8px; }.personal-list .request-filters label > span { margin-bottom:.4rem; color:#5b7362; font-size:.67rem; letter-spacing:.06em; }.personal-list .request-filters > button { padding-inline:.8rem; }.personal-list .request-filters select { text-overflow:ellipsis; }
    .personal-list .history-request-code { font-size:.8rem; font-weight:700; }.personal-list .history-item b { font-size:.86rem; font-weight:650; }.personal-list .history-item small,.personal-list .request-history-table td > small { font-size:.71rem; color:#7c8b80; }.personal-list .history-value { font-size:.85rem; font-weight:700; }.personal-list .history-tier { font-size:.65rem; padding:.35rem .5rem; border-radius:6px; }
    .personal-list .history-status { height:auto; overflow:visible; white-space:normal; font-size:.76rem; text-transform:none; line-height:1.55; border-radius:6px; }.personal-list .history-stage { max-width:310px; font-size:.79rem !important; line-height:1.65; color:#6c8072 !important; margin-top:.45rem; }.personal-list .history-deadline { font-size:.7rem; font-weight:700; text-transform:none; }.personal-list .history-action { min-height:36px; border-radius:7px; font-size:.72rem; }.personal-list .request-actions { flex-wrap:wrap; gap:.35rem; }.personal-list .request-history-table thead th { font-size:.65rem !important; padding:1rem !important; color:#728679 !important; letter-spacing:.06em; }.personal-list .request-history-table tbody td { padding:1.05rem 1rem !important; }
    .personal-list .clear-request-filters { font-size:.75rem; }.personal-list .request-empty { padding:2.5rem 1.2rem; }.personal-list .request-empty strong { font-size:1rem; }.personal-list .request-empty p { font-size:.84rem; line-height:1.7; }.personal-list .request-empty button { min-height:40px; font-size:.8rem; }.personal-list .request-empty a { font-size:.8rem; }
    .personal-list .workflow-page-heading h4 { font-size:1.7rem; letter-spacing:-.035em; font-weight:750; }.personal-list .workflow-page-heading p { font-size:.84rem; line-height:1.6; }.personal-list .archive-filter-card { border:1px solid #e0e8e3 !important; box-shadow:none !important; }.personal-list .archive-filter-card :is(input,select,button) { min-height:39px; font-size:.8rem; }
    body:has(.personal-workspace,.personal-list,.personal-guidance) .top-bar .dropdown-menu { left:auto; right:0; }
    @media(min-width:1500px) { .personal-columns { grid-template-columns:minmax(0,1fr) 340px; } }
    @media(max-width:1199px) { .personal-columns { grid-template-columns:minmax(0,1fr) 280px; gap:1rem; }.personal-action-grid { grid-template-columns:1fr; }.personal-list .request-filters { grid-template-columns:repeat(3,minmax(0,1fr)); }.personal-list .request-metrics { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    @media(max-width:991px) { .personal-columns { grid-template-columns:1fr; }.personal-action-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }.personal-side-stack { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; align-items:start; }.personal-side-stack > :first-child { grid-column:1/-1; }.personal-side-stack > * { margin-bottom:0; }.personal-summary > a { padding:1.1rem; }.personal-summary-top > span:not(.personal-summary-icon) { font-size:.68rem; }.dashboard-page:has(.personal-workspace) .dashboard-hero { min-height:140px; } }
    @media(max-width:767px) { .personal-summary { gap:.7rem; }.personal-summary-top > span:not(.personal-summary-icon) { display:none; }.personal-summary strong { font-size:2.1rem; }.personal-summary h3 { font-size:.86rem; }.personal-summary p { font-size:.74rem; }.personal-action-grid { gap:.7rem; padding:.85rem; }.personal-panel > header { padding:1.1rem; }.personal-request-list > li { padding:1.1rem; }.personal-request-top { align-items:flex-start; }.personal-request-list .personal-next-step,.personal-submitted,.personal-request-row .personal-deadline,.personal-row-actions { margin-left:0; }.personal-request-identity a { font-size:.9rem; }.personal-list .request-metrics { grid-template-columns:repeat(2,minmax(0,1fr)); }.personal-list .request-metrics > :last-child { grid-column:1/-1; }.personal-list .request-filters { grid-template-columns:repeat(2,minmax(0,1fr)); }.personal-list .request-search { grid-column:1/-1; }.personal-list .workflow-responsive-table td[data-label="Status"] { display:flex; flex-direction:column; align-items:flex-start; gap:.45rem; text-align:left; }.personal-list .history-stage { width:100%; max-width:none; }.personal-list .workflow-responsive-table td::before { font-size:.65rem; }.personal-guidance { padding:1.1rem; }.dashboard-page:has(.personal-workspace) .dashboard-hero { padding:.4rem 0 1.2rem; }.dashboard-page:has(.personal-workspace) .dashboard-hero-actions { min-width:0; } }
    @media(max-width:575px) { .personal-summary { grid-template-columns:1fr; }.personal-summary > a { display:grid; grid-template-columns:42px minmax(0,1fr) auto; column-gap:.8rem; padding:1rem; align-items:center; }.personal-summary-top { grid-column:1; grid-row:1/3; }.personal-summary-top > svg { display:none; }.personal-summary-icon { width:40px; height:40px; border-radius:11px; }.personal-summary h3 { grid-column:2; grid-row:1; font-size:.9rem; }.personal-summary p { grid-column:2; grid-row:2; margin-top:.25rem; font-size:.74rem; }.personal-summary strong { grid-column:3; grid-row:1/3; margin:0; font-size:1.95rem; }.personal-action-grid,.personal-side-stack { grid-template-columns:1fr; }.personal-request-top { flex-direction:column; gap:.7rem; }.personal-request-top > .personal-status { margin-left:3.1rem; }.personal-list .request-filters { grid-template-columns:1fr; }.personal-list .request-search { grid-column:auto; }.personal-filter-tabs { width:100%; }.personal-filter-tabs a { flex:1 1 auto; justify-content:center; padding:.55rem .6rem; font-size:.74rem; }.personal-list .request-metric { padding:.9rem; }.personal-list .request-metric h3 { font-size:.78rem; }.personal-list .request-metric p { font-size:.7rem; }.personal-list .personal-list-heading { align-items:flex-start; }.personal-list-heading .personal-navigation { width:100%; }.personal-list-heading .personal-navigation > :first-child { flex:1 1 100%; justify-content:center; }.dashboard-page:has(.personal-workspace) .dashboard-hero-actions { grid-template-columns:repeat(2,minmax(0,1fr)); }.dashboard-page:has(.personal-workspace) .hero-btn.primary { grid-column:1/-1; }.dashboard-page:has(.personal-workspace) .dashboard-hero-meta { flex-direction:row; flex-wrap:wrap; gap:.6rem; }.dashboard-page:has(.personal-workspace) .dashboard-hero-meta span { font-size:.7rem; } }
    @media(prefers-reduced-motion:reduce) { .personal-workspace *,.personal-list * { animation:none !important; transition:none !important; scroll-behavior:auto !important; } }
    @media print { .personal-summary,.personal-side-stack,.personal-navigation,.personal-filter-tabs,.personal-row-actions,.personal-panel header > a { display:none; }.personal-columns { display:block; }.personal-panel { box-shadow:none; break-inside:avoid; } }
</style>
@endPushOnce
