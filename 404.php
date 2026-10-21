<?php
http_response_code(404);
header('X-Robots-Tag: noindex');
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Window not found · Vikas OS</title>
  <meta name="robots" content="noindex">
  <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
  <style>
    :root { color-scheme: light dark; }
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; font: 15px/1.6 "Segoe UI", system-ui, -apple-system, sans-serif;
      background: linear-gradient(180deg, #9FCDEE, #D8ECF6 55%, #F6E6D6); color: #1C1F24; }
    .win { width: min(440px, 100%); background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 30px 70px -20px rgba(15,20,35,.45); }
    .bar { height: 40px; display: flex; align-items: center; gap: 8px; padding: 0 14px; background: #F5F6F8; border-bottom: 1px solid rgba(0,0,0,.08); }
    .bar i { width: 12px; height: 12px; border-radius: 50%; background: #FF5F57; } .bar i:nth-child(2) { background: #FEBC2E; } .bar i:nth-child(3) { background: #28C840; }
    .body { padding: 28px; text-align: center; }
    .code { font-size: 54px; font-weight: 800; letter-spacing: -.04em; background: linear-gradient(100deg, #1FA474, #3D7BF2 60%, #8A5CF6); -webkit-background-clip: text; background-clip: text; color: transparent; line-height: 1; }
    h1 { margin: 12px 0 6px; font-size: 20px; } p { margin: 0 0 20px; color: #4B515B; }
    a { display: inline-block; padding: 10px 18px; border-radius: 10px; background: #3D7BF2; color: #fff; text-decoration: none; font-weight: 600; }
    @media (prefers-color-scheme: dark) { body { background: linear-gradient(180deg, #070D22, #16213F 55%, #33294F); } .win { background: #1F2228; color: #E7E9EC; } .bar { background: #262A31; border-color: rgba(255,255,255,.08); } p { color: #B7BDC6; } }
  </style>
</head>
<body>
  <main class="win" role="main">
    <div class="bar" aria-hidden="true"><i></i><i></i><i></i></div>
    <div class="body">
      <div class="code">404</div>
      <h1>This window doesn't exist</h1>
      <p>The page you're looking for was moved or never opened. Let's get you back to the desktop.</p>
      <a href="/">Back to Vikas OS</a>
    </div>
  </main>
</body>
</html>
