<?php
/** Absolute base URL of the site (works on localhost sub-folders and behind Render's HTTPS proxy). */
function site_base_url(): string
{
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host   = preg_replace('/[^a-z0-9.\-:\[\]]/i', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    $path   = rtrim(str_replace(chr(92), '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/'); // chr(92) = backslash (Windows)
    return ($https ? 'https' : 'http') . '://' . $host . $path . '/';
}
