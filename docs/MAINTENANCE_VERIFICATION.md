# Maintenance verification — October 29, 2026

The existing REST API scope is retained. This maintenance fixes the failing website checks and updates vulnerable dependencies before deployment.

## Website repairs

- Restored the employee request guidance partial, including the existing `employee-next-step` anchor, tier-aware next steps, the latest clarification remarks, deadline information, and navigation. Clarification remarks remain HTML-escaped.
- Restored the employee dashboard's displayed preview count and total, with the existing eight-request preview and prioritization unchanged. The SLA explanation identifies a working-day processing target rather than a promised pickup date.
- Added an active-request return link to populated archives, alongside the export and print actions.
- Gave the deadline queue's “See All” link a contextual accessible label.
- Updated older SMO dashboard, unified inventory empty-state, and report navigation assertions to match the current interface, while retaining data, permission, filtering, and link checks.

The baseline suite contained eight failing UI assertions across 367 tests. The fixes address missing guidance/navigation as well as obsolete wording assertions; no assertions were skipped.

## Dependency updates

Composer updates were limited to the affected packages and required dependencies within the existing requirements. Laravel remains on version 12, Symfony on version 7, and the application remains compatible with PHP 8.2 and the PHP 8.3 Docker image. No advisory exclusions or blocking overrides were added.

| Package | Before | After |
|---|---|---|
| laravel/framework | 12.56.0 | 12.70.2 |
| dompdf/dompdf | 3.1.5 | 3.1.6 |
| guzzlehttp/guzzle | 7.10.0 | 7.16.1 |
| guzzlehttp/psr7 | 2.9.0 | 2.13.2 |
| league/commonmark | 2.8.2 | 2.10.4 |
| league/flysystem | 3.33.0 | 3.36.0 |
| Affected Symfony 7.4 components | 7.4.8 | 7.4.22 |
| axios | 1.15.0 | 1.21.0 |
| vite | 7.3.2 | 7.3.7 |
| postcss | 8.5.10 | 8.5.32 |
| concurrently | 9.2.1 | 9.2.5 |

The PHP update also includes `guzzlehttp/promises` and `symfony/polyfill-intl-idn`. npm updates include vulnerable transitive packages and use the existing package requirements without `--force`. The checked-in `composer.lock` and `package-lock.json` contain the exact versions to deploy.

## Checks

| Check | Result |
|---|---|
| Full PHP suite | 367 passed, 3,836 assertions |
| API tests, included above | 26 passed |
| Frontend tests | 141 passed |
| Production Vite build | Passed |
| Laravel route and Blade view cache builds, using isolated temporary paths | Passed |
| Composer audit, including development packages | Zero known advisories; previously 51 across 13 packages |
| npm audit, including development packages | Zero vulnerabilities; previously 12 affected packages |
| Composer validation and installed platform requirements | Passed |
| Formatting of the changed PHP tests and whitespace checks | Passed |

Audits reflect the advisory data available when these checks ran. Raw audit reports and test reports are saved in the ignored `storage/logs/maintenance-*` files. PHP tests use an in-memory SQLite database; this maintenance adds no database migration.

## Deployment

The code and lockfiles are prepared locally. Docker is unavailable in this workspace, so the complete container build must run on Render. The existing Dockerfile keeps the package-specific Git checkout workaround for the previously failing `fruitcake/php-cors` archive download.

Publish the reviewed changes, including both lockfiles, to the branch used by Render and deploy that revision. The existing entrypoint builds Laravel caches, runs pending migrations, and starts the scheduler and mail worker. Keep deployment credentials and the stable application key in Render's environment settings.

After deployment, check `/up`, verify that `/api/v1/me` rejects a missing token with JSON HTTP 401, and sign in through the website to verify the employee guidance and SMO inventory/report screens. A Render integration was found and offered, but it is not connected in this session; no hosted deployment has been triggered.
