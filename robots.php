<?php
// Served as /robots.txt (see .htaccess)
require __DIR__ . '/includes/url.php';
header('Content-Type: text/plain; charset=utf-8');
$base = site_base_url();
echo "User-agent: *\n";
echo "Allow: /\n";
foreach (['includes/', 'data/', 'contact.php', 'slots.php', 'mail-test.php'] as $p) { echo "Disallow: /{$p}\n"; }
echo "\nSitemap: {$base}sitemap.xml\n";
