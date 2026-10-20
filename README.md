# Vikas OS: portfolio of Vikas Rambaul Giri

A portfolio you can boot: a desktop-style site with draggable windows, a dock,
Spotlight search, a working terminal, a job-description fit checker and a
contact form that emails via Brevo (HTTPS API) or PHPMailer (SMTP).

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
rules apply). Free hosts block SMTP, so email uses the Brevo API:

| Variable | Value |
|---|---|
| `BREVO_API_KEY` | Brevo → SMTP & API → API Keys |
| `MAIL_FROM` | a sender email verified in Brevo |
| `MAIL_TO` | where inquiries are delivered |

Content lives in `includes/content.php`.
