# Supply Management Office Request Tracking and Inventory System

<p align="center">
  <strong>Holy Trinity College of General Santos City</strong><br>
  College of Engineering and Technology Education (CETE)<br>
  Bachelor of Science in Information Technology (BSIT) &bull; Capstone Project
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2 or later">
  <img src="https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL or MariaDB">
  <img src="https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap 5.3">
  <img src="https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?style=for-the-badge&logo=alpinedotjs&logoColor=white" alt="Alpine.js 3">
</p>

## About the project

The Supply Management Office Request Tracking and Inventory System is a web-based platform for managing institutional supply requisitions at Holy Trinity College of General Santos City (HTC).

It replaces a paper-based process with a centralized workflow where employees can submit requests, authorized signatories can review them, and Supply Management Office (SMO) personnel can manage inventory and release approved items. The system also provides status tracking, deadline alerts, notifications, PDF reports, and an auditable approval history.

The project is intended to support transparent electronic records and approval workflows in line with the objectives of the Philippine Electronic Commerce Act of 2000 (Republic Act No. 8792).

## Main features

- Multi-item supply requisitions with automatic totals
- Minor and major request workflows based on the PHP 1,000 threshold
- Role-based approval queues for seven institutional roles
- Request tracking numbers and a visual progress tracker
- Approve, reject, and office-clarification decisions
- Digital approval records with SHA-256 signature tokens
- Inventory management and release deductions
- Inventory transaction logs and low-stock monitoring
- In-app and email notifications
- Day 2 warning and Day 3 overdue SLA alerts
- Filterable reports and PDF generation
- User registration, account approval, and role management
- Request and administrative archives

## Approval workflow

Every standard request begins with the requester's department head.

```text
Employee / Faculty
        |
        v
Department Head
        |
        +------------------------------+
        |                              |
        v                              v
Minor request                    Major request
(PHP 1,000 or less)              (more than PHP 1,000)
        |                              |
        v                              v
VP for Finance                   VP for Administration
        |                              |
        |                              v
        |                         School Provost
        |                              |
        |                              v
        |                         School President
        |                              |
        +---------------+--------------+
                        |
                        v
                 SMO verification
                        |
                        v
                  Item release
```

At each applicable approval stage, a signatory can:

1. **Approve** the requisition and send it to the next stage.
2. **Reject** the requisition and provide an explanatory remark.
3. **Request clarification** and place the requisition in a clarification queue while notifying the requester and SMO.

## User roles

| Role         | Primary responsibility                                    |
| ------------ | --------------------------------------------------------- |
| `employee`   | Creates and tracks supply requisitions                    |
| `dept_head`  | Performs the first departmental review                    |
| `vp_finance` | Reviews minor requisitions                                |
| `vp_admin`   | Reviews major requisitions                                |
| `provost`    | Reviews endorsed major requisitions                       |
| `president`  | Gives final approval to major requisitions                |
| `smo`        | Manages users, inventory, releases, reports, and archives |

## Technology stack

| Layer             | Technology                                   |
| ----------------- | -------------------------------------------- |
| Backend           | PHP 8.2+, Laravel 12, Eloquent ORM           |
| Database          | MySQL or MariaDB                             |
| Frontend          | Blade, Bootstrap 5.3, Alpine.js, Vite        |
| PDF generation    | Barryvdh Laravel DomPDF                      |
| Email             | Laravel Mail (SMTP or log driver)            |
| Testing           | PHPUnit 11                                   |
| Local environment | XAMPP or an equivalent PHP/MySQL environment |

See the [entity-relationship diagram](database/ERD.md) for the current application data model.

## Requirements

Install the following before setting up the application:

- PHP 8.2 or later with the extensions required by Laravel
- Composer 2
- Node.js and npm
- MySQL 8 or MariaDB
- Git

For Windows development, XAMPP can provide Apache, PHP, and MariaDB. Make sure the PHP executable used by Composer and Artisan is PHP 8.2 or later.

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/rivacjayrex-dotcom/htc-supply-management-system.git
cd htc-supply-management-system
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Create the environment file

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

