<?php
declare(strict_types=1);
session_start();

$p = require __DIR__ . '/includes/content.php';

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$baseUrl = $scheme . '://' . $host . $path . '/';

if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$_SESSION['form_time'] = time();

$resumePath = __DIR__ . '/' . $p['resume'];
$hasResume  = is_file($resumePath);
$resumeKb   = $hasResume ? max(1, (int)round(filesize($resumePath) / 1024)) : 0;
$resumeDate = $hasResume ? date('M Y', filemtime($resumePath)) : '';
$photo      = is_file(__DIR__ . '/assets/img/photo.jpg') ? 'assets/img/photo.jpg' : '';
$waText     = rawurlencode("Hi Vikas, I came across your portfolio and would like to discuss an opportunity.");

$groups = [];
foreach ($p['skills'] as $s) { $groups[$s[1]][] = $s; }
$groupNames = ['SEO' => 'Search (SEO)', 'Paid' => 'Paid media', 'B2B' => 'B2B & export', 'Analytics' => 'Analytics', 'Web' => 'Web & code', 'Content' => 'Content'];

/* App registry: id => [name, icon key, x%, y%, w, h, desktop icon type, desktop label] */
$apps = [
    'about'      => ['About Me',   'about',   26,  5, 560, 650, 'txt',    'About Me.txt'],
    'results'    => ['Results',    'results', 30,  9, 740, 580, 'folder', 'Results'],
    'experience' => ['Experience', 'exp',     28,  7, 700, 600, 'folder', 'Experience'],
    'skills'     => ['Skills',     'skills',  33, 12, 720, 540, 'folder', 'Skills'],
    'fit'        => ['Fit Check',  'fit',     29,  6, 780, 640, 'app',    'Fit Check'],
    'resume'     => ['Resume.pdf', 'pdf',     35,  5, 700, 680, 'pdfdoc', 'Resume.pdf'],
    'terminal'   => ['Terminal',   'term',    38, 22, 620, 420, 'app',    'Terminal'],
    'mail'       => ['Mail',       'mail',    36, 10, 580, 620, 'app',    'Mail'],
    'call'       => ['Book a Call', 'call',   31,  6, 640, 660, 'app',    'Book a Call'],
];
$icons = [
    'about'   => '<circle cx="12" cy="8.5" r="3.6"/><path d="M5 19.5c1.2-3.6 4-5.2 7-5.2s5.8 1.6 7 5.2"/>',
    'results' => '<path d="M4 19h16"/><path d="M6 16l4-5 3 3 5-7"/><path d="M15 7h3v3"/>',
    'exp'     => '<rect x="3.5" y="7.5" width="17" height="11.5" rx="2"/><path d="M9 7.5V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1.5M3.5 12.5h17"/>',
    'skills'  => '<path d="M5 7h9M18 7h1M5 12h3M12 12h7M5 17h11M20 17h-1"/><circle cx="16" cy="7" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="17" r="2"/>',
    'fit'     => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r="1.2" fill="currentColor"/>',
    'pdf'     => '<path d="M7 3.5h7l4 4V20a.5.5 0 0 1-.5.5h-10A.5.5 0 0 1 7 20z"/><path d="M14 3.5V8h4M9.5 13h5M9.5 16h5"/>',
    'term'    => '<rect x="3.5" y="5" width="17" height="14" rx="2"/><path d="m7.5 10 2.5 2-2.5 2M12.5 14.5h4"/>',
    'mail'    => '<rect x="3.5" y="5.5" width="17" height="13" rx="2"/><path d="m4.5 7 7.5 6 7.5-6"/>',
    'call'    => '<rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/><path d="m9.3 14.6 2 2 3.6-3.6"/>',
];
function icon(string $k, array $icons): string {
    return '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[$k] . '</svg>';
}
/** "06/2023 — 11/2025" or "08/2026 — Present" → "2 yrs 6 mos" (months counted inclusively, like LinkedIn). */
function duration(string $period): string {
    if (!preg_match('#(\d{2})/(\d{4})\s*[—–-]\s*(?:(\d{2})/(\d{4})|Present)#u', $period, $m)) { return ''; }
    $endY = isset($m[4]) && $m[4] !== '' ? (int)$m[4] : (int)date('Y');
    $endM = isset($m[3]) && $m[3] !== '' ? (int)$m[3] : (int)date('n');
    $months = ($endY - (int)$m[2]) * 12 + ($endM - (int)$m[1]) + 1;
    if ($months < 1) { return ''; }
    $y = intdiv($months, 12); $mo = $months % 12;
    return trim(($y ? $y . ' yr' . ($y > 1 ? 's' : '') : '') . ' ' . ($mo ? $mo . ' mo' . ($mo > 1 ? 's' : '') : ''));
}

/* Realistic desktop icons: folders (with emblem), documents, app tiles */
function deskIcon(array $a, array $icons): string {
    switch ($a[6]) {
        case 'folder':
            return '<span class="fico fico--folder"><svg viewBox="0 0 64 56" aria-hidden="true">'
                . '<path d="M4 8a4 4 0 0 1 4-4h15.5a4 4 0 0 1 2.9 1.2L30 9h26a4 4 0 0 1 4 4v4H4z" fill="url(#g-folder-back)"/>'
                . '<rect x="4" y="13" width="56" height="39" rx="4" fill="url(#g-folder)"/>'
                . '<rect x="4" y="13" width="56" height="2" fill="#fff" opacity=".35"/>'
                . '</svg><span class="fico__emblem">' . icon($a[1], $icons) . '</span></span>';
        case 'txt':
            return '<span class="fico fico--doc"><svg viewBox="0 0 48 60" aria-hidden="true">'
                . '<path d="M4 3h28l12 12v40a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" fill="#FFFFFF" stroke="#D5DAE0"/>'
                . '<path d="M32 3v10a2 2 0 0 0 2 2h10" fill="#EEF1F4" stroke="#D5DAE0"/>'
                . '<g stroke="#B9C1CB" stroke-width="2" stroke-linecap="round"><path d="M11 24h26M11 30h26M11 36h26M11 42h18"/></g>'
                . '</svg><span class="fico__badge">TXT</span></span>';
        case 'pdfdoc':
            return '<span class="fico fico--doc"><svg viewBox="0 0 48 60" aria-hidden="true">'
                . '<path d="M4 3h28l12 12v40a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" fill="#FFFFFF" stroke="#D5DAE0"/>'
                . '<path d="M32 3v10a2 2 0 0 0 2 2h10" fill="#EEF1F4" stroke="#D5DAE0"/>'
                . '<g stroke="#C9D0D8" stroke-width="2" stroke-linecap="round"><path d="M11 22h26M11 27h20"/></g>'
                . '<rect x="0" y="34" width="34" height="14" rx="3" fill="#E5483A"/><text x="17" y="44.5" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="9.5" font-weight="700" fill="#fff">PDF</text>'
                . '</svg></span>';
        default:
            return '<span class="appicon appicon--' . htmlspecialchars($a[1]) . '">' . icon($a[1], $icons) . '</span>';
    }
}

