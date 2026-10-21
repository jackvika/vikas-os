<?php
/**
 * "Book a Call" helpers: slot validation, booked-slot lookup, calendar links.
 * Slots are exchanged as UTC ISO strings ("2026-10-22T05:30:00Z") and shown in IST.
 */
declare(strict_types=1);

require_once __DIR__ . '/mailer.php';

const BOOKING_TZ = 'Asia/Kolkata';

/**
 * Validate a requested slot start. Returns ['start' => UTC DateTimeImmutable, 'label' => "Wed 22 Oct 2026, 11:00 AM IST"]
 * or null with a human-readable $error.
 */
function booking_parse_slot(string $iso, array $profile, ?string &$error = null): ?array
{
    $utc = new DateTimeZone('UTC');
    $start = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $iso, $utc);
    if (!$start) { $error = 'Please choose a day and a time.'; return null; }

    $ist = $start->setTimezone(new DateTimeZone(BOOKING_TZ));
    $now = new DateTimeImmutable('now', $utc);
    $notice = (int)($profile['call_notice_hours'] ?? 2);

    if ((int)$ist->format('N') >= 6) { $error = 'Calls are available Monday to Friday.'; return null; }
    if (!in_array($ist->format('H:i'), $profile['call_slots'] ?? [], true)) { $error = 'Please choose one of the listed times.'; return null; }
    if ($start < $now->modify("+{$notice} hours")) { $error = "Please choose a time at least {$notice} hours from now."; return null; }
    if ($start > $now->modify('+21 days')) { $error = 'Please choose a time within the next three weeks.'; return null; }

    return ['start' => $start, 'label' => $ist->format('D j M Y, g:i A') . ' IST'];
}

/**
 * Upcoming booked slot starts (UTC ISO strings), read from the Leads sheet via the relay.
 * Cached briefly so opening the app stays fast; pass $fresh = true right before booking.
 */
function booking_busy(bool $fresh = false): array
{
    if (!relay_configured()) { return []; }
    $cache = sys_get_temp_dir() . '/vos_busy_slots.json';
    if (!$fresh && is_file($cache) && filemtime($cache) > time() - 60) {
        $cached = json_decode((string)file_get_contents($cache), true);
        if (is_array($cached)) { return $cached; }
    }
    $res = relay_request(['action' => 'busy'], $err);
    if (!is_array($res) || !isset($res['busy']) || !is_array($res['busy'])) {
        if ($err) { mail_log('Could not load booked slots: ' . $err); }
        return [];
    }
    $busy = array_values(array_filter(array_map('strval', $res['busy'])));
    @file_put_contents($cache, json_encode($busy), LOCK_EX);
    return $busy;
}

/** Forget the cached busy list (after a new booking). */
function booking_busy_forget(): void
{
    @unlink(sys_get_temp_dir() . '/vos_busy_slots.json');
}

/** Google Calendar "add event" link. */
function gcal_link(string $title, DateTimeImmutable $startUtc, int $minutes, string $details): string
{
    $end = $startUtc->modify("+{$minutes} minutes");
    return 'https://calendar.google.com/calendar/render?' . http_build_query([
        'action'  => 'TEMPLATE',
        'text'    => $title,
        'dates'   => $startUtc->format('Ymd\THis\Z') . '/' . $end->format('Ymd\THis\Z'),
        'details' => $details,
        'ctz'     => BOOKING_TZ,
    ], '', '&', PHP_QUERY_RFC3986);
}
