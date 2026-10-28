# REST API v1

The HTC Supply Management System exposes a bearer-token JSON API at `/api/v1`. It uses the same database, requisition submission service, validation, approval routing, and notifications as the website. Existing session-based website endpoints continue to work.

## Setup

For a new checkout or deployment:

```sh
composer install
php artisan migrate
```

The migration adds `personal_access_tokens`. For an already configured installation where only this migration is needed:

```sh
php artisan migrate --path=database/migrations/2026_10_28_000001_create_personal_access_tokens_table.php
```

Tokens expire after 1,440 minutes (24 hours). Set `API_TOKEN_EXPIRATION_MINUTES` in `.env` to change this, then refresh any cached Laravel configuration. The existing scheduler runs `sanctum:prune-expired --hours=24` daily. Expired tokens are rejected immediately even before pruning.

Use HTTPS for deployed API clients. Keep tokens in the client's secure credential storage. The versioned API accepts bearer tokens; browser session cookies do not authenticate it. Cross-origin browser clients need an explicit CORS configuration for their origin and the `Authorization` header. No cross-origin frontend is configured by this change.

## Try it in PowerShell

Start the application with `php artisan serve`. Substitute an existing active account's username or school ID. `Get-Credential` prompts for the password without putting it in shell history.

```powershell
$baseUrl = 'http://127.0.0.1:8000/api/v1'
$credential = Get-Credential -Message 'Enter your system username or school ID and password'
$loginBody = @{
    login = $credential.UserName
    password = $credential.GetNetworkCredential().Password
    device_name = 'PowerShell'
} | ConvertTo-Json

$login = Invoke-RestMethod -Method Post -Uri "$baseUrl/auth/tokens" -ContentType 'application/json' -Body $loginBody
$headers = @{ Authorization = "Bearer $($login.access_token)"; Accept = 'application/json' }

Invoke-RestMethod -Uri "$baseUrl/me" -Headers $headers
Invoke-RestMethod -Uri "$baseUrl/supplies?in_stock=1&per_page=20" -Headers $headers
Invoke-RestMethod -Uri "$baseUrl/requisitions" -Headers $headers

# Revoke this client's token when finished.
Invoke-RestMethod -Method Delete -Uri "$baseUrl/auth/token" -Headers $headers
```

Use your own application base URL if Apache/XAMPP or another host serves it. In Postman, import [api-v1.openapi.json](api-v1.openapi.json), set the server URL, call `POST /auth/tokens`, and put `access_token` in the collection's Bearer Token authorization.

## Endpoints

All paths below are relative to `/api/v1`. Every endpoint except token creation requires `Authorization: Bearer <access_token>`.

| Method | Path | Behavior |
|---|---|---|
| POST | `/auth/tokens` | Sign in using `login`, `password`, and `device_name`; returns a token with HTTP 201 |
| DELETE | `/auth/token` | Revoke the token used for this request; HTTP 204 |
| GET | `/me` | Read the authenticated user's profile |
| GET | `/supplies` | Read the paginated catalog |
| GET | `/supplies/{id}` | Read a catalog item and its valid options |
| PATCH | `/supplies/{id}` | SMO only: update quantity, price, and/or stock threshold |
| GET | `/requisitions` | Read permitted requisitions, including archived records |
| GET | `/requisitions/{id}` | Read a permitted requisition with items and approval history |
| POST | `/requisitions` | Submit a requisition; HTTP 201 with a `Location` header |

Active users receive `supplies:read`, `requisitions:read`, and `requisitions:write` abilities. SMO users also receive `supplies:write`. Each endpoint enforces the corresponding ability, and inventory writes separately verify the user's current SMO role. Password, account status, role, or department changes through the application revoke that user's tokens. Account status is also checked on every authenticated request.

Requisition visibility matches the existing `visibleTo` model scope: SMO can read all requisitions; every other role can read only their own. Inaccessible and nonexistent requisition IDs both return HTTP 404. Reviewers continue to use the website's approval queue for approval and fulfillment actions. Requisition editing/withdrawal, inventory creation/deletion, user management, and reports remain website features in this version.

### List filters

Both lists accept `page` (default 1, maximum 1,000,000) and `per_page` (default 20, maximum 100). Lists return `data`, `links`, and `meta` including current page and total count.

`GET /supplies` accepts:

