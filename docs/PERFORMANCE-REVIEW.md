# Page loading review — October 27, 2026

The strongest measured bottleneck is the database connection. The local application uses a remote MySQL server, so opening the site on localhost still requires internet round trips for its data. A frontend rewrite would retain that delay.

## Measurements

Read-only diagnostics used the existing configured database. No business records or connection settings were changed. The dashboard probe called its controller and rendered its Blade view with an existing SMO account in memory; it did not log in through HTTP or submit actions. Its numbers exclude authentication, initial database connection setup, downloads, and browser rendering.

| Measurement | Observed value |
| --- | --- |
| Initial database connection | 589 ms |
| Five sequential `SELECT 1` calls | 211 ms average; 198–249 ms range |
| Original SMO dashboard | 32 queries; 8.54 seconds in database calls |
| Final SMO dashboard | 15 queries; 6.30 seconds in database calls |
| Original/final dashboard HTML | 311,563 / 229,366 bytes, about 26% less |
| First public Render `/up` request | 34.41 seconds |
| Following public Render `/login` request | 1.18 seconds |
| Later public Render `/up` request | 0.45 seconds |

Database latency varied considerably during the review: an intermediate 21-query version took 13.35 seconds in database calls. The query and payload reductions are repeatable changes in work; these individual timing samples are not a controlled browser benchmark or a guaranteed percentage speedup.

The Render result is consistent with a service starting after inactivity, but the service's compute plan was not inspected. Render documents that free services sleep after 15 idle minutes and can take about a minute to start: [Render free-service documentation](https://render.com/docs/free#spinning-down-on-idle). The public HTTP measurements do not establish Render's authenticated database latency.

## Changes implemented locally

- Load the institutional holiday calendar once per request/job instead of once per displayed requisition. Calendar edits and transaction rollbacks invalidate the current scope; the calendar is reloaded in a new scope.
- Combine three SMO requisition totals into one database query, retaining fulfillment status and completion-month rules.
- Load the overlapping SMO request previews' items, owners, and departments together. Each relationship table needs one query instead of three.
- Move about 82 KB of shared authenticated styles and 11 KB of guest styles out of HTML into separate Vite CSS assets. Page styles remain in the head, after the shared styles.
- Replace the blocking `unpkg.com/lucide@latest` script with a pinned local icon dependency and explicit icon imports. Preserve the `window.lucide.createIcons()` API used by Alpine and live updates.
- Bundle the existing Montserrat font locally with `font-display: swap`, removing Google Fonts requests.
- Enable Apache compression in the Docker image and long-lived caching for content-hashed Vite assets. These hosting changes take effect after rebuilding/redeploying the container.
- Restore the missing truncated approval-preview summary required by the existing dashboard test.

## Remaining steps with the largest potential effect

1. **For localhost:** use a separate local MySQL/MariaDB database rather than the cloud database. Import the data needed for development, then configure the local connection. The current cloud connection was preserved because it may intentionally supply shared data. Configure the local `APP_URL` for the address actually used in the browser.
2. **For Render:** measure `SELECT 1` from inside the running service. Compare the database location with the web-service region; use nearby infrastructure when moving either service. Moving infrastructure or upgrading a plan was not performed.
3. **For slow first visits:** check the Render compute plan and compare idle versus warm requests. Reducing frontend code cannot remove the documented free-service startup delay.
4. **For local PHP:** the CLI OPcache extension was absent. Check Apache's PHP configuration separately before enabling it; CLI settings do not prove Apache's settings.

Use production environment settings (`APP_ENV=production`, `APP_DEBUG=false`) in Render. The Docker startup already caches Laravel configuration, routes, and views. Do not cache authenticated HTML publicly or change the shared session/cache backend merely to reduce database queries.

## Repeating the checks

```bash
php artisan smo:check-performance --samples=5
npm run build
php artisan test --filter='BusinessDaySlaTest|SmoDashboardTest|WorkspacePerformanceTest|InitialRenderTest|PerformanceDiagnosticsTest|RequisitionCatalogTest|SessionRecoveryTest|NotificationFeatureTest'
node --test --experimental-test-isolation=none tests/Frontend/*.test.mjs
```

The diagnostic command only executes `SELECT 1`, omits credentials and hostnames, and identifies its results as CLI/database timings. It is also suitable for a Render shell when available. Clear stale Laravel configuration before changing environments; tests must retain the project's in-memory SQLite restriction.

The production Vite build and all 139 frontend tests passed. Focused PHP checks passed, including calendar invalidation, dashboard totals, relationship query budgets, session recovery, notifications, and catalog behavior. The full PHP suite finished with **294 passing tests out of 301**, seven assertion failures, and no errors. The failures concern existing UI text in `DashboardWorkspaceTest`, `DynamicSearchWorkspaceTest`, `EmployeeRequestExperienceTest`, and `ReportWorkspaceTest`. The CSS assertion in `RequisitionCatalogTest` was updated to inspect its new stylesheet location. PHP formatting and `git diff --check` also passed. The Docker image itself was not rebuilt in this environment.

No rebuild of the application architecture, data migration, production deployment, hosting upgrade, or database-region change was performed.

## Follow-up: slow runtime polls and queued navigation

The supplied development-server logs show built assets loading in milliseconds, but `/api/runtime-state`, `/inventory`, and `/notifications` taking seconds. The initial favicon request also waited several seconds. These observations point to backend/database waiting and local request contention rather than large CSS or font downloads.

The log format matches Laravel's `artisan serve` output. PHP's built-in server handles requests in one process; a blocked request can stall other requests. PHP does not support multiple built-in-server workers on Windows. See the [PHP built-in server manual](https://www.php.net/manual/en/features.commandline.webserver.php).

For Windows development, start Apache through XAMPP and open `http://localhost/htc-supply-system/public/` instead of the Artisan server's port. A dedicated Apache virtual host should point its document root at this project's `public` directory. Apache improves concurrency, while a local development database addresses the underlying remote-database latency. Neither environment change was applied automatically.

Additional changes:

- An idle runtime poll now executes one notification summary query and skips reloading payloads when there are no unread/new records.
- Notification action resolution fetches only the requisition fields needed for authorization. Department owners and clarification logs load only when the applicable review checks require them. Targets are rechecked against current records on every resolution.
- Notification category/unread counts share one database round trip. Personal request totals also share one query. The existing category predicates, including legacy notification rules, remain in use.
- Workspace version markers are fetched together. A warm database-backed cache now needs one read for the three markers instead of three reads; missing markers retain the existing initialization behavior.
- Polling yields to navigation, writes, searches, and workspace refreshes. Slow responses introduce a delay of twice their elapsed time, capped at 30 seconds; the normal minimum remains three seconds. Server retry cooldowns and failure backoff can be longer. The polling lease covers that delay so another visible tab does not immediately undo the reduction.

A read-only controller probe against the configured database measured **one query / 189 ms database time / 191 ms controller time** with no new notices. A probe with pending notices needed four queries / 1.13 seconds database time. These exclude database connection setup, authentication, HTTP session middleware, and browser rendering; they must not be compared directly with the complete HTTP durations in the supplied logs.

Follow-up validation: all **141 frontend tests** passed. The full PHP run completed **298 passing tests out of 305**, with the same seven UI wording failures and no errors. After the additional version-marker batching change, all **45 focused PHP tests** passed, including a new database-cache query-budget check. This follow-up changes PHP/Blade code; the previously built static assets do not require rebuilding for these changes alone.
