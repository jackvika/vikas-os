<?php
/**
 * Inquiry emails via PHPMailer (manual install: the 3 raw source files).
 *
 * Deliverability notes:
 * - Clean, light, mostly-text layout (no hidden text, no image-heavy design)
 * - Every HTML email has a matching plain-text version
 * - Message-ID uses the sender's real domain (not "localhost")
 * - No X-Mailer header; auto-replies carry RFC 3834 "Auto-Submitted" headers
 * - Links are only included when the site is on a public domain
 */
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

function mail_config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/mail-config.php';
        if (empty($cfg['from_email'])) { $cfg['from_email'] = $cfg['username']; }
    }
    return $cfg;
}

function mail_is_configured(): bool
{
    $c = mail_config();
    return relay_configured() || ($c['username'] !== '' && $c['password'] !== '' && $c['host'] !== '');
}

/** Gmail relay (Google Apps Script web app) is used when URL + secret are set. */
function relay_configured(): bool
{
    $c = mail_config();
    return ($c['relay_url'] ?? '') !== '' && ($c['relay_secret'] ?? '') !== '';
}

/**
 * Send one message through the Gmail relay over HTTPS (port 443, never blocked).
 * Apps Script answers a POST with a 302 to googleusercontent.com, which is fetched with GET.
 */
