<?php
/**
 * Private mail diagnostics.
 *
 * Enabled only when the DIAG_TOKEN environment variable is set (e.g. in Render →
 * Environment). Open:  /mail-test.php?token=YOUR_TOKEN          (connectivity check)
 *                      /mail-test.php?token=YOUR_TOKEN&send=1   (also sends a test email)
 * Remove DIAG_TOKEN again when you are done.
 */
declare(strict_types=1);

$token = (string)(getenv('DIAG_TOKEN') ?: '');
if ($token === '' || !hash_equals($token, (string)($_GET['token'] ?? ''))) {
    http_response_code(404);
    exit('Not Found');
}

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');
@set_time_limit(90);

require __DIR__ . '/includes/mailer.php';
date_default_timezone_set('Asia/Kolkata');
$c = mail_config();

$mask = static function (string $v): string {
    if ($v === '') { return '(not set)'; }
    $at = strpos($v, '@');
    return $at === false ? substr($v, 0, 2) . '***' : substr($v, 0, 2) . '***' . substr($v, $at);
};

echo "Vikas OS: mail diagnostics (" . date('d M Y H:i:s T') . ")\n";
echo str_repeat('=', 60) . "\n\n";

echo "CONFIG\n";
echo "  Gmail relay   : " . (relay_configured() ? 'ON (' . preg_replace('#/s/([^/]{6})[^/]*#', '/s/$1…', $c['relay_url']) . ', secret set)' : 'off (set GMAIL_RELAY_URL + GMAIL_RELAY_SECRET)') . "\n";
echo "  Transport     : " . (relay_configured() ? 'Gmail relay over HTTPS' : 'PHPMailer SMTP') . "\n";
echo "  SMTP host     : {$c['host']}:{$c['port']} ({$c['secure']})\n";
echo "  SMTP_USER     : " . $mask($c['username']) . "\n";
echo "  SMTP_PASS     : " . ($c['password'] === '' ? '(not set)' : 'set (' . strlen(str_replace(' ', '', $c['password'])) . ' chars)') . "\n";
echo "  MAIL_TO       : {$c['to_email']}\n";
echo "  Configured    : " . (mail_is_configured() ? 'yes' : 'NO: set SMTP_USER and SMTP_PASS') . "\n\n";

echo "DNS\n";
$ip = gethostbyname($c['host']);
echo "  {$c['host']} -> " . ($ip === $c['host'] ? 'FAILED to resolve' : $ip) . "\n\n";

echo "OUTBOUND CONNECTIVITY (5s timeout each)\n";
$targets = [
    ['api.github.com', 443, 'HTTPS control (should be OPEN)'],
    ['script.google.com', 443, 'Gmail relay (Apps Script)'],
    [$c['host'], 587, 'SMTP STARTTLS'],
    [$c['host'], 465, 'SMTP SSL'],
    ['smtp-relay.brevo.com', 2525, 'SMTP relay alt port 2525'],
    ['mail.smtp2go.com', 2525, 'SMTP relay alt port 2525'],
];
$smtpOpen = false;
foreach ($targets as [$h, $port, $label]) {
    $t = microtime(true);
    $fp = @fsockopen($h, $port, $errno, $errstr, 5);
    $ms = (int)round((microtime(true) - $t) * 1000);
    $ok = (bool)$fp;
    if ($fp) { fclose($fp); }
    if ($ok && $h === $c['host']) { $smtpOpen = true; }
    printf("  %-24s %-5d %-6s %5d ms  %s%s\n", $h, $port, $ok ? 'OPEN' : 'BLOCKED', $ms, $label, $ok ? '' : " [{$errstr}]");
}
echo "\n";

echo "VERDICT\n";
if (relay_configured()) {
    echo "  ✓ Using the Gmail relay over HTTPS (SMTP ports are not needed).\n";
} elseif (!mail_is_configured()) {
    echo "  ✗ Email is not configured. Set GMAIL_RELAY_URL + GMAIL_RELAY_SECRET (or SMTP_USER + SMTP_PASS) in Render → Environment.\n";
} elseif (!$smtpOpen) {
    echo "  ✗ This host blocks outgoing SMTP to {$c['host']} (ports 587/465).\n";
    echo "    PHPMailer cannot reach Gmail from here. Options: an SMTP relay on port 2525\n";
    echo "    (if shown OPEN above), a paid instance, or a host that allows SMTP.\n";
} else {
    echo "  ✓ SMTP port is reachable.\n";
}
echo "\n";

if (isset($_GET['send']) && relay_configured()) {
    echo "TEST EMAIL (Gmail relay)\n";
    $ok = relay_send([
        'to'      => [$c['to_email'], $c['to_name']],
        'replyTo' => [$c['to_email'], $c['to_name']],
        'subject' => 'Vikas OS mail test (Gmail relay)',
        'html'    => '<p>This is a test email from <b>mail-test.php</b> on ' . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'server') . ' at ' . date('d M Y H:i:s') . '.</p><p>If you can read this, the Gmail relay works.</p>',
        'text'    => 'This is a test email from mail-test.php at ' . date('d M Y H:i:s') . '. If you can read this, the Gmail relay works.',
        'headers' => [],
    ], $err);
    echo $ok ? "  ✓ Sent to {$c['to_email']} (check inbox and spam)\n" : "  ✗ {$err}\n";
} elseif (isset($_GET['send'])) {
    echo "TEST EMAIL\n";
    $m = smtp_open($err);
    if (!$m) {
        echo "  ✗ " . $err . "\n";
    } else {
        try {
            $m->addAddress($c['to_email'], $c['to_name']);
            $m->Subject = 'Vikas OS mail test';
            $m->isHTML(false);
            $m->Body = "This is a test email from mail-test.php on " . ($_SERVER['HTTP_HOST'] ?? 'server') . " at " . date('d M Y H:i:s') . ".\nIf you can read this, PHPMailer works on this host.";
            $m->send();
            $m->smtpClose();
            echo "  ✓ Sent to {$c['to_email']} (check inbox and spam)\n";
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            echo "  ✗ Send failed: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "Add &send=1 to the URL to also send a test email to MAIL_TO.\n";
}
