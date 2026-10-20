<?php
/**
 * Contact form handler.
 * Every inquiry is stored in data/leads.csv (a mini CRM you can open in Excel)
 * and emailed via PHPMailer SMTP (see includes/mail-config.php), with a
 * branded auto-reply to the sender.
 */
declare(strict_types=1);
session_start();

$p = require __DIR__ . '/includes/content.php';
$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

function respond(bool $ok, string $error = '', int $code = 200): never {
    global $wantsJson;
    if ($wantsJson) {
        http_response_code($ok ? 200 : $code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($ok ? ['ok' => true] : ['ok' => false, 'error' => $error]);
    } else {
        // No-JS fallback: back to the page
        header('Location: ./' . ($ok ? '?sent=1' : '?error=1') . '#contact', true, 303);
    }
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(false, 'Method not allowed.', 405);
}

// CSRF
if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
    respond(false, 'Your session expired. Please refresh the page and try again.', 400);
}

// Honeypot + minimum fill time: quietly accept bots without storing anything
if (!empty($_POST['website']) || (time() - (int)($_SESSION['form_time'] ?? 0)) < 3) {
    respond(true);
}

// Simple rate limit: max 3 submissions per 10 minutes per session
$now = time();
$_SESSION['sent'] = array_values(array_filter($_SESSION['sent'] ?? [], fn($t) => $t > $now - 600));
if (count($_SESSION['sent']) >= 3) {
    respond(false, 'Too many messages in a short time. Please try again later.', 429);
}

$clean = fn(string $k, int $max) => mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string)($_POST[$k] ?? ''))), 0, $max);
$name    = $clean('name', 80);
$email   = $clean('email', 120);
$company = $clean('company', 120);
$message = $clean('message', 3000);
$type    = $clean('type', 40);
if (!in_array($type, ['Full-time role', 'Freelance project', 'Something else'], true)) { $type = 'Something else'; }

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($message) < 10) {
    respond(false, 'Please fill in your name, a valid email and a message.', 422);
}

// Store the lead
$dir = __DIR__ . '/data';
if (!is_dir($dir)) { mkdir($dir, 0750, true); }
$file = $dir . '/leads.csv';
$isNew = !is_file($file);
$fh = fopen($file, 'ab');
if ($fh === false) {
    respond(false, 'Could not save your message.', 500);
}
flock($fh, LOCK_EX);
if ($isNew) {
    fwrite($fh, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens it correctly
    fputcsv($fh, ['Date', 'Type', 'Name', 'Email', 'Company', 'Message', 'IP']);
}
// Prefix values that spreadsheet apps would treat as formulas
$safe = fn(string $v) => preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
fputcsv($fh, array_map($safe, [date('Y-m-d H:i'), $type, $name, $email, $company, $message, $_SERVER['REMOTE_ADDR'] ?? '']));
flock($fh, LOCK_UN);
fclose($fh);

$_SESSION['sent'][] = $now;
session_write_close();

// Answer the visitor right away, then send the emails in the background
if ($wantsJson) {
    ignore_user_abort(true);
    set_time_limit(60);
    $out = json_encode(['ok' => true]);
    http_response_code(200);
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Length: ' . strlen($out));
    header('Connection: close');
    echo $out;
    while (ob_get_level() > 0) { ob_end_flush(); }
    flush();
    if (function_exists('fastcgi_finish_request')) { fastcgi_finish_request(); }
}

// Email notification + auto-reply via PHPMailer (skipped until SMTP is configured)
require __DIR__ . '/includes/mailer.php';
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$siteUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/';
send_inquiry_mails(
    ['date' => date('d M Y, H:i'), 'type' => $type, 'name' => $name, 'email' => $email, 'company' => $company, 'message' => $message],
    $p,
    $siteUrl
);

if (!$wantsJson) { respond(true); }