function relay_send(array $msg, ?string &$error = null): bool
{
    $c = mail_config();
    $payload = json_encode([
        'secret'   => $c['relay_secret'],
        'to'       => $msg['to'][0],
        'replyTo'  => $msg['replyTo'][0],
        'subject'  => $msg['subject'],
        'html'     => $msg['html'],
        'text'     => $msg['text'],
        'fromName' => $c['from_name'],
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($c['relay_url']);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,   // 302 → GET the result (curl's default for 302)
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($body === false) { $error = "Gmail relay unreachable: {$curlErr}"; return false; }
    $json = json_decode((string)$body, true);
    if ($status === 200 && is_array($json) && !empty($json['ok'])) { return true; }

    $reason = is_array($json) ? (string)($json['error'] ?? 'unknown error') : 'unexpected response (HTTP ' . $status . ')';
    if ($reason === 'unauthorized') { $reason = 'unauthorized: GMAIL_RELAY_SECRET does not match RELAY_SECRET in the Apps Script'; }
    elseif (!is_array($json) && stripos((string)$body, '<html') !== false) { $reason .= '. Is the web app deployed with "Who has access: Anyone"?'; }
    $error = 'Gmail relay failed: ' . $reason;
    return false;
}

function mail_log(string $msg): void
{
    $dir = dirname(__DIR__) . '/data';
    if (!is_dir($dir)) { mkdir($dir, 0750, true); }
    file_put_contents($dir . '/mail.log', '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
    error_log('[mail] ' . $msg); // also visible in hosting logs (e.g. Render → Logs)
}

/** True when the site runs on a real public domain (links to localhost look like spam). */
function site_is_public(string $siteUrl): bool
{
    $host = (string)parse_url($siteUrl, PHP_URL_HOST);
    if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.test')) { return false; }
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return (bool)filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
    return str_contains($host, '.');
}

function make_mailer(): PHPMailer
{
    $c = mail_config();
    $m = new PHPMailer(true);
    $m->isSMTP();
    $m->Host       = $c['host'];
    $m->Port       = (int)$c['port'];
    $m->SMTPAuth   = true;
    $m->Username   = $c['username'];
    $m->Password   = $c['password'];
    $m->SMTPSecure = $c['secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $m->Timeout    = 10;  // fail fast if the host blocks SMTP
    $m->CharSet    = PHPMailer::CHARSET_UTF8;
    $m->Encoding   = PHPMailer::ENCODING_QUOTED_PRINTABLE;
    $m->XMailer    = ' ';                                   // don't advertise PHPMailer
    $m->Hostname   = substr(strrchr($c['from_email'], '@'), 1) ?: 'localhost'; // Message-ID domain
    $m->setFrom($c['from_email'], $c['from_name']);
    $m->Sender     = $c['from_email'];                      // envelope sender = authenticated account
    if (!empty($c['debug'])) {
        $m->SMTPDebug   = 2;
        $m->Debugoutput = static fn($str) => mail_log('SMTP: ' . trim($str));
    }
    return $m;
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

/**
 * Professional, light email layout. Table-based with inline styles so it renders
 * consistently in Gmail, Outlook (desktop + web) and Apple Mail.
 */
function email_layout(string $inner, string $footer): string
{
    return '<!DOCTYPE html>'
        . '<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="x-apple-disable-message-reformatting"><title>Vikas Rambaul Giri</title></head>'
        . '<body style="margin:0;padding:0;background-color:#F4F5F7;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F4F5F7;">'
        . '<tr><td align="center" style="padding:32px 16px;">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background-color:#FFFFFF;border:1px solid #E3E5EA;border-radius:8px;">'
        . '<tr><td style="height:4px;background-color:#2B44C4;border-radius:8px 8px 0 0;font-size:0;line-height:0;">&nbsp;</td></tr>'
        . '<tr><td style="padding:28px 36px 8px 36px;font-family:Arial,Helvetica,sans-serif;">'
        . '<p style="margin:0;font-size:17px;font-weight:bold;color:#111827;">Vikas Rambaul Giri</p>'
        . '<p style="margin:2px 0 0 0;font-size:13px;color:#6B7280;">Digital Marketing Specialist &amp; SEO Expert</p>'
        . '</td></tr>'
        . '<tr><td style="padding:0 36px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="border-top:1px solid #E5E7EB;font-size:0;line-height:0;">&nbsp;</td></tr></table></td></tr>'
        . '<tr><td style="padding:20px 36px 32px 36px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:24px;color:#374151;">' . $inner . '</td></tr>'
        . '</table>'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;">'
        . '<tr><td style="padding:16px 36px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;color:#9CA3AF;text-align:center;">' . $footer . '</td></tr>'
        . '</table>'
        . '</td></tr></table></body></html>';
}

function detail_row(string $label, string $valueHtml): string
{
    return '<tr>'
        . '<td style="padding:8px 12px 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#6B7280;width:96px;vertical-align:top;border-bottom:1px solid #F0F1F4;">' . h($label) . '</td>'
        . '<td style="padding:8px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#111827;border-bottom:1px solid #F0F1F4;">' . $valueHtml . '</td>'
        . '</tr>';
}

function button(string $href, string $label): string
{
    return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 4px 0;"><tr>'
        . '<td style="background-color:#2B44C4;border-radius:6px;">'
        . '<a href="' . h($href) . '" style="display:inline-block;padding:11px 22px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:bold;color:#FFFFFF;text-decoration:none;">' . h($label) . '</a>'
        . '</td></tr></table>';
}

function signature(array $p): string
{
    return '<p style="margin:24px 0 0 0;">Best regards,</p>'
        . '<p style="margin:4px 0 0 0;color:#111827;"><strong>Vikas Rambaul Giri</strong><br>'
        . '<span style="font-size:13px;color:#6B7280;">Digital Marketing Specialist &amp; SEO Expert<br>'
        . h($p['phone']) . ' &nbsp;|&nbsp; <a href="mailto:' . h($p['email']) . '" style="color:#2B44C4;text-decoration:none;">' . h($p['email']) . '</a></span></p>';
}

/**
 * Build both messages (notification + auto-reply) once, then deliver them via PHPMailer SMTP.
 */
function build_inquiry_messages(array $lead, array $profile, string $siteUrl): array
{
    $c = mail_config();
    $public = site_is_public($siteUrl);
    $messages = [];

    // 1) Notification to Vikas
    $inner = '<p style="margin:0 0 16px 0;">You have a new inquiry from your portfolio website.</p>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 20px 0;">'
        . detail_row('Name', h($lead['name']))
        . detail_row('Email', '<a href="mailto:' . h($lead['email']) . '" style="color:#2B44C4;text-decoration:none;">' . h($lead['email']) . '</a>')
        . detail_row('Company', h($lead['company'] !== '' ? $lead['company'] : '-'))
        . detail_row('Regarding', h($lead['type']))
        . detail_row('Received', h($lead['date']))
        . '</table>'
        . '<p style="margin:0 0 8px 0;font-size:13px;color:#6B7280;">Message</p>'
        . '<div style="padding:14px 16px;background-color:#F9FAFB;border:1px solid #E5E7EB;border-radius:6px;color:#111827;">' . nl2br(h($lead['message'])) . '</div>'
        . '<p style="margin:20px 0 0 0;font-size:13px;color:#6B7280;">Reply to this email to respond directly to ' . h($lead['name']) . '.</p>';
    $messages['notify'] = [
        'to'      => [$c['to_email'], $c['to_name']],
        'replyTo' => [$lead['email'], $lead['name']],
        'subject' => 'Portfolio inquiry from ' . $lead['name'] . ' (' . $lead['type'] . ')',
        'html'    => email_layout($inner, 'Sent from the contact form on your portfolio website.'),
        'text'    => "You have a new inquiry from your portfolio website.\n\n"
            . "Name: {$lead['name']}\nEmail: {$lead['email']}\nCompany: " . ($lead['company'] !== '' ? $lead['company'] : '-') . "\n"
            . "Regarding: {$lead['type']}\nReceived: {$lead['date']}\n\nMessage:\n{$lead['message']}\n\n"
            . "Reply to this email to respond directly to {$lead['name']}.",
        'headers' => [],
    ];

    // 2) Auto-reply to the sender
    if (!empty($c['auto_reply'])) {
        $first = trim(explode(' ', trim($lead['name']))[0]) ?: $lead['name'];
        $inner = '<p style="margin:0 0 16px 0;">Dear ' . h($first) . ',</p>'
            . '<p style="margin:0 0 16px 0;">Thank you for contacting me regarding a <strong>' . h(strtolower($lead['type'])) . '</strong>. '
            . 'This is a short note to confirm that your message has reached me. I will review it and reply personally within one working day.</p>'
            . '<p style="margin:0 0 16px 0;">For reference, I am a Digital Marketing Specialist with over three years of experience in B2B and export marketing, '
            . 'covering SEO, Google Ads, Meta Ads and lead generation through IndiaMART and Alibaba.com.</p>';
        if ($public) {
            $inner .= '<p style="margin:0 0 8px 0;">My resume is available here:</p>' . button($siteUrl . $profile['resume'], 'View my resume');
        }
        $inner .= '<p style="margin:16px 0 0 0;">If your request is urgent, you can also reach me on ' . h($profile['phone']) . '.</p>'
            . signature($profile);
        $quoted = '<p style="margin:0 0 6px 0;">Your message:</p>'
            . '<p style="margin:0;font-style:italic;">&ldquo;' . nl2br(h(mb_strimwidth($lead['message'], 0, 500, '...'))) . '&rdquo;</p>';

        $messages['reply'] = [
            'to'      => [$lead['email'], $lead['name']],
            'replyTo' => [$c['to_email'], $c['to_name']],
            'subject' => 'Thank you for your message',
            'html'    => email_layout($inner, $quoted . '<p style="margin:12px 0 0 0;">You received this email because you submitted the contact form on Vikas Rambaul Giri\'s portfolio.</p>'),
            'text'    => "Dear {$first},\n\n"
                . "Thank you for contacting me regarding a " . strtolower($lead['type']) . ". This is a short note to confirm that your message has reached me. I will review it and reply personally within one working day.\n\n"
                . "For reference, I am a Digital Marketing Specialist with over three years of experience in B2B and export marketing, covering SEO, Google Ads, Meta Ads and lead generation through IndiaMART and Alibaba.com.\n\n"
                . ($public ? "My resume: {$siteUrl}{$profile['resume']}\n\n" : '')
                . "If your request is urgent, you can also reach me on {$profile['phone']}.\n\n"
                . "Best regards,\nVikas Rambaul Giri\nDigital Marketing Specialist & SEO Expert\n{$profile['phone']} | {$profile['email']}\n\n"
                . "---\nYour message:\n" . mb_strimwidth($lead['message'], 0, 500, '...') . "\n\n"
                . "You received this email because you submitted the contact form on Vikas Rambaul Giri's portfolio.",
            'headers' => ['Auto-Submitted' => 'auto-replied', 'X-Auto-Response-Suppress' => 'All'],
        ];
    }
    return $messages;
}

/**
 * Open one authenticated SMTP connection. Tries the configured port first, then the
 * other standard port (587 STARTTLS <-> 465 SSL). Returns null + a clear reason on failure.
 */
function smtp_open(?string &$error = null): ?PHPMailer
{
    $c = mail_config();
    $attempts = [[(int)$c['port'], $c['secure']]];
    if ((int)$c['port'] === 587) { $attempts[] = [465, 'ssl']; }
    elseif ((int)$c['port'] === 465) { $attempts[] = [587, 'tls']; }

    $reasons = [];
    foreach ($attempts as [$port, $secure]) {
        $m = make_mailer();
        $m->Port = $port;
        $m->SMTPSecure = $secure === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $m->SMTPKeepAlive = true;
        $t = microtime(true);
        try {
            if ($m->smtpConnect()) { return $m; }
            $reasons[] = "{$c['host']}:{$port} refused";
        } catch (Exception $e) {
            $msg = $e->getMessage();
            $secs = round(microtime(true) - $t, 1);
            if (stripos($msg, 'authenticate') !== false) {
                $error = "SMTP login failed for {$c['username']} (wrong SMTP_USER / SMTP_PASS, or the Gmail app password was revoked).";
                return null;
            }
            $reasons[] = "{$c['host']}:{$port} unreachable after {$secs}s ({$msg})";
        }
    }
    $error = 'Could not connect to SMTP: ' . implode('; ', $reasons)
        . '. If this runs on a host that blocks outgoing SMTP (e.g. Render free plan), use an SMTP relay that supports port 2525 or a host that allows SMTP.';
    return null;
}

/**
 * Sends the notification to the site owner and (optionally) an auto-reply to the sender.
 * Returns ['notified' => bool, 'replied' => bool].
 */
function send_inquiry_mails(array $lead, array $profile, string $siteUrl): array
{
    $result = ['notified' => false, 'replied' => false];
    if (!mail_is_configured()) {
        mail_log('Not sent: email is not configured. Set GMAIL_RELAY_URL + GMAIL_RELAY_SECRET, or SMTP_USER + SMTP_PASS (Render → Environment).');
        return $result;
    }
    $messages = build_inquiry_messages($lead, $profile, $siteUrl);
    $key = ['notify' => 'notified', 'reply' => 'replied'];

    // Hosts that block SMTP (Render free): send through the Gmail relay over HTTPS
    if (relay_configured()) {
        foreach ($messages as $k => $msg) {
            $result[$key[$k]] = relay_send($msg, $err);
            if (!$result[$key[$k]]) { mail_log(($k === 'notify' ? 'Notify' : 'Auto-reply') . ' not sent: ' . $err); }
        }
        return $result;
    }

    // SMTP via PHPMailer: connect ONCE (with port fallback), then send both messages
    $m = smtp_open($error);
    if (!$m) {
        mail_log('Not sent: ' . $error);
        return $result;
    }
    foreach ($messages as $k => $msg) {
        try {
            $m->clearAllRecipients();
            $m->clearReplyTos();
            $m->clearCustomHeaders();
            $m->addAddress($msg['to'][0], $msg['to'][1]);
            $m->addReplyTo($msg['replyTo'][0], $msg['replyTo'][1]);
            foreach ($msg['headers'] as $hk => $hv) { $m->addCustomHeader($hk, $hv); }
            $m->Subject = $msg['subject'];
            $m->isHTML(true);
            $m->Body = $msg['html'];
            $m->AltBody = $msg['text'];
            $m->send();
            $result[$key[$k]] = true;
        } catch (Exception $e) {
            mail_log(($k === 'notify' ? 'Notify' : 'Auto-reply') . ' failed: ' . $e->getMessage());
        }
    }
    $m->smtpClose();
    return $result;
}