$termData = [
    'name' => $p['name'], 'title' => $p['title'], 'email' => $p['email'], 'phone' => $p['phone'],
    'location' => $p['location'], 'notice' => $p['notice'],
    'experience' => array_map(fn($x) => [$x['period'], $x['role'], $x['company']], $p['experience']),
    'skills' => array_map(fn($g) => array_map(fn($s) => $s[0], $g), $groups),
    'results' => array_map(fn($c) => [$c['metric'][0], $c['title']], $p['cases']),
    'resume' => $hasResume ? $p['resume'] : '',
    'call' => [
        'slots'   => $p['call_slots'] ?? [],
        'days'    => (int)($p['call_days'] ?? 10),
        'notice'  => (int)($p['call_notice_hours'] ?? 2),
        'minutes' => (int)($p['call_minutes'] ?? 30),
    ],
];
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($p['name']) ?> — <?= e($p['title']) ?> | Vikas OS</title>
  <meta name="description" content="<?= e($p['name']) ?> is a Digital Marketing Specialist &amp; SEO Expert with 3+ years in B2B and export marketing: SEO, Google Ads, Meta Ads, IndiaMART, Alibaba.com and lead generation. 1-month notice period.">
  <meta name="author" content="<?= e($p['name']) ?>">
  <meta name="robots" content="index, follow, max-image-preview:large">
  <link rel="canonical" href="<?= e($baseUrl) ?>">
  <meta name="theme-color" content="#DCE6EA">

  <meta property="og:type" content="profile">
  <meta property="og:site_name" content="<?= e($p['name']) ?>">
  <meta property="og:title" content="Vikas OS: the portfolio of <?= e($p['name']) ?>">
  <meta property="og:description" content="A portfolio you can boot. 3+ years in B2B &amp; export marketing: SEO, Google &amp; Meta Ads, IndiaMART, Alibaba.com. +200% organic traffic.">
  <meta property="og:url" content="<?= e($baseUrl) ?>">
  <meta property="og:image" content="<?= e($baseUrl) ?>assets/img/og-image.png">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:image" content="<?= e($baseUrl) ?>assets/img/og-image.png">

  <meta property="og:image:alt" content="Vikas OS: About Me window with +200% organic traffic, 3+ years in B2B &amp; export marketing">

  <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
  <link rel="manifest" href="site.webmanifest">
  <meta name="apple-mobile-web-app-title" content="Vikas OS">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css?v=91">
  <script>
    document.documentElement.classList.add('js');
    try {
      var t = localStorage.getItem('theme');
      if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.setAttribute('data-theme', 'dark');
      if (localStorage.getItem('quick') === '1') document.documentElement.classList.add('quick');
      var w = localStorage.getItem('wallpaper'); if (w) document.documentElement.setAttribute('data-wp', w);
    } catch (e) {}
  </script>
  <noscript>
    <style>
      /* Without JavaScript: skip the boot screen and show every window as a readable page */
      .boot, .icons, .widgets, .dock, .win__lights, .win__back { display: none !important; }
      body { overflow: auto; }
      .desktop { position: relative; overflow: visible; display: flex; flex-direction: column; min-height: 100%; padding: calc(var(--mb-total) + 24px) 16px 60px; }
      .wallpaper { position: fixed; }
      .windows { position: relative; inset: auto; pointer-events: auto; display: flex; flex-direction: column; gap: 18px; max-width: 860px; width: 100%; margin: 0 auto; }
      .win, .win[hidden] { display: flex !important; position: relative !important; left: auto !important; top: auto !important; width: 100% !important; height: auto !important; z-index: auto !important; animation: none; }
      .win__body { overflow: visible; }
    </style>
  </noscript>

  <script type="application/ld+json">
  <?= json_encode([
      '@context' => 'https://schema.org',
      '@graph' => [
          [
              '@type' => 'Person',
              '@id' => $baseUrl . '#person',
              'name' => $p['name'],
              'jobTitle' => $p['title'],
              'url' => $baseUrl,
              'image' => $baseUrl . ($photo ?: 'assets/img/og-image.png'),
              'email' => 'mailto:' . $p['email'],
              'telephone' => '+' . $p['phone_raw'],
              'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Umbergaon, Valsad', 'addressRegion' => 'Gujarat', 'postalCode' => '396165', 'addressCountry' => 'IN'],
              'alumniOf' => ['@type' => 'CollegeOrUniversity', 'name' => 'University of Mumbai'],
              'worksFor' => ['@type' => 'Organization', 'name' => 'Arizone International'],
              'knowsAbout' => array_column($p['skills'], 0),
              'sameAs' => array_values(array_filter([$p['linkedin']])),
          ],
          ['@type' => 'ProfilePage', '@id' => $baseUrl . '#webpage', 'url' => $baseUrl, 'name' => $p['name'] . ' — ' . $p['title'], 'mainEntity' => ['@id' => $baseUrl . '#person'], 'inLanguage' => 'en'],
      ],
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
  </script>
</head>
<body>
<a class="skip" href="#win-about" data-open="about">Skip to About Me</a>

<!-- ================= BOOT ================= -->
<div class="boot" data-boot aria-hidden="true">
  <div class="boot__logo"><span></span></div>
  <p class="boot__name">Vikas OS</p>
  <div class="boot__bar"><i data-boot-bar></i></div>
</div>

<!-- ================= MENU BAR ================= -->
<header class="menubar" data-menubar>
  <div class="menubar__left">
    <div class="menu">
      <button type="button" class="menu__btn menu__btn--logo" aria-haspopup="true" aria-expanded="false" aria-label="Vikas OS menu"><span class="os-logo" aria-hidden="true"></span></button>
      <div class="menu__list" role="menu" hidden>
        <button role="menuitem" data-open="about">About this Vikas</button>
        <button role="menuitem" data-action="quick">Quick view (one page)</button>
        <hr>
        <button role="menuitem" data-action="restart">Restart…</button>
      </div>
    </div>
    <span class="mb-app" data-active-app>Finder</span>
    <div class="menu">
      <button type="button" class="menu__btn" aria-haspopup="true" aria-expanded="false">File</button>
      <div class="menu__list" role="menu" hidden>
        <button role="menuitem" data-open="resume">Open Resume.pdf</button>
        <?php if ($hasResume): ?><a role="menuitem" href="<?= e($p['resume']) ?>" download>Download CV (PDF)</a><?php endif; ?>
        <hr>
        <button role="menuitem" data-open="results">Open Results</button>
        <button role="menuitem" data-open="experience">Open Experience</button>
      </div>
    </div>
    <div class="menu">
      <button type="button" class="menu__btn" aria-haspopup="true" aria-expanded="false">Hire</button>
      <div class="menu__list" role="menu" hidden>
        <button role="menuitem" data-open="call">Book an interview call…</button>
        <button role="menuitem" data-open="mail">Send a message…</button>
        <button role="menuitem" data-copy="<?= e($p['email']) ?>">Copy email address</button>
        <a role="menuitem" href="https://wa.me/<?= e($p['phone_raw']) ?>?text=<?= $waText ?>" target="_blank" rel="noopener">WhatsApp Vikas</a>
        <a role="menuitem" href="tel:+<?= e($p['phone_raw']) ?>">Call <?= e($p['phone']) ?></a>
        <hr>
        <button role="menuitem" data-open="fit">Check fit against a JD…</button>
      </div>
    </div>
    <div class="menu">
      <button type="button" class="menu__btn" aria-haspopup="true" aria-expanded="false">View</button>
      <div class="menu__list" role="menu" hidden>
        <button role="menuitem" data-action="theme">Toggle dark mode</button>
        <button role="menuitem" data-action="quick">Quick view (one page)</button>
        <button role="menuitem" data-action="closeall">Close all windows</button>
      </div>
    </div>
  </div>
  <div class="menubar__right">
    <span class="status-pill"><i class="dot"></i>Open to work</span>
    <button type="button" class="quick-btn" data-action="quick"><span data-quick-label>Quick view</span></button>
    <button type="button" class="mb-icon" data-action="theme" aria-label="Toggle dark mode">
      <svg class="i-moon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5Z" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"/></svg>
      <svg class="i-sun" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><circle cx="12" cy="12" r="4.2" fill="none" stroke="currentColor" stroke-width="1.9"/><path d="M12 2.5v2.2M12 19.3v2.2M2.5 12h2.2M19.3 12h2.2M5.3 5.3l1.6 1.6M17.1 17.1l1.6 1.6M5.3 18.7l1.6-1.6M17.1 6.9l1.6-1.6" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>
    </button>
    <span class="mb-status" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="17" height="17"><path d="M2.5 9a14 14 0 0 1 19 0M5.5 12.3a9.5 9.5 0 0 1 13 0M8.6 15.5a5 5 0 0 1 6.8 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="18.6" r="1.4" fill="currentColor"/></svg>
      <span class="battery"><i></i></span>
    </span>
    <button type="button" class="mb-icon" data-action="spotlight" aria-label="Search (Ctrl+Space)">
      <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
    </button>
    <span class="clock" data-clock>--:--</span>
  </div>
</header>

<!-- ================= SPOTLIGHT ================= -->
<div class="spotlight" data-spotlight hidden>
  <div class="spotlight__box" role="dialog" aria-modal="true" aria-label="Spotlight search">
    <div class="spotlight__input">
      <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="2"/><path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      <input type="text" placeholder="Spotlight Search" data-spot-in aria-label="Search apps and actions" autocomplete="off" spellcheck="false">
    </div>
    <ul class="spotlight__list" data-spot-list role="listbox">
      <?php foreach ($apps as $id => $a): ?>
        <li role="option" data-spot-open="<?= e($id) ?>" data-spot-text="<?= e($a[7] . ' ' . $a[0]) ?>"><span class="spot-ico appicon--<?= e($a[1]) ?>"><?= icon($a[1], $icons) ?></span><span><?= e($a[7]) ?></span><small>Application</small></li>
      <?php endforeach; ?>
      <?php if ($hasResume): ?><li role="option" data-spot-href="<?= e($p['resume']) ?>" data-spot-text="download cv resume pdf"><span class="spot-ico appicon--dl"><?= icon('pdf', $icons) ?></span><span>Download CV</span><small>Action</small></li><?php endif; ?>
      <li role="option" data-spot-copy="<?= e($p['email']) ?>" data-spot-text="copy email contact mail"><span class="spot-ico appicon--mail"><?= icon('mail', $icons) ?></span><span>Copy email address</span><small><?= e($p['email']) ?></small></li>
      <li role="option" data-spot-action="quick" data-spot-text="quick view one page"><span class="spot-ico appicon--about"><?= icon('results', $icons) ?></span><span>Quick view</span><small>Action</small></li>
      <li role="option" data-spot-action="theme" data-spot-text="dark mode theme light night"><span class="spot-ico appicon--term"><?= icon('term', $icons) ?></span><span>Toggle dark mode</span><small>Action</small></li>
    </ul>
  </div>
</div>

<!-- ================= CONTEXT MENU ================= -->
<div class="ctx" data-ctx role="menu" hidden>
  <button role="menuitem" data-open="mail">New Message to Vikas…</button>
  <?php if ($hasResume): ?><a role="menuitem" href="<?= e($p['resume']) ?>" download>Download CV</a><?php endif; ?>
  <button role="menuitem" data-open="about">Get Info</button>
  <hr>
  <button role="menuitem" data-action="wallpaper">Change Wallpaper</button>
  <button role="menuitem" data-action="theme">Toggle Dark Mode</button>
  <button role="menuitem" data-action="quick">Quick View</button>
  <hr>
  <button role="menuitem" data-action="closeall">Clean Up (close windows)</button>
</div>

<!-- ================= DESKTOP ================= -->
<main class="desktop" id="main" data-desktop>
  <div class="wallpaper" aria-hidden="true">
    <svg class="wp-svg" viewBox="0 0 1600 1000" preserveAspectRatio="xMidYMid slice">
      <defs>
        <linearGradient id="wp-sky" x1="0" y1="0" x2="0" y2="1"><stop offset="0" style="stop-color:var(--sky1)"/><stop offset=".55" style="stop-color:var(--sky2)"/><stop offset="1" style="stop-color:var(--sky3)"/></linearGradient>
        <radialGradient id="wp-glow"><stop offset="0" style="stop-color:var(--sun);stop-opacity:.9"/><stop offset="1" style="stop-color:var(--sun);stop-opacity:0"/></radialGradient>
        <linearGradient id="wp-haze" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset="1" style="stop-color:var(--haze)" stop-opacity=".55"/></linearGradient>
      </defs>
      <rect width="1600" height="1000" fill="url(#wp-sky)"/>
      <g class="wp-starfield" style="fill:#fff">
        <circle cx="140" cy="90" r="1.3"/><circle cx="320" cy="160" r="1"/><circle cx="520" cy="70" r="1.5"/><circle cx="690" cy="210" r="1"/><circle cx="860" cy="110" r="1.2"/><circle cx="980" cy="60" r="1"/><circle cx="1260" cy="140" r="1.4"/><circle cx="1420" cy="80" r="1"/><circle cx="1520" cy="220" r="1.2"/><circle cx="240" cy="280" r="1"/><circle cx="430" cy="330" r="1.2"/><circle cx="760" cy="300" r="1"/><circle cx="1360" cy="320" r="1"/><circle cx="60" cy="380" r="1.1"/>
      </g>
      <circle cx="1130" cy="330" r="300" fill="url(#wp-glow)"/>
      <circle cx="1130" cy="330" r="64" style="fill:var(--sun-core)"/>
      <path style="fill:var(--h1)" d="M0 560 C 180 470 360 520 560 465 C 760 410 930 505 1110 455 C 1300 402 1450 470 1600 430 V1000 H0Z"/>
      <path style="fill:var(--h2)" d="M0 650 C 230 585 430 640 650 598 C 870 556 1050 640 1270 598 C 1430 568 1520 602 1600 590 V1000 H0Z"/>
      <rect y="420" width="1600" height="320" fill="url(#wp-haze)"/>
      <path style="fill:var(--h3)" d="M0 760 C 260 700 490 762 730 720 C 970 678 1190 760 1410 720 C 1510 703 1565 711 1600 707 V1000 H0Z"/>
      <path style="fill:var(--h4)" d="M0 872 C 300 820 560 884 830 852 C 1090 820 1330 884 1600 842 V1000 H0Z"/>
    </svg>
  </div>

  <!-- Shared gradients for folder icons -->
  <svg width="0" height="0" style="position:absolute" aria-hidden="true">
    <defs>
      <linearGradient id="g-folder" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#8CC8FF"/><stop offset="1" stop-color="#4C9BF0"/></linearGradient>
      <linearGradient id="g-folder-back" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#6AB0F5"/><stop offset="1" stop-color="#3F86DA"/></linearGradient>
    </defs>
  </svg>

  <!-- Desktop icons (single click opens) -->
  <nav class="icons" aria-label="Desktop">
    <?php foreach ($apps as $id => $a): ?>
      <button type="button" class="dicon" data-open="<?= e($id) ?>" aria-label="Open <?= e($a[7]) ?>">
        <?= deskIcon($a, $icons) ?>
        <span class="dicon__label"><?= e($a[7]) ?></span>
      </button>
    <?php endforeach; ?>
  </nav>
  <div class="band" data-band hidden></div>

  <!-- Widgets -->
  <aside class="widgets" aria-label="Widgets">
    <div class="widget widget--note">
      <p class="note__title">Recruiter? Start here</p>
      <ol>
        <li><button type="button" data-open="about">Read “About Me”</button></li>
        <li><button type="button" data-open="fit">Paste your JD in “Fit Check”</button></li>
        <li><button type="button" data-open="resume">Open “Resume.pdf”</button></li>
        <li><button type="button" data-open="call">Book a call</button> or <button type="button" data-open="mail">say hi via “Mail”</button></li>
      </ol>
      <p class="note__foot">In a hurry? <button type="button" data-action="quick">Quick view →</button></p>
    </div>
    <div class="widget widget--stats">
      <p class="widget__label">At a glance</p>
      <ul>
        <li><strong>+200%</strong><span>organic traffic</span></li>
        <li><strong>3+ yrs</strong><span>B2B &amp; export</span></li>
        <li><strong>3</strong><span>B2B portals</span></li>
      </ul>
    </div>
    <div class="widget widget--cal" data-cal>
      <div class="cal__head"><p class="cal__month" data-cal-month></p><p class="cal__today" data-cal-day></p></div>
      <div class="cal__grid" data-cal-grid></div>
      <p class="cal__foot"><i class="dot"></i>Free for an interview call · <button type="button" data-open="call">book</button></p>
    </div>
    <div class="widget widget--status">
      <p class="widget__label">Availability</p>
      <p class="widget__big"><i class="dot"></i>1-month notice</p>
      <p class="widget__sub">Umbergaon, Valsad · Gujarat</p>
      <p class="widget__sub" data-ist>India time --:--</p>
    </div>
  </aside>

  <!-- ================= WINDOWS ================= -->
  <div class="windows" data-windows>

    <!-- ABOUT -->
    <section class="win" id="win-about" data-app="about" data-geo="<?= implode(',', array_slice($apps['about'], 2)) ?>" role="dialog" aria-label="About Me" tabindex="-1" hidden>
      <header class="win__bar" data-drag>
        <div class="win__lights"><button type="button" class="tl tl--close" data-close aria-label="Close About Me"></button><button type="button" class="tl tl--min" data-min aria-label="Minimise"></button><button type="button" class="tl tl--max" data-max aria-label="Maximise"></button></div>
        <p class="win__title"><span class="win__ico appicon--about"><?= icon('about', $icons) ?></span>About Me</p>
        <button type="button" class="win__back" data-close>‹ Home</button>
      </header>
      <div class="win__body about">
        <div class="about__hero">
          <div class="about__avatar"><?php if ($photo): ?><img src="<?= e($photo) ?>" alt="<?= e($p['name']) ?>" width="96" height="96"><?php else: ?><span>VG</span><?php endif; ?></div>
          <div>
            <h1 class="about__name"><?= e($p['name']) ?></h1>
            <p class="about__title"><?= e($p['title']) ?></p>
            <p class="about__status"><i class="dot"></i>Open to new roles · <?= e($p['notice']) ?></p>
          </div>
        </div>
        <p class="about__bio">Hi! I'm Vikas. I help <strong>B2B and export businesses</strong> get found on Google, win inquiries on <strong>IndiaMART and Alibaba.com</strong>, and follow every lead through CRM. I also build the landing pages that convert.</p>
        <ul class="about__stats">
          <li><strong>+200%</strong><span>organic traffic</span></li>
          <li><strong>3+ yrs</strong><span>B2B &amp; export</span></li>
          <li><strong>3</strong><span>B2B portals run</span></li>
        </ul>
        <dl class="about__facts">
          <div><dt>Current role</dt><dd>Digital Marketing Executive, Arizone International</dd></div>
          <div><dt>Location</dt><dd>Umbergaon, Valsad, Gujarat, India</dd></div>
          <div><dt>Education</dt><dd>B.Sc. IT, University of Mumbai (2021)</dd></div>
          <div><dt>Core stack</dt><dd>SEO · Google Ads · Meta Ads · IndiaMART · Alibaba.com · CRM</dd></div>
        </dl>
        <h2 class="about__h">Why hire me</h2>
        <ol class="why">
          <li><strong>I know B2B &amp; export buyers.</strong> Industrial products, OEM/ODM inquiries and marketplace leads are my everyday work.</li>
          <li><strong>I own the whole funnel.</strong> SEO, Google &amp; Meta Ads, landing pages and CRM follow-up, so no lead falls between teams.</li>
          <li><strong>I'm technical.</strong> Website migrations, index clean-ups and landing pages don't have to wait for a developer.</li>
        </ol>
        <p class="about__fit"><span>Best fit for</span> SEO Executive · Digital Marketing Executive / Specialist · B2B &amp; Export Marketing</p>
        <div class="about__actions">
          <?php if ($hasResume): ?><a class="btn btn--primary" href="<?= e($p['resume']) ?>" download>Download CV</a><?php endif; ?>
          <button type="button" class="btn btn--ghost" data-open="fit">Check fit for your role</button>
          <button type="button" class="btn btn--ghost" data-open="mail">Contact</button>
        </div>
      </div>
    </section>

    <!-- RESULTS -->
    <section class="win" id="win-results" data-app="results" data-geo="<?= implode(',', array_slice($apps['results'], 2)) ?>" role="dialog" aria-label="Results" tabindex="-1" hidden>
      <header class="win__bar" data-drag>
        <div class="win__lights"><button type="button" class="tl tl--close" data-close aria-label="Close Results"></button><button type="button" class="tl tl--min" data-min aria-label="Minimise"></button><button type="button" class="tl tl--max" data-max aria-label="Maximise"></button></div>
        <p class="win__title"><span class="win__ico appicon--results"><?= icon('results', $icons) ?></span>Results</p>
        <button type="button" class="win__back" data-close>‹ Home</button>
      </header>
      <div class="win__body">
        <h2 class="win__h">Selected results</h2>
        <div class="cases">
          <?php foreach ($p['cases'] as $i => $c): ?>
            <article class="case<?= $i === 0 ? ' case--big' : '' ?>">
              <p class="case__client"><?= e($c['client']) ?> · <?= e($c['period']) ?></p>
              <p class="case__metric"><?= e($c['metric'][0]) ?></p>
              <p class="case__sub"><?= e($c['metric'][1]) ?></p>
              <h3 class="case__title"><?= e($c['title']) ?></h3>
              <?php if ($i === 0): ?>
                <div class="bars" aria-hidden="true"><?php foreach ([20, 26, 31, 38, 45, 54, 63, 74, 86, 100] as $k => $h): ?><i style="--h:<?= $h ?>%;--k:<?= $k ?>"></i><?php endforeach; ?></div>
              <?php endif; ?>
              <details class="case__more"><summary>How I did it</summary>
                <p><?= e($c['challenge']) ?></p>
                <ul><?php foreach ($c['actions'] as $a): ?><li><?= e($a) ?></li><?php endforeach; ?></ul>
              </details>
              <ul class="chips"><?php foreach ($c['tags'] as $t): ?><li><?= e($t) ?></li><?php endforeach; ?></ul>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- EXPERIENCE -->
    <section class="win" id="win-experience" data-app="experience" data-geo="<?= implode(',', array_slice($apps['experience'], 2)) ?>" role="dialog" aria-label="Experience" tabindex="-1" hidden>
      <header class="win__bar" data-drag>
        <div class="win__lights"><button type="button" class="tl tl--close" data-close aria-label="Close Experience"></button><button type="button" class="tl tl--min" data-min aria-label="Minimise"></button><button type="button" class="tl tl--max" data-max aria-label="Maximise"></button></div>
        <p class="win__title"><span class="win__ico appicon--exp"><?= icon('exp', $icons) ?></span>Experience</p>
        <button type="button" class="win__back" data-close>‹ Home</button>
      </header>
      <div class="win__body">
        <h2 class="win__h">Experience</h2>
        <ol class="timeline">
          <?php foreach ($p['experience'] as $x): ?>
            <li class="job<?= $x['current'] ? ' job--now' : '' ?>">
              <div class="job__top"><span class="job__date"><?= e($x['period']) ?><?php if ($d = duration($x['period'])): ?> <span class="job__dur">· <?= e($d) ?></span><?php endif; ?></span><?php if ($x['current']): ?><span class="badge">Current</span><?php else: ?><span class="badge badge--muted"><?= e($x['type']) ?></span><?php endif; ?></div>
              <h3 class="job__role"><?= e($x['role']) ?></h3>
              <p class="job__co"><?= e($x['company']) ?></p>
              <ul class="job__pts"><?php foreach ($x['points'] as $pt): ?><li><?= e($pt) ?></li><?php endforeach; ?></ul>
            </li>
          <?php endforeach; ?>
          <li class="job job--edu">
            <div class="job__top"><span class="job__date">06/2018 — 06/2021</span><span class="badge badge--muted">Education</span></div>
            <h3 class="job__role"><?= e($p['education']['degree']) ?></h3>
            <p class="job__co"><?= e($p['education']['school']) ?></p>
          </li>
        </ol>
      </div>
    </section>

    <!-- SKILLS (settings-style) -->
    <section class="win" id="win-skills" data-app="skills" data-geo="<?= implode(',', array_slice($apps['skills'], 2)) ?>" role="dialog" aria-label="Skills" tabindex="-1" hidden>
      <header class="win__bar" data-drag>
        <div class="win__lights"><button type="button" class="tl tl--close" data-close aria-label="Close Skills"></button><button type="button" class="tl tl--min" data-min aria-label="Minimise"></button><button type="button" class="tl tl--max" data-max aria-label="Maximise"></button></div>
        <p class="win__title"><span class="win__ico appicon--skills"><?= icon('skills', $icons) ?></span>Skills</p>
        <button type="button" class="win__back" data-close>‹ Home</button>
      </header>
      <div class="win__body win__body--split" data-skilltabs>
        <nav class="settings__nav" role="tablist" aria-label="Skill categories">
          <?php $gi = 0; foreach ($groups as $cat => $items): ?>
            <button type="button" role="tab" aria-selected="<?= $gi === 0 ? 'true' : 'false' ?>" data-skilltab="<?= $gi ?>"><?= e($groupNames[$cat] ?? $cat) ?><span><?= count($items) ?></span></button>
          <?php $gi++; endforeach; ?>
        </nav>
        <div class="settings__pane">
          <?php $gi = 0; foreach ($groups as $cat => $items): ?>
            <div class="settings__group" data-skillpane="<?= $gi ?>" <?= $gi ? 'hidden' : '' ?>>
              <h2 class="win__h"><?= e($groupNames[$cat] ?? $cat) ?></h2>
              <ul class="setlist">
                <?php foreach ($items as $s): ?>
                  <li><div><strong><?= e($s[0]) ?></strong><small><?= e($s[3]) ?></small></div><span class="setlist__where"><?= e($s[2]) ?></span><span class="toggle is-on" aria-label="Enabled"></span></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php $gi++; endforeach; ?>
        </div>
      </div>
    </section>

    <!-- FIT CHECK -->
    <section class="win" id="win-fit" data-app="fit" data-geo="<?= implode(',', array_slice($apps['fit'], 2)) ?>" role="dialog" aria-label="Fit Check" tabindex="-1" hidden>
      <header class="win__bar" data-drag>
        <div class="win__lights"><button type="button" class="tl tl--close" data-close aria-label="Close Fit Check"></button><button type="button" class="tl tl--min" data-min aria-label="Minimise"></button><button type="button" class="tl tl--max" data-max aria-label="Maximise"></button></div>
        <p class="win__title"><span class="win__ico appicon--fit"><?= icon('fit', $icons) ?></span>Fit Check</p>
        <button type="button" class="win__back" data-close>‹ Home</button>
      </header>
      <div class="win__body">
        <h2 class="win__h">Does Vikas fit your role?</h2>
        <p class="muted">Paste a job description or pick an example. You'll see what matches, with evidence, and what's honestly new to him.</p>
        <div class="matcher" data-matcher>
          <div class="presets" role="group" aria-label="Example roles">
            <button type="button" data-preset="seo">SEO Executive</button>
            <button type="button" data-preset="perf">Performance Marketer</button>
            <button type="button" data-preset="b2b">B2B / Export Marketing</button>
            <button type="button" data-preset="dm">Digital Marketing Manager</button>
          </div>
          <label class="sr-only" for="jd">Job description</label>
          <textarea id="jd" rows="6" placeholder="Paste the job description here…" data-jd></textarea>
          <div class="matcher__bar">
            <span class="muted small">Runs privately in your browser. Nothing is sent or stored.</span>
            <button type="button" class="btn btn--primary" data-analyze>Check the fit</button>
          </div>
          <div class="result" data-result hidden>
            <div class="result__score">
              <div class="ring">
                <svg viewBox="0 0 120 120" aria-hidden="true">
                  <defs><linearGradient id="ring-g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#22B07D"/><stop offset=".55" stop-color="#3D7BF2"/><stop offset="1" stop-color="#8A5CF6"/></linearGradient></defs>
                  <circle cx="60" cy="60" r="52" class="ring__bg"/>
                  <circle cx="60" cy="60" r="52" class="ring__fg" data-ring/>
                </svg>
                <div class="ring__num"><strong data-score>0</strong><span>% fit</span></div>
              </div>
              <div><p class="result__verdict" data-verdict></p><p class="muted small" data-summary></p></div>
            </div>
            <div class="result__lists">
              <div><p class="result__label result__label--ok">Matches, with evidence</p><ul class="hits" data-hits></ul></div>
              <div><p class="result__label result__label--gap">New to him (happy to learn)</p><ul class="gaps" data-gaps></ul></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- RESUME.PDF -->
    <section class="win" id="win-resume" data-app="resume" data-geo="<?= implode(',', array_slice($apps['resume'], 2)) ?>" role="dialog" aria-label="Resume" tabindex="-1" hidden>
      <header class="win__bar" data-drag>
        <div class="win__lights"><button type="button" class="tl tl--close" data-close aria-label="Close Resume"></button><button type="button" class="tl tl--min" data-min aria-label="Minimise"></button><button type="button" class="tl tl--max" data-max aria-label="Maximise"></button></div>
        <p class="win__title"><span class="win__ico appicon--pdf"><?= icon('pdf', $icons) ?></span>Resume.pdf</p>
        <button type="button" class="win__back" data-close>‹ Home</button>
      </header>
      <div class="pdfbar">
        <span class="muted small"><?= $hasResume ? e($resumeKb . ' KB · 2 pages · updated ' . $resumeDate) : 'Resume' ?></span>
        <div class="pdfbar__actions">
          <?php if ($hasResume): ?>
            <button type="button" class="btn btn--ghost btn--xs" data-copy="<?= e($baseUrl . $p['resume']) ?>">Copy link</button>
            <a class="btn btn--ghost btn--xs" href="<?= e($p['resume']) ?>" target="_blank" rel="noopener">Open</a>
            <a class="btn btn--primary btn--xs" href="<?= e($p['resume']) ?>" download>Download</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="win__body win__body--pdf">
        <article class="paper" aria-label="Resume of <?= e($p['name']) ?>">
          <header class="paper__head">
            <h3 class="paper__name"><?= e($p['name']) ?></h3>
            <p class="paper__title"><?= e($p['title']) ?></p>
            <p class="paper__contact"><?= e($p['email']) ?> · <?= e($p['phone']) ?> · Umbergaon, Valsad 396165</p>
          </header>
          <p class="paper__summary"><?= e($p['summary']) ?></p>
          <h4 class="paper__h">Professional experience</h4>
          <ol class="paper__jobs">
            <?php foreach ($p['experience'] as $x): ?>
              <li>
                <div class="paper__job-head"><span><strong><?= e($x['role']) ?></strong> · <?= e($x['company']) ?></span><span class="paper__date"><?= e($x['period']) ?></span></div>
                <ul><?php foreach ($x['points'] as $pt): ?><li><?= e($pt) ?></li><?php endforeach; ?></ul>
              </li>
            <?php endforeach; ?>
          </ol>
          <h4 class="paper__h">Education</h4>
          <p><strong><?= e($p['education']['degree']) ?></strong> · <?= e($p['education']['school']) ?> · 06/2018 — 06/2021</p>
          <h4 class="paper__h">Key skills</h4>
          <p><?= e(implode(' · ', array_column($p['skills'], 0))) ?></p>
        </article>
      </div>
    </section>

    <!-- TERMINAL -->
    <section class="win win--term" id="win-terminal" data-app="terminal" data-geo="<?= implode(',', array_slice($apps['terminal'], 2)) ?>" role="dialog" aria-label="Terminal" tabindex="-1" hidden>
      <header class="win__bar" data-drag>
        <div class="win__lights"><button type="button" class="tl tl--close" data-close aria-label="Close Terminal"></button><button type="button" class="tl tl--min" data-min aria-label="Minimise"></button><button type="button" class="tl tl--max" data-max aria-label="Maximise"></button></div>
        <p class="win__title">recruiter@vikas-os: ~</p>
        <button type="button" class="win__back" data-close>‹ Home</button>
      </header>
      <div class="win__body term" data-term>
        <div class="term__out" data-term-out aria-live="polite">
          <p>Vikas OS terminal. Type <b>help</b> to see commands, or try <b>sudo hire vikas</b>.</p>
        </div>
        <form class="term__line" data-term-form autocomplete="off">
          <label for="term-in" class="term__prompt">recruiter@vikas-os:~$</label>
          <input id="term-in" type="text" spellcheck="false" autocapitalize="off" data-term-in aria-label="Terminal command">
        </form>
      </div>
    </section>

    <!-- BOOK A CALL -->
    <section class="win" id="win-call" data-app="call" data-geo="<?= implode(',', array_slice($apps['call'], 2, 4)) ?>" role="dialog" aria-label="Book a Call" tabindex="-1" hidden>
      <header class="win__bar" data-drag>
        <div class="win__lights"><button type="button" class="tl tl--close" data-close aria-label="Close Book a Call"></button><button type="button" class="tl tl--min" data-min aria-label="Minimise"></button><button type="button" class="tl tl--max" data-max aria-label="Maximise"></button></div>
        <p class="win__title"><span class="win__ico appicon--call"><?= icon('call', $icons) ?></span>Book a Call</p>
        <button type="button" class="win__back" data-close>‹ Home</button>
      </header>
      <div class="win__body call" data-call>
        <h2 class="win__h">Book an interview call</h2>
        <p class="muted">Pick a day and time that suits you. Times are in India Standard Time (IST); your local time is shown too. Vikas confirms by email within one working day.</p>

        <?php if (!empty($p['booking_url'])): ?>
          <div class="call__modes" role="tablist" aria-label="Booking method">
            <button type="button" role="tab" aria-selected="true" data-callmode="request">Request a slot</button>
            <button type="button" role="tab" aria-selected="false" data-callmode="instant">Instant booking</button>
          </div>
          <div class="call__instant" data-callpane="instant" hidden>
            <iframe title="Book a call with Vikas" data-src="<?= e($p['booking_url']) ?>" loading="lazy"></iframe>
            <a class="btn btn--ghost btn--xs" href="<?= e($p['booking_url']) ?>" target="_blank" rel="noopener">Open booking page in a new tab ↗</a>
          </div>
        <?php endif; ?>

        <form class="callform" action="contact.php" method="post" data-callform data-callpane="request" novalidate>
          <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
          <input type="hidden" name="type" value="Interview call">
          <input type="hidden" name="slot" value="" data-slot-input>
          <input type="hidden" name="slot_start" value="" data-slot-start>
          <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

          <p class="call__step"><span>1</span>Choose a day</p>
          <div class="call__days" data-days role="radiogroup" aria-label="Day"></div>

          <p class="call__step"><span>2</span>Choose a time <small class="muted" data-tz-note></small></p>
          <div class="call__times" data-times role="radiogroup" aria-label="Time"></div>

          <p class="call__step"><span>3</span>Your details</p>
          <div class="call__fields">
            <label class="cfield"><span>Name *</span><input type="text" name="name" required maxlength="80" autocomplete="name" placeholder="Priya Sharma"></label>
            <label class="cfield"><span>Work email *</span><input type="email" name="email" required maxlength="120" autocomplete="email" placeholder="priya@company.com"></label>
            <label class="cfield"><span>Phone (optional)</span><input type="tel" name="phone" maxlength="30" autocomplete="tel" placeholder="+91 98765 43210"></label>
            <label class="cfield"><span>Company</span><input type="text" name="company" maxlength="120" autocomplete="organization" placeholder="Company name"></label>
            <label class="cfield cfield--wide"><span>Role / agenda (optional)</span><textarea name="message" rows="3" maxlength="1500" placeholder="e.g. SEO Executive role, 30-minute intro call"></textarea></label>
          </div>

          <div class="call__summary" data-call-summary>Choose a day and time above.</div>
          <div class="call__foot">
            <p class="form__status" data-call-status role="status" aria-live="polite"></p>
            <button class="btn btn--primary" type="submit" data-call-submit><span data-call-submit-text>Request this call</span></button>
          </div>

          <div class="form__success" data-call-success hidden>
            <span class="form__check" aria-hidden="true">✓</span>
            <p class="form__done">Call requested</p>
            <p class="muted" data-call-done-text>Vikas will confirm by email within one working day.</p>
            <button type="button" class="btn btn--ghost" data-call-reset>Book another time</button>
          </div>
        </form>
      </div>
    </section>

    <!-- MAIL -->
    <section class="win" id="win-mail" data-app="mail" data-geo="<?= implode(',', array_slice($apps['mail'], 2)) ?>" role="dialog" aria-label="Mail" tabindex="-1" hidden>
      <header class="win__bar" data-drag>
        <div class="win__lights"><button type="button" class="tl tl--close" data-close aria-label="Close Mail"></button><button type="button" class="tl tl--min" data-min aria-label="Minimise"></button><button type="button" class="tl tl--max" data-max aria-label="Maximise"></button></div>
        <p class="win__title"><span class="win__ico appicon--mail"><?= icon('mail', $icons) ?></span>New Message</p>
        <button type="button" class="win__back" data-close>‹ Home</button>
      </header>
      <div class="win__body win__body--mail">
        <form class="form" action="contact.php" method="post" data-form novalidate>
          <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
          <div class="hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
          <div class="mailrow"><span class="mailrow__k">To:</span><span class="to-chip"><span class="to-chip__av">VG</span><?= e($p['name']) ?></span></div>
          <label class="mailrow field"><span class="mailrow__k">From:</span><input type="text" name="name" required maxlength="80" autocomplete="name" placeholder="Your name"><span class="field__error">Please enter your name.</span></label>
          <label class="mailrow field"><span class="mailrow__k">Email:</span><input type="email" name="email" required maxlength="120" autocomplete="email" placeholder="you@company.com"><span class="field__error">Please enter a valid email.</span></label>
          <label class="mailrow field"><span class="mailrow__k">Company:</span><input type="text" name="company" maxlength="120" autocomplete="organization" placeholder="Optional"></label>
          <div class="mailrow"><span class="mailrow__k">Subject:</span>
            <div class="seg" role="radiogroup" aria-label="Subject">
              <label><input type="radio" name="type" value="Full-time role" checked><span>Full-time role</span></label>
              <label><input type="radio" name="type" value="Freelance project"><span>Project</span></label>
              <label><input type="radio" name="type" value="Something else"><span>Other</span></label>
            </div>
          </div>
          <label class="field mailbody"><span class="sr-only">Message</span><textarea name="message" rows="8" required minlength="10" maxlength="3000" placeholder="Hi Vikas, we're hiring for…"></textarea><span class="field__error">Please write at least 10 characters.</span></label>
          <div class="mailfoot">
            <p class="form__status" data-form-status role="status" aria-live="polite"></p>
            <button class="btn btn--primary" type="submit" data-submit>
              <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M4 12 20 4l-6 16-3-7z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
              <span data-submit-text>Send message</span>
            </button>
          </div>
          <div class="form__success" data-form-success hidden>
            <span class="form__check" aria-hidden="true">✓</span>
            <p class="form__done">Message sent</p>
            <p class="muted">A confirmation is on its way to your inbox. Vikas replies within one working day.</p>
            <button type="button" class="btn btn--ghost" data-form-reset>New message</button>
          </div>
        </form>
        <p class="mail__alt muted small">Prefer direct? <button type="button" data-copy="<?= e($p['email']) ?>"><?= e($p['email']) ?></button> · <a href="tel:+<?= e($p['phone_raw']) ?>"><?= e($p['phone']) ?></a> · <a href="https://wa.me/<?= e($p['phone_raw']) ?>?text=<?= $waText ?>" target="_blank" rel="noopener">WhatsApp</a></p>
      </div>
    </section>
  </div>

  <!-- Quick view header (only visible in quick view) -->
  <div class="quickhead">
    <p>Quick view: everything on one page.</p>
    <button type="button" class="btn btn--ghost btn--xs" data-action="quick">Back to desktop</button>
  </div>
</main>

<!-- ================= DOCK ================= -->
<nav class="dock" aria-label="Dock">
  <?php foreach ($apps as $id => $a): ?>
    <button type="button" class="dock__app" data-open="<?= e($id) ?>" data-dock="<?= e($id) ?>" aria-label="<?= e($a[0]) ?>">
      <span class="appicon appicon--<?= e($a[1]) ?>"><?= icon($a[1], $icons) ?></span>
      <span class="dock__tip"><?= e($a[0]) ?></span>
      <i class="dock__dot" aria-hidden="true"></i>
    </button>
  <?php endforeach; ?>
  <?php if ($hasResume): ?>
    <span class="dock__sep" aria-hidden="true"></span>
    <a class="dock__app dock__app--dl" href="<?= e($p['resume']) ?>" download aria-label="Download CV">
      <span class="appicon appicon--dl"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11m0 0-4.5-4.5M12 15l4.5-4.5M5 20h14"/></svg></span>
      <span class="dock__tip">Download CV</span>
    </a>
  <?php endif; ?>
  <button type="button" class="dock__app dock__app--trash" data-action="trash" aria-label="Trash">
    <span class="appicon appicon--trash"><svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.5 7h15M9.5 7V5h5v2M6.5 7l1 13h9l1-13M10 11v6M14 11v6"/></svg></span>
    <span class="dock__tip">Trash</span>
  </button>
</nav>

<div class="toast" role="status" aria-live="polite" data-toast></div>

<script>window.VIKAS = <?= json_encode($termData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;</script>
<script src="assets/js/main.js?v=90" defer></script>
</body>
</html>
