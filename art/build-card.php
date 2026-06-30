<?php

declare(strict_types=1);

// Builds the social card: the 1,721 CUATM localities plotted by their real
// coordinates so the dots draw the map of Moldova, with the title overlaid.

$rows = json_decode((string) file_get_contents(__DIR__.'/../database/data/cuatm.json'), true, flags: JSON_THROW_ON_ERROR);

$pts = [];
foreach ($rows as $r) {
    if ($r['lat'] === null || $r['lng'] === null) {
        continue;
    }
    $pts[] = ['lat' => (float) $r['lat'], 'lng' => (float) $r['lng'], 'type' => $r['type']];
}

$lats = array_column($pts, 'lat');
$lngs = array_column($pts, 'lng');
$latMin = min($lats);
$latMax = max($lats);
$lngMin = min($lngs);
$lngMax = max($lngs);
$meanLat = array_sum($lats) / count($lats);
$kx = cos(deg2rad($meanLat)); // horizontal squeeze so the country isn't stretched

// Map panel geometry inside the left 640x640 square.
$panelX = 70;
$panelY = 60;
$panelW = 510;
$panelH = 520;

$spanLng = ($lngMax - $lngMin) * $kx;
$spanLat = ($latMax - $latMin);
$scale = min($panelW / $spanLng, $panelH / $spanLat);
$drawW = $spanLng * $scale;
$drawH = $spanLat * $scale;
$offX = $panelX + ($panelW - $drawW) / 2;
$offY = $panelY + ($panelH - $drawH) / 2;

$big = ['raion', 'municipality', 'autonomous_unit', 'territorial_unit'];

$circles = '';
foreach ($pts as $p) {
    $x = $offX + (($p['lng'] - $lngMin) * $kx) * $scale;
    $y = $offY + ($latMax - $p['lat']) * $scale; // invert y
    $isBig = in_array($p['type'], $big, true);
    $r = $isBig ? 4.2 : 1.7;
    $fill = $isBig ? '#7dd3fc' : '#38bdf8';
    $op = $isBig ? '0.95' : '0.55';
    $circles .= sprintf('<circle cx="%.1f" cy="%.1f" r="%.1f" fill="%s" opacity="%s"/>', $x, $y, $r, $fill, $op);
}

$count = number_format(count($pts));

$html = <<<HTML
<!doctype html><html><head><meta charset="utf-8"><style>
*{margin:0;padding:0;box-sizing:border-box}
html,body{width:1280px;height:640px}
body{font-family:'DejaVu Sans',Verdana,sans-serif;background:#0b1020;color:#e7ecf5;overflow:hidden;position:relative}
.glow{position:absolute;width:760px;height:760px;left:-180px;top:-120px;border-radius:50%;background:radial-gradient(circle,rgba(56,189,248,.18),transparent 62%)}
.wrap{position:absolute;inset:0;display:flex;align-items:center}
.map{width:640px;height:640px;position:relative}
.right{flex:1;padding:0 70px 0 10px}
.kicker{font-size:18px;letter-spacing:.32em;text-transform:uppercase;color:#64d2ff;font-weight:bold;margin-bottom:18px}
h1{font-size:60px;line-height:1.05;font-weight:bold;letter-spacing:-.5px}
h1 .accent{color:#38bdf8}
.tag{margin-top:22px;font-size:23px;line-height:1.5;color:#aab6cc;max-width:470px}
.chips{margin-top:38px;display:flex;gap:14px}
.chip{font-size:18px;font-weight:bold;padding:10px 18px;border:1px solid #24324d;border-radius:999px;color:#cfe0f5;background:#121a2e}
.meta{position:absolute;left:70px;bottom:40px;font-size:16px;color:#5d6b85;letter-spacing:.04em}
</style></head><body>
<div class="glow"></div>
<div class="wrap">
  <div class="map"><svg width="640" height="640" viewBox="0 0 640 640">$circles</svg></div>
  <div class="right">
    <div class="kicker">$count localities</div>
    <h1>Moldova <span class="accent">CUATM</span><br>for Laravel</h1>
    <div class="tag">Every administrative-territorial unit of Moldova as ready-to-use Eloquent models, with official codes and coordinates.</div>
    <div class="chips"><div class="chip">Laravel 12 · 13</div><div class="chip">PHP 8.3+</div></div>
  </div>
</div>
<div class="meta">ihangan/laravel-moldova-cuatm</div>
</body></html>
HTML;

file_put_contents(__DIR__.'/social-card.html', $html);
echo 'wrote art/social-card.html with '.count($pts)." points\n";