- `search`: item-name search, maximum 100 characters.
- `requisition_tier`: `minor` or `major`.
- `in_stock`: `1` to include only positive stock; omitted or `0` includes all available catalog records. Soft-deleted items are excluded.

`GET /requisitions` accepts:

- `search`: existing requisition search, maximum 100 characters, restricted to permitted records.
- `request_type`: `minor` or `major`.
- `status`: `pending`, `approved_dept`, `approved_vp`, `approved_provost`, `approved_president`, `processing`, `ready_for_pickup`, `for_clarification`, `completed`, `released`, `rejected`, or `withdrawn`.

### Submit a requisition

```json
{
  "request_type": "minor",
  "purpose": "Printing classroom learning materials.",
  "items": [
    { "supply_id": 1, "qty": 2, "options": { "Size": "A4" } }
  ]
}
```

Choose a real catalog ID and use the item's returned `options` map to select valid choices. Omit `options` when none are configured. Purpose must contain 10–500 characters. A request may have 1–100 items, positive integer quantities, and at most five option selections per item.

The server calculates item names, prices, subtotals, grand total, ownership, tracking number, and initial approval status. Submitted values cannot override these. The server checks the combined quantity when the same supply appears more than once. All items must belong to the selected tier. Minor totals must be at most PHP 1,000; major totals must exceed PHP 1,000. Submission does not reserve or deduct stock; the existing fulfillment workflow handles stock deductions.

Item prices must fit the existing requisition snapshot limit of PHP 99,999,999.99, and the combined total must not exceed PHP 9,999,999,999.99. Values above these storage limits return HTTP 422 before records are written.

Successful submission returns `data`, `message`, and `notification_warning`. A non-null warning means the requisition was saved but some notifications failed; do not resubmit it just to retry notifications. The detail endpoint returns current status, next-step guidance, item snapshots, and approval history without signature tokens or authentication secrets.

### Update inventory

```json
{ "quantity": 40, "unit_price": "125.50", "min_stock_level": 10 }
```

Send at least one of these three fields. Quantity and minimum stock must be integers from 0 to 2,147,483,647; price must be from 0.01 to 99,999,999.99 with at most two decimal places. Other fields cannot update the catalog through this endpoint. Quantity is the new absolute balance. Stock writes use a database row lock and record the difference in inventory history with the acting SMO user. Price and threshold updates are also recorded in the activity log. Existing low/out-of-stock alerts run after saving; notification failures return a warning alongside the saved supply.

### Responses and limits

Resource responses use a `data` object. Monetary values are decimal strings in PHP currency, and timestamps use ISO 8601 UTC. API responses disable caching. Errors use a JSON `message`; HTTP 422 also contains field-keyed `errors`.

| Status | Meaning |
|---|---|
| 200 | Read or update succeeded |
| 201 | Token or requisition created |
| 204 | Current token revoked |
| 401 | Missing, invalid, expired, or revoked token |
| 403 | Inactive account, missing ability, or insufficient role |
| 404 | Missing or inaccessible record |
| 422 | Invalid credentials, payload, filters, or business-rule violation |
| 429 | Rate limit reached; respect `Retry-After` |

Token creation is limited to 5 attempts/minute per normalized login and 20/minute per IP. Authenticated API traffic is limited to 60 requests/minute per user across tokens, with an additional limit of 10 requisition submissions/minute per user. Invalid submissions count toward these limits. An expired token requires a new login; there is no refresh-token endpoint.

## Verification

```sh
php artisan route:list --path=api/v1 -v
php artisan test --compact tests/Feature/Api/V1
```

The feature tests use real bearer tokens against the HTTP routes and an isolated in-memory SQLite database. They cover login, token hashing/expiry/revocation, account and role restrictions, pagination, catalog integrity, submission totals/routing, notifications, inventory audit records, and rate limits.

Implementation verification: all 26 API tests passed. The full suite ran 332 tests with 325 passing and seven UI text assertion failures. A separate checkout of the original source reproduced exactly those seven failures across the affected UI test classes (29 tests checked), confirming that they predate the API change. PHP formatting, Composer metadata, and all OpenAPI schema references were checked.

Composer's production dependency audit reported advisories affecting 12 existing packages, including the framework and PDF dependencies. It reported none for the newly added Sanctum package. Those existing dependency upgrades require separate compatibility testing.

Authentication follows the [Laravel 12 Sanctum documentation](https://laravel.com/docs/12.x/sanctum).
