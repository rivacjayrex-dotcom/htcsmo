# Brevo email setup and panel demonstration

## Delivery method

Use `MAIL_MAILER=brevo` on Render free hosting. It sends real transactional emails through Brevo's HTTPS API using the official Symfony Brevo mail transport. Password resets and requisition emails use the same Laravel Mail configuration. This demonstrates functional email delivery via an API; it does not demonstrate the SMTP protocol.

Render free services block outbound ports 25, 465, and 587. If the panel explicitly requires SMTP, demonstrate it locally on XAMPP or an SMTP-capable host using the SMTP settings below. Brevo also offers port 2525, but its availability from your deployed host must be tested before relying on it.

Sources: [Render free-service limitations](https://render.com/docs/free), [Brevo SMTP setup](https://help.brevo.com/hc/en-us/articles/7924908994450-Send-transactional-emails-using-Brevo-SMTP).

## 1. Prepare the free Brevo account

1. Create your Brevo account and complete its verification/onboarding. Ensure transactional sending is enabled; resolve any activation request shown in the dashboard.
2. Add a sender under **Settings > Senders, Domains & Dedicated IPs** and complete email verification. Use that exact address as `MAIL_FROM_ADDRESS`. An SMTP login address is not a sender address.
3. If you control a school/custom domain, authenticate it using Brevo's DNS instructions (including DKIM and DMARC). Only use the school's address if you can verify it. A sender owned by the demonstrator can be used for testing; follow the requirements Brevo displays for that address/domain.
4. Under **Settings > SMTP & API > API Keys**, create an API key with transactional-email sending access. Store it directly in Render's environment settings or your private local `.env`.

Do not paste the key into chat, commit it, or put it in frontend JavaScript. `BREVO_API_KEY` must contain an **API key**, not an SMTP key or your Brevo account password.

The local `.env` file is untracked and ignored by Git. Keep local credentials there and production credentials in Render's Environment settings. Use `.env.example` as the committed template, with secret values left blank. If a real key enters Git history, revoke and replace it, and remove it from every unpushed commit before retrying the push. Do not use GitHub's protection bypass for a real key.

Sources: [API keys](https://help.brevo.com/hc/en-us/articles/40159485019794-Create-and-manage-your-API-keys), [sender troubleshooting](https://help.brevo.com/hc/en-us/articles/115000188150-Troubleshooting-Issues-with-Brevo-SMTP).

## 2. Configure Render

Set these values in your Render service's **Environment** settings, then save and redeploy:

```dotenv
MAIL_MAILER=brevo
BREVO_API_KEY=replace-with-your-private-api-key
MAIL_FROM_ADDRESS=your-verified-sender@your-domain.com
MAIL_FROM_NAME="HTC Supply Management Office"
APP_URL=https://your-service.onrender.com
QUEUE_CONNECTION=database
```

Remove old Mailtrap variables/credentials from Render. `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, and `MAIL_URL` are not used by the Brevo API mailer. Local `.env` changes do not update Render. Keep the existing `APP_KEY` and database configuration.

The Docker entrypoint caches configuration, migrates the database, and starts a worker for the `mail,default` queues. Requisition emails wait for this worker; password-reset emails send immediately within the request. The email HTTP transport has a bounded timeout so an unavailable provider does not wait indefinitely.

Use the correct public `APP_URL` so links in emails open your deployed system. Requisition owner links go to the request details; reviewer links go to their authorized approval queue. Users must sign in, and existing access checks still apply.

Free Render services sleep after inactivity. Open the site before the demonstration and allow it to wake up; queued jobs cannot run while the service is asleep. Store jobs/users in a persistent external database, not an ephemeral SQLite file.

## 3. Local configuration and delivery test

Use the same environment values locally, adjusting `APP_URL` to the URL you actually serve. Then run:

```powershell
php artisan config:clear
php artisan queue:restart
php artisan mail:test --check
php artisan mail:test your-real-inbox@example.com
```

`--check` validates that required local values exist; it does not contact Brevo or prove that the sender/key is accepted. The second command sends **one real email** immediately without requiring a queue worker. Confirm it arrives in the recipient inbox/spam folder and in Brevo's transactional logs. Provider acceptance is not proof of inbox delivery.

For requisition emails, keep a worker running:

```powershell
php artisan queue:work --queue=mail,default --tries=3 --timeout=30
```

Alternatively, `powershell -File tools/start-mail-worker.ps1` starts the local mail worker in the background. After configuration/code changes, restart the worker. Do not run commands that clear the queue: pending real emails may be waiting there.

## 4. Panel demonstration

Use real inbox addresses on the demonstration accounts; seeded/example addresses will not receive mail. Each recipient consumes an email send, so avoid creating many active approvers/SMO accounts just for the demonstration.

| Action                 | Email recipients                                                                                       |
| ---------------------- | ------------------------------------------------------------------------------------------------------ |
| Forgot password        | The registered account's email address                                                                 |
| New requisition        | Requestor, active SMO users, and the assigned active initial reviewer(s), with duplicate users removed |
| Approval/endorsement   | Requestor and the active next reviewer(s); final approval alerts SMO                                   |
| Rejection              | Requestor, with the rejection reason                                                                   |
| Office clarification   | Requestor, with the consultation instructions and remarks                                              |
| SMO processing started | Requestor                                                                                              |
| Ready for pickup       | Requestor                                                                                              |
| Completed/released     | Requestor                                                                                              |

1. Open **Forgot password**, enter a real registered email, and open the received reset link. Set a new password and sign in. Reset links expire after 60 minutes and cannot be reused after success. Unknown addresses receive the same generic confirmation without an email.
2. Submit a requisition. Show the requestor confirmation, SMO email, and assigned reviewer email.
3. Approve it at one stage. Show the requestor update and next reviewer's email. Reviewer links open the approval queue after sign-in.
4. Reject a separate requisition with remarks. Show the rejection reason in the email. Use another request to demonstrate clarification if needed.
5. Complete a fully approved requisition through processing, ready for pickup, and completion. Show the corresponding requestor emails.
6. Show Brevo's transactional logs as delivery evidence. The application's `email_logs` records successful requisition handoffs and final job failures; its `sent` status means the provider accepted the message, not that it reached the inbox. Password-reset emails are visible in Brevo's logs, not the application's requisition email log.

## 5. Free-tier limits and troubleshooting

Brevo's free plan currently allows **300 email sends per day**, shared by resets, submissions, and workflow notifications. Unused sends do not carry over. Reaching the quota can delay transactional mail; check the dashboard before the panel demonstration. See [Brevo free-plan limits](https://help.brevo.com/hc/en-us/articles/208580669-FAQs-What-are-the-limits-of-the-Free-plan).

- **Missing key / unauthorized:** check `BREVO_API_KEY`, API permissions, transactional activation, and whether Render was redeployed after the change. A newly created SMTP key will not work as an API key.
- **Sender rejected:** confirm that `MAIL_FROM_ADDRESS` exactly matches a verified sender and complete required domain authentication.
- **Reset works but requisition emails do not:** check that the mail queue worker is running and the service is awake. Locally use `php artisan queue:failed`; inspect the Render logs for production worker failures. Only retry selected failed jobs after fixing the cause, because retries can send real emails again.
- **Provider accepted but inbox empty:** inspect Brevo's delivery/bounce/suppression logs, recipient address, spam folder, and daily quota. API success alone is not delivery confirmation.
- **Reset email send fails:** the form reports temporary unavailability and removes the failed reset token so it can be requested again. Provider credentials and raw errors are not displayed to the user.
- **Diagnosing a reset failure on Render:** retry once, then find `Password reset email could not be sent` in Render Logs. The `reason`, optional `http_status`, and optional `provider_code` identify the failure without logging credentials or reset links. `missing_brevo_api_key` means the running Render environment lacks a key; `api_authentication_failed` means Brevo rejected authentication; `api_access_denied` means check the key's Transactional email **All** permission and account access; `sender_configuration_rejected` means check the verified sender; `ip_authorization_required` means review the blocked IP in Brevo's API security settings and authorize the legitimate server IP; `account_under_validation` / `transactional_sending_not_enabled` means complete Brevo activation. `provider_connection_failed` means no usable provider response was received. Do not disable IP protection or certificate verification to work around an error.
- **Link opens the wrong host:** correct `APP_URL` and refresh configuration/restart workers. A localhost URL will not work on a panelist's separate device.

## Optional: real Brevo SMTP on XAMPP or an SMTP-capable host

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=copy-the-exact-smtp-login-from-brevo
MAIL_PASSWORD=your-brevo-smtp-key
MAIL_TIMEOUT=15
MAIL_FROM_ADDRESS=your-verified-sender@your-domain.com
MAIL_FROM_NAME="HTC Supply Management Office"
```

Use an **SMTP key** for `MAIL_PASSWORD`, not an API key. Port 587 uses STARTTLS automatically when the server advertises it; do not set `MAIL_SCHEME=tls` in this Laravel/Symfony configuration. Keep certificate verification enabled. For port 465 use `MAIL_SCHEME=smtps`. Follow Brevo's current setup instructions if a different port is needed. Remove any old `MAIL_URL` that would override individual SMTP settings, clear configuration, restart workers, and send a test email.
