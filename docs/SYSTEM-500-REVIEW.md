# Requisition submission 500 review

Reviewed October 23, 2026 (Asia/Manila).

The reported failure happens after submitting a requisition, while the browser is still on the submission URL. The user confirmed that the requisition exists afterward. This places the failure after persistence, or at the transaction's commit boundary, rather than establishing that the submission was rejected.

## Reproduced defect and local fix

`WorkspaceObserver` implements `ShouldHandleEventsAfterCommit`. Every requisition and requisition-item write schedules an update to a cached workspace version. Previously, an exception from that cache update propagated out of the transaction after the database had committed. This could return HTTP 500, prevent the submission controller from reaching its email and notification code, and leave the saved requisition in the database.

A regression test injected a failure in that refresh-cache update. Before the fix, the submission returned HTTP 500. The observer now catches exceptions from the refresh-marker update and records a warning with the affected domain, model, record ID, and exception. The submission can finish and redirect normally. Database write failures are still errors; this change only isolates the cache callback that runs after commitment.

The regression verifies one saved requisition, one saved item, the dashboard redirect, the owner's in-app notification, queued emails, and logged refresh warnings. The focused submission, workflow, modification, fulfillment, and performance checks passed: **66 tests, 673 assertions**.

This is a confirmed code defect that matches the reported symptom. The exception from the affected Render deployment has not been supplied, so the production incident's root cause is **not yet confirmed**. Fatal execution timeouts, a different deployed revision, and session persistence failures remain possible. The fix has not been deployed by this review.

If the cache remains unavailable, automatic workspace refresh can stay stale. The underlying cache permissions or availability must still be corrected; a normal page reload retrieves current business records. This patch does not make an unavailable database or session store healthy.

## System scan results

| Check | Result |
| --- | --- |
| PHP syntax in application, configuration, routes, migrations, and seeders/factories | 127 files checked; no syntax errors |
| Blade compilation | 75 templates checked; no compile errors |
| Literal Blade includes/components/parent views | No missing referenced views |
| Route registration | 63 application routes registered |
| Frontend interaction suite | 135 passed |
| Backend suite before this fix | 286 passed; 7 failed; 3,068 assertions |
| Backend suite after this fix | 287 passed; the same 7 failed; 0 errors; 3,076 assertions |
| Configured cloud database | Read-only connection succeeded during review |
| Submission-related schema | Required columns present in requisitions, requisition_items, supplies, notifications, jobs, institutional_holidays, supply_options, and supply_option_values |
| Migrations on the configured database | No unapplied migrations |
| Local frontend build manifest | Present |

The database check used the connection configured in this workspace. It did not verify Render's environment variables, make a real submission, or change application records. SQLite integration tests do not establish identical behavior for every MySQL constraint, collation, or production data value.

## Seven existing backend test failures

All seven failures occurred after successful HTTP 200 responses and concern expected page text. They are separate from an HTTP 500 response, and should be reconciled with the intended current UI rather than silently removed from the test suite.

| Test | First missing expected text |
| --- | --- |
| DashboardWorkspaceTest: SMO command center | Inventory and fulfillment overview |
| EmployeeRequestExperienceTest: dashboard preview limits | Showing 8 of 14 active requests. |
| EmployeeRequestExperienceTest: tier-aware detail stages | Your next step |
| EmployeeRequestExperienceTest: clarification detail guidance | Clarification remarks and next step |
| EmployeeRequestExperienceTest: personal archive navigation | View my active requests |
| ReportWorkspaceTest: operational analytics | Open Report Center |
| SmoDashboardTest: deadline preview limits | Showing the earliest 6 of 8 approval follow-ups. |

The remaining assertions in each failed test may not have run. These results do not certify that every later assertion passes.

## Deployment and incident checks still needed

1. Obtain the first exception message and application stack frames from Render's Logs for the failed submission. The Docker entrypoint already routes ordinary file/stack logging to stderr as well, unless a different custom log channel is configured. Do not enable public debug output to collect it.
2. Compare the deployed revision with this workspace. The current submission controller already isolates email and in-app notification exceptions; an older deployed revision may lack that protection.
3. Verify the running service uses `QUEUE_CONNECTION=database`. This is the local configured value; Render's value has not been inspected. If it is `sync`, email delivery runs in the HTTP request and a fatal SMTP timeout can occur after saving. The SMTP timeout is currently unset in the mail configuration; a queue worker's job timeout does not protect an HTTP request using the synchronous queue.
4. If the exception mentions cache/file permissions or the new `Workspace refresh marker failed after a committed change.` warning, inspect the runtime user's access to `storage/framework/cache/data`. Docker build-time ownership does not prove the ownership of files later created by root CLI processes.
5. If the exception mentions sessions or logging, inspect the configured session store and log destinations. Those can fail while Laravel is finishing an otherwise successful response.
6. If it reports a database connection failure or execution timeout, investigate that specific error. Local history contains a database hostname lookup failure and database execution timeouts, but those older entries do not identify the current Render incident.

Use the already saved requisition when continuing the workflow. Retrying the same submission can create another request; the form has no server-side idempotency token.

Detailed backend test output is available locally in `storage/logs/system-scan-tests.xml` and `storage/logs/system-scan-after-fix.xml`; the new cache regression initially failed in `storage/logs/submission-cache-regression.xml`. These files may contain synthetic test data and large rendered HTML and should not be published as production logs.
