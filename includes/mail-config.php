<?php
/**
 * SMTP settings for the contact form (PHPMailer).
 *
 * No secrets live in this file, so it is safe to commit.
 *  - In production on Render (free plan blocks SMTP), set:
 *      GMAIL_RELAY_URL, GMAIL_RELAY_SECRET  (Google Apps Script relay over HTTPS)
 *  - On hosts that allow SMTP, set instead:
 *      SMTP_HOST, SMTP_PORT, SMTP_SECURE (tls|ssl), SMTP_USER, SMTP_PASS,
 *      SMTP_FROM (optional), SMTP_FROM_NAME (optional), MAIL_TO (optional)
 *  - Locally (XAMPP), put your values in includes/mail-config.local.php
 *    (git-ignored). Anything set there overrides the environment.
 *
 * Mail is sent only when a username and password are present.
 */
$env = static fn(string $k, string $default = ''): string => (string)(getenv($k) !== false ? getenv($k) : ($_ENV[$k] ?? $_SERVER[$k] ?? $default));

$config = [
    // Gmail relay via Google Apps Script (HTTPS). Used instead of SMTP when both are set.
    // See deploy/gmail-relay.gs for the script and setup steps.
    'relay_url'    => $env('GMAIL_RELAY_URL'),
    'relay_secret' => $env('GMAIL_RELAY_SECRET'),

    // SMTP via PHPMailer (used locally, or on hosts that allow SMTP)
    'host'       => $env('SMTP_HOST', 'smtp.gmail.com'),
    'port'       => (int)$env('SMTP_PORT', '587'),
    'secure'     => $env('SMTP_SECURE', 'tls'),
    'username'   => $env('SMTP_USER'),
    'password'   => $env('SMTP_PASS'),
    'from_email' => $env('SMTP_FROM'),
    'from_name'  => $env('SMTP_FROM_NAME', 'Vikas Rambaul Giri'),
    'to_email'   => $env('MAIL_TO', 'vikasr.giri@outlook.com'),
    'to_name'    => 'Vikas Rambaul Giri',
    'auto_reply' => $env('MAIL_AUTO_REPLY', '1') !== '0',
    'debug'      => $env('MAIL_DEBUG', '0') === '1',
];

$local = __DIR__ . '/mail-config.local.php';
if (is_file($local)) {
    $override = require $local;
    if (is_array($override)) {
        $config = array_merge($config, array_filter($override, static fn($v) => $v !== '' && $v !== null));
    }
}

return $config;
