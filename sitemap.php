<?php
// Served as /sitemap.xml (see .htaccess)
require __DIR__ . '/includes/url.php';
header('Content-Type: application/xml; charset=utf-8');
$base = site_base_url();
$pages = [
    ['loc' => $base, 'file' => __DIR__ . '/index.php', 'priority' => '1.0'],
    ['loc' => $base . 'Vikas_Rambaul_Giri_Resume.pdf', 'file' => __DIR__ . '/Vikas_Rambaul_Giri_Resume.pdf', 'priority' => '0.6'],
];
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($pages as $pg) {
    if (!is_file($pg['file'])) { continue; }
    echo '  <url><loc>' . htmlspecialchars($pg['loc'], ENT_XML1) . '</loc><lastmod>' . date('Y-m-d', filemtime($pg['file'])) . '</lastmod><priority>' . $pg['priority'] . "</priority></url>\n";
}
echo "</urlset>\n";