On macOS or Linux:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### 4. Configure the database

Create a MySQL or MariaDB database named `htc_supply_system`, then update these values in `.env`:

```dotenv
APP_NAME="HTC Supply Management System"
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=htc_supply_system
DB_USERNAME=root
DB_PASSWORD=
```

### Broadcast server to all connected wifi

php artisan serve --host=0.0.0.0 --port=8000

Adjust the database username and password for your local environment.

### 5. Configure email

The default `.env.example` writes email to the application log. To deliver email through an SMTP provider, replace the mail values in `.env`, for example:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_FROM_ADDRESS="smo-notifications@online.htcgsc.edu.ph"
MAIL_FROM_NAME="${APP_NAME}"
```

Do not commit real SMTP credentials to the repository.

### 6. Create the database tables and seed reference data

```bash
php artisan migrate
php artisan db:seed
php artisan db:seed --class=SupplyCategorySeeder
```

`DatabaseSeeder` creates departments and approved workflow accounts. `SupplyCategorySeeder` creates the initial supply categories.

The seeded accounts use the password stored in `SEED_ACCOUNT_PASSWORD`. The development default in `.env.example` is `Password123!`; change it before seeding any shared or production environment.

### 7. Build frontend assets

```bash
npm run build
```

### 8. Start the application

For a simple local session, run these commands in separate terminals:

```bash
php artisan serve
```

```bash
npm run dev
```

### Render environment settings

Keep `APP_KEY` stable between deployments. Generate it once with
`php artisan key:generate --show`, save that value in Render, and never rotate
it during a build. Rotating the key invalidates sessions and produces 419
responses for forms that were already open.

Recommended Render environment variables:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-service.onrender.com
APP_KEY=base64:your-persistent-key
SESSION_DRIVER=database
SESSION_DOMAIN=
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

The test suite is restricted to in-memory SQLite. Clear locally cached
configuration with `php artisan config:clear` before running tests.

Open `http://127.0.0.1:8000` in a browser.

Alternatively, the Composer development script starts the Laravel server, queue listener, log viewer, and Vite together:

```bash
composer run dev
```

## Scheduled SLA alerts

The `smo:check-deadlines` command checks every active requisition against one fixed SLA that starts at initial submission. It warns after two working days and marks the request overdue after three working days, excluding weekends and dates stored in `institutional_holidays`. Alerts go to the requestor, all active SMO users, and the approver currently responsible for the request. Laravel schedules the command daily at 8:00 AM in the configured application timezone (`Asia/Manila` by default). Read notifications older than one year are pruned by a separate daily task.

During local development, keep the scheduler running:

```bash
php artisan schedule:work
```

The included Docker entrypoint starts `schedule:work` automatically. Non-Docker deployments must configure the server to execute Laravel's scheduler every minute.

The command can also be run manually:

```bash
php artisan smo:check-deadlines
```

## Testing

Run the automated test suite with either command:

```bash
composer test
```

```bash
php artisan test
```

## Tracking and audit records

The system generates institutional tracking numbers such as:

- Minor: `MIN-CETE-20000026`
- Major: `MAJ-CBMA-10000025`

Requisition items store a price snapshot so historical totals do not change when catalog prices are updated. Approval actions and inventory movements are stored in separate audit tables. Approved actions also receive a SHA-256 signature token associated with the signer, role, IP address, user agent, and signing time.

## Project team

| Name                  | Role / focus                               | Program / institution                           |
| --------------------- | ------------------------------------------ | ----------------------------------------------- |
| Margart Augwie Ampan  | Assistant Documenter and System Tester     | BS Information Technology, Holy Trinity College |
| Segundino Jr. C. Baja | Head Documenter and System Tester          | BS Information Technology, Holy Trinity College |
| Jayrex E. Rivac       | Full-Stack Developer and Quality Assurance | BS Information Technology, Holy Trinity College |

---

<p align="center">
  <strong>Holy Trinity College of General Santos City</strong><br>
</p>
