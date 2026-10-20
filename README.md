# Vikas OS: portfolio of Vikas Rambaul Giri

A portfolio you can boot: a desktop-style site with draggable windows, a dock,
Spotlight search, a working terminal, a job-description fit checker and a
contact form that emails via PHPMailer (SMTP).

**Stack:** PHP 8.2, vanilla JS/CSS, PHPMailer 7 (bundled, no Composer), Docker.

## Run locally (XAMPP)

1. Put this folder in `htdocs/` and open `http://localhost/<folder>/`.
2. For email via Gmail SMTP, create `includes/mail-config.local.php` (git-ignored):

```php
<?php
return [
    'username' => 'you@gmail.com',
    'password' => 'your-16-char-app-password',
];
```

## Deploy free on Render

[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy?repo=https://github.com/jackvika/vikas-os)

`render.yaml` creates a free Docker web service (PHP 8.2 + Apache, so `.htaccess`
rules apply).

### Email on Render: Gmail relay (recommended)

Render's free plan blocks outgoing SMTP, so production email goes through a tiny
Google Apps Script that sends from your own Gmail over HTTPS (~100 emails/day free).
Follow the steps at the top of [`deploy/gmail-relay.gs`](deploy/gmail-relay.gs), then set:

| Variable | Value |
|---|---|
| `GMAIL_RELAY_URL` | the Apps Script web app URL (`…/exec`) |
| `GMAIL_RELAY_SECRET` | the same value as `RELAY_SECRET` in the script |

Check it at `/mail-test.php?token=<DIAG_TOKEN>&send=1` (set `DIAG_TOKEN` temporarily).

### Email on hosts that allow SMTP

Without the relay variables, the contact form uses PHPMailer over SMTP:

| Variable | Value |
|---|---|
| `SMTP_HOST` / `SMTP_PORT` / `SMTP_SECURE` | `smtp.gmail.com` / `587` / `tls` (preset) |
| `SMTP_USER` | Gmail address |
| `SMTP_PASS` | 16-character Gmail app password |
| `MAIL_TO` | where inquiries are delivered |

Mail errors are written to the service logs (Render → Logs, lines starting with `[mail]`).

Content lives in `includes/content.php`.
