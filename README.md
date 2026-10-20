# Vikas OS: portfolio of Vikas Rambaul Giri

A portfolio you can boot: a desktop-style site with draggable windows, a dock,
Spotlight search, a working terminal, a job-description fit checker and a
contact form that emails via PHPMailer.

**Stack:** PHP 8.2, vanilla JS/CSS, PHPMailer 7 (bundled, no Composer).

## Run locally (XAMPP)

1. Put this folder in `htdocs/` and open `http://localhost/<folder>/`.
2. For email, create `includes/mail-config.local.php` (git-ignored):

```php
<?php
return [
    'username' => 'you@gmail.com',
    'password' => 'your-16-char-app-password',
];
```

## Deploy (Railway)

The included `Dockerfile` runs PHP 8.2 + Apache, so `.htaccess` rules apply.
Set these variables in Railway → Variables:

| Variable | Example |
|---|---|
| `SMTP_HOST` | `smtp.gmail.com` |
| `SMTP_PORT` | `587` |
| `SMTP_SECURE` | `tls` |
| `SMTP_USER` | `you@gmail.com` |
| `SMTP_PASS` | Gmail app password |
| `MAIL_TO` | `vikasr.giri@outlook.com` |

Content lives in `includes/content.php`.
