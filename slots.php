<?php
/**
 * Booked interview slots for the "Book a Call" app.
 * Returns {"ok":true,"busy":["2026-10-22T05:30:00Z", …]}: upcoming booked starts only,
 * no names or other personal data. Empty when the Gmail relay / Leads sheet is not set up.
 */
declare(strict_types=1);

require __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/booking.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

echo json_encode(['ok' => true, 'busy' => booking_busy()]);
