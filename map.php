<?php

session_start();
// Require login (admin or user)
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}
$conn = new mysqli("10.1.1.17", "badets", "badets@1234", "haumonstersDB");
// Fetch monsters
$monsters = [];
$result = $conn->query("SELECT monster_name, monster_type, spawn_latitude, spawn_longitude, picture_url FROM monsterstbl");
while ($row = $result->fetch_assoc()) {
    $monsters[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Monster Map — HAUPokémon</title>
<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Nunito:wght@700;800;900&display=swap" rel="stylesheet">
<link href="https://fonts.cdnfonts.com/css/pokemon-solid" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ─── CSS VARIABLES ─────────────────────────── */
:root {
  --red:         #cc0000;
  --red-dark:    #8b0000;
  --red-light:   #ff4444;
  --red-glow:    rgba(204,0,0,0.5);
  --yellow:      #ffde00;
  --yellow-dk:   #b8a000;
  --yellow-glow: rgba(255,222,0,0.4);
  --blue:        #3b4cca;
  --blue-light:  #6878f0;
  --blue-glow:   rgba(59,76,202,0.4);
  --green:       #00a550;
  --green-light: #4ade80;
  --cyan:        #00e5ff;
}

/* ─── BODY ───────────────────────────────────── */
body {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #0d0d1a;
  background-image:
    radial-gradient(ellipse at 15% 20%, rgba(59,76,202,0.28) 0%, transparent 45%),
    radial-gradient(ellipse at 85% 80%, rgba(204,0,0,0.25) 0%, transparent 45%);
  font-family: 'Nunito', sans-serif;
  font-weight: 700;
  color: #f0eaff;
  overflow-x: hidden;
}

/* Stars */
.stars { position: fixed; inset: 0; pointer-events: none; z-index: 0; }
.star {
  position: absolute; width: 4px; height: 4px;
  background: var(--yellow); border-radius: 1px;
  animation: twinkle 2.5s ease-in-out infinite;
}
.star:nth-child(2n) { background: #fff; width: 2px; height: 2px; animation-delay: .7s; }
.star:nth-child(3n) { animation-delay: 1.4s; }
.star:nth-child(4n) { animation-delay: 2.1s; }
@keyframes twinkle {
  0%,100% { opacity:.12; transform:scale(1); }
  50%      { opacity:1;   transform:scale(1.8); }
}

/* Floating pokéballs */
.bg-balls { position: fixed; inset: 0; pointer-events: none; z-index: 0; overflow: hidden; }
.bg-ball   { position: absolute; opacity:.05; animation: bg-float linear infinite; }
@keyframes bg-float {
  0%   { transform: translateY(110vh) rotate(0deg); }
  100% { transform: translateY(-20vh) rotate(720deg); }
}

/* ─── TOP BAR ────────────────────────────────── */
.topbar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 28px; height: 64px;
  background: linear-gradient(180deg, #1a0a22 0%, #0d0614 100%);
  border-bottom: 3px solid var(--blue);
  box-shadow: 0 4px 0 #1a2060, 0 6px 30px rgba(59,76,202,0.3);
  position: sticky; top: 0; z-index: 200;
}
.brand { display: flex; align-items: center; gap: 14px; }
.brand-logo {
  font-family: 'Pokemon Solid', 'Press Start 2P', monospace;
  font-size: 1.1rem;
  color: var(--yellow);
  -webkit-text-stroke: 2px #3b4cca;
  paint-order: stroke fill;
  text-shadow: 3px 3px 0 #2a36a0, 0 0 28px var(--yellow-glow);
}
.brand-badge {
  font-family: 'Press Start 2P', monospace; font-size: 0.34rem;
  background: linear-gradient(180deg, var(--red-light), var(--red));
  color: #fff; padding: 4px 9px; border-radius: 4px;
  border: 2px solid #660000;
  box-shadow: 0 3px 0 #440000, inset 0 1px 0 rgba(255,255,255,0.2);
  letter-spacing: 0.1em;
}
.topbar-right { display: flex; align-items: center; gap: 10px; }

/* Power LED */
.power-led {
  width: 10px; height: 10px; border-radius: 50%;
  background: var(--green-light);
  box-shadow: 0 0 8px var(--green-light), 0 0 18px rgba(74,222,128,.5);
  animation: led-pulse 2s ease-in-out infinite;
}
@keyframes led-pulse { 0%,100%{opacity:1} 50%{opacity:.3} }

/* Back button (topbar style matching catch_monsters) */
.topbar-btn,
.player-chip {
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 14px;
}

.topbar-btn {
  display: flex; align-items: center; gap: 6px;
  font-family: 'Press Start 2P', monospace; font-size: 0.3rem;
  letter-spacing: 0.08em; color: #f0eaff;
  background: linear-gradient(180deg, #1e1640, #120e28);
  border: 2px solid var(--blue); border-radius: 8px;
  padding: 7px 12px; cursor: pointer; text-decoration: none;
  box-shadow: 0 3px 0 #1a2060;
  transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
}
.topbar-btn:hover {
  border-color: var(--red);
  box-shadow: 0 5px 0 #aa2200, 0 8px 20px rgba(204,0,0,0.2);
  transform: translateY(-2px);
}
.topbar-btn svg { width: 14px; height: 14px; stroke: var(--yellow); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }

/* ─── MAIN ───────────────────────────────────── */
.main {
  flex: 1; padding: 28px 32px 56px;
  max-width: 1100px; margin: 0 auto; width: 100%;
  position: relative; z-index: 1;
}

/* ─── PAGE HEADER ────────────────────────────── */
.page-header {
  display: flex; align-items: flex-end; justify-content: space-between;
  flex-wrap: wrap; gap: 12px; margin-bottom: 24px;
  animation: fadeUp 0.4s ease both;
}
.page-eyebrow {
  font-family: 'Press Start 2P', monospace; font-size: 0.36rem;
  color: var(--blue-light); letter-spacing: 0.22em;
  margin-bottom: 8px;
  display: flex; align-items: center; gap: 8px;
}
.page-eyebrow::before {
  content: ''; display: inline-block; width: 18px; height: 2px;
  background: var(--blue);
}
.page-title-main {
  font-size: 2rem; font-weight: 900;
  color: #f0eaff; letter-spacing: 0.03em; line-height: 1;
}
.page-title-main span {
  color: var(--yellow);
  -webkit-text-stroke: 1px #a08000; paint-order: stroke fill;
  text-shadow: 3px 3px 0 #2a36a0, 0 0 22px var(--yellow-glow);
}

/* SECTION LABEL */
.section-label {
  display:flex; align-items:center; gap:10px;
  margin-bottom:16px; animation:fadeUp .5s .1s ease both;
}
.section-label-pip {
  width:8px; height:8px; border-radius:2px;
  background:var(--yellow); box-shadow:0 0 8px var(--yellow-glow);
}
.section-label-text {
  font-family:'Press Start 2P',monospace; font-size:.36rem;
  color:var(--yellow); letter-spacing:.22em;
  text-shadow:0 0 14px var(--yellow-glow); white-space:nowrap;
}
.section-label-line {
  flex:1; height:1px;
  background:linear-gradient(90deg,rgba(59,76,202,0.4),transparent);
}

/* MAP CARD */
.map-card {
  background: linear-gradient(155deg, #ffffff 0%, #f7f2e8 55%, #ede6d8 100%);
  border: 3px solid #ccc4b0;
  border-radius: 18px;
  overflow: hidden;
  position: relative;
  box-shadow:
    0 6px 0 #a89880,
    0 10px 28px rgba(0,0,0,0.28),
    inset 0 1px 0 #fff;
  animation: fadeUp 0.5s 0.15s ease both;
}
.map-card-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 18px;
  gap: 12px;
  border-bottom: 2px solid #e0d8cc;
  background: linear-gradient(180deg, #fefcf8 0%, #f7f2e8 100%);
  flex-wrap: nowrap;
}
.map-card-title-row {
  display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1;
}
.map-icon-pill {
  width: 48px; height: 48px; border-radius: 10px; flex-shrink: 0;
  background: linear-gradient(145deg, #d8e4ff, #b8caff);
  border: 2px solid #90a8e8;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem;
  box-shadow: 0 3px 0 #8090d0, inset 0 1px 0 rgba(255,255,255,0.9);
}
.map-card-title {
  font-size: 1.05rem; font-weight: 900; color: #1a1030; letter-spacing: .03em;
  line-height: 1.3; margin-bottom: 2px;
}
.map-card-sub {
  font-size: .78rem; color: #7a6a50; font-weight: 700; line-height: 1.4;
}
.map-type-badge {
  font-family: 'Press Start 2P', monospace; font-size: .26rem;
  background: #d8e4ff; color: #2240a0;
  border: 1px solid #90a8e8; border-radius: 4px;
  padding: 4px 9px; letter-spacing: .05em;
  display: inline-flex; align-items: center; white-space: nowrap;
}
.map-ctrl-row {
  display: flex; align-items: center; gap: 8px; flex-shrink: 0;
}
.map-live-badge {
  display: flex; align-items: center; gap: 6px;
  font-family: 'Press Start 2P', monospace; font-size: 0.26rem;
  color: var(--green); letter-spacing: 0.08em;
  background: linear-gradient(145deg, #d0f8e8, #b8f0d8);
  border: 1px solid #80d8b0; border-radius: 5px; padding: 6px 10px;
}
.map-live-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--green-light);
  box-shadow: 0 0 6px var(--green-light);
  animation: led-pulse 1.5s ease-in-out infinite;
}

/* Map wrap + dark tile filter */
.map-wrap {
  position: relative; height: 500px; overflow: hidden;
}
#map {
  height: 100%;
  width: 100%;
  background: #1a1a2e;
  display: block;
  z-index: 1;
}
.leaflet-tile { filter: brightness(0.58) saturate(0.45) hue-rotate(200deg); }

/* Pokeball watermark on map */
.map-pb {
  position: absolute; right: -24px; bottom: -24px;
  width: 110px; height: 110px; pointer-events: none; opacity: .06; z-index: 1;
}

/* BOTTOM BAR */
.bottom-bar {
  display:flex; align-items:center; justify-content:space-between;
  padding:14px 28px; background:rgba(8,6,20,0.92);
  border-top:2px solid #1e2060; font-size:.68rem; color:#4a4a7a;
  letter-spacing:.08em; position:relative; z-index:1;
}
.bottom-bar-left { display:flex; align-items:center; gap:10px; }
.pixel-dots { display:flex; gap:5px; }
.pixel-dot { width:7px; height:7px; border-radius:2px; }
.pixel-dot:nth-child(1){background:#1e2060;}
.pixel-dot:nth-child(2){background:var(--blue);}
.pixel-dot:nth-child(3){background:var(--blue-light);opacity:.7;}
.version-tag {
  font-family:'Press Start 2P',monospace; font-size:.3rem;
  color:var(--yellow); letter-spacing:.08em; opacity:.7;
}

/* TOAST */
.toast {
  position:fixed; bottom:28px; left:50%;
  transform:translateX(-50%) translateY(80px);
  background:linear-gradient(145deg,#1a1840,#0e0c28);
  border:2px solid var(--blue); border-radius:10px;
  padding:12px 24px;
  font-family:'Press Start 2P',monospace; font-size:.38rem; color:var(--yellow);
  z-index:9999; pointer-events:none; white-space:nowrap; letter-spacing:.1em;
  transition:transform .4s cubic-bezier(.34,1.56,.64,1);
  box-shadow:0 4px 0 #1a2060,0 12px 40px rgba(0,0,0,.7),0 0 30px rgba(59,76,202,.35);
}
.toast::before { content:'▶ '; color:var(--blue-light); }
.toast.show { transform:translateX(-50%) translateY(0); }

/* Leaflet popup overrides */
.leaflet-popup-content-wrapper {
  background: linear-gradient(145deg,#1a1840,#0e0c28) !important;
  border: 2px solid var(--blue) !important;
  border-radius: 10px !important;
  color: #f0eaff !important;
  box-shadow: 0 4px 0 #1a2060, 0 8px 24px rgba(0,0,0,.7) !important;
  font-family: 'Nunito', sans-serif !important;
  font-weight: 700 !important;
}
.leaflet-popup-tip { background: var(--blue) !important; }
.leaflet-popup-content b {
  font-family: 'Press Start 2P', monospace !important;
  font-size: 0.5rem !important;
  color: var(--yellow) !important;
  display: block !important; margin-bottom: 4px !important;
}
.leaflet-popup-content {
  color: #c0b8e0 !important; font-size: 0.78rem !important;
}

@keyframes fadeUp {
  from { opacity:0; transform:translateY(18px); }
  to   { opacity:1; transform:translateY(0); }
}

@media (max-width: 600px) {
  .main { padding: 20px 14px 36px; }
  .page-title-main { font-size: 1.5rem; }
  .map-wrap { height: 360px; }
}
</style>
</head>
<body>

<div class="stars" id="stars"></div>
<div class="bg-balls" id="bgBalls"></div>
<div class="toast" id="toast"></div>

<header class="topbar">
  <div class="brand">
    <div class="brand-logo">HAUPokémon</div>
  </div>
  <div class="topbar-right">
    <div class="power-led"></div>
  </div>
</header>

<main class="main">

  <div class="page-header">
    <div>
      <div class="page-eyebrow">// EXPLORER</div>
      <div class="page-title-main">Monster <span>Map</span></div>
    </div>
    <?php
    $dashboard = ($_SESSION['role'] == 'admin')
        ? 'admin_dashboard.php'
        : 'player_dashboard.php';
    echo '<a class="topbar-btn" href="'.$dashboard.'" onclick="showToast(\'Going back...\')">
      <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
      BACK
    </a>';
    ?>
  </div>

  <div class="section-label">
    <div class="section-label-pip"></div>
    <span class="section-label-text">Spawn Locations</span>
    <div class="section-label-line"></div>
  </div>

  <div class="map-card">
    <div class="map-card-header">
      <div class="map-card-title-row">
        <div class="map-icon-pill">🗺️</div>
        <div>
          <div class="map-card-title">Live Spawn Map</div>
          <div class="map-card-sub">GPS-based spawn locations</div>
        </div>
      </div>
      <div class="map-ctrl-row">
        <div class="map-live-badge">
          <div class="map-live-dot"></div>
          LIVE
        </div>
      </div>
    </div>
    <div class="map-wrap">
      <div id="map"></div>
      <svg class="map-pb" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <circle cx="50" cy="50" r="48" fill="none" stroke="white" stroke-width="5"/>
        <path d="M2,50 A48,48 0 0,1 98,50 Z" fill="white" opacity=".7"/>
        <rect x="2" y="46" width="96" height="8" fill="white"/>
        <circle cx="50" cy="50" r="13" fill="none" stroke="white" stroke-width="5"/>
        <circle cx="50" cy="50" r="7" fill="white"/>
      </svg>
    </div>
  </div>

</main>

<footer class="bottom-bar">
  <div class="bottom-bar-left">
    <div class="pixel-dots">
      <div class="pixel-dot"></div><div class="pixel-dot"></div><div class="pixel-dot"></div>
    </div>
    BADETS © CLOUD SYSTEMS INTL
  </div>
  <div class="version-tag">HAUPokémon</div>
</footer>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg.toUpperCase();
  t.classList.add('show');
  clearTimeout(t._tid);
  t._tid = setTimeout(() => t.classList.remove('show'), 2500);
}

// Initialize map (default: Pampanga/HAU area)
var map = L.map('map').setView([15.1563, 120.5917], 10);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

// Load monsters from PHP
var monsters = <?php echo json_encode($monsters); ?>;

// Type → glow color map
var typeGlowColors = {
    fire:     '#ff4422',
    water:    '#3399ff',
    grass:    '#44dd44',
    electric: '#ffdd00',
    psychic:  '#ff66cc',
    ice:      '#66eeff',
    rock:     '#bbaa77',
    ground:   '#ddaa55',
    flying:   '#aabbff',
    bug:      '#88cc00',
    poison:   '#cc44ff',
    ghost:    '#8855cc',
    dragon:   '#7755ff',
    dark:     '#886644',
    steel:    '#aabbcc',
    fairy:    '#ffaacc',
    fighting: '#cc5500',
    normal:   '#bbbbbb'
};

function getTypeGlow(type) {
    var key = (type || '').toLowerCase().trim();
    return typeGlowColors[key] || '#ffffff';
}

function getIconSize(zoom) {
    var size = Math.round(16 + (zoom - 6) * 3);
    size = Math.max(14, Math.min(size, 56));
    return size;
}

function buildIcon(monster, size, glowColor) {
    var glowFilter = 'drop-shadow(0 0 5px ' + glowColor + ')';
    var imgContent = monster.picture_url
        ? '<img src="' + monster.picture_url + '" onerror="this.outerHTML=\'<span style=&quot;font-size:' + Math.round(size * 0.7) + 'px;filter:' + glowFilter + '&quot;>🦕</span>\'" style="width:' + size + 'px;height:' + size + 'px;object-fit:contain;">'
        : '<span style="font-size:' + Math.round(size * 0.8) + 'px;">🦕</span>';

    var iconHtml = '<div style="font-size:' + Math.round(size * 0.7) + 'px;filter:' + glowFilter + ';display:flex;align-items:center;justify-content:center;">' + imgContent + '</div>';

    return L.divIcon({
        html: iconHtml,
        className: '',
        iconSize:   [size, size],
        iconAnchor: [Math.round(size / 2), Math.round(size / 2)],
        popupAnchor:[0, -Math.round(size / 2)]
    });
}

// Store markers so we can update them on zoom
var markerObjects = [];

monsters.forEach(function(monster) {
    var glowColor = getTypeGlow(monster.monster_type);
    var currentZoom = map.getZoom();
    var size = getIconSize(currentZoom);

    var marker = L.marker(
        [parseFloat(monster.spawn_latitude), parseFloat(monster.spawn_longitude)],
        { icon: buildIcon(monster, size, glowColor) }
    ).addTo(map);

    var popupImg = monster.picture_url
        ? '<img src="' + monster.picture_url + '" onerror="this.outerHTML=\'<span style=&quot;font-size:2rem&quot;>🦕</span>\'" alt="' + monster.monster_name + '" style="width:80px;height:auto;margin-top:6px;border-radius:8px;filter:drop-shadow(0 0 6px ' + glowColor + ');">'
        : '<span style="font-size:2rem;filter:drop-shadow(0 0 6px ' + glowColor + ');">🦕</span>';

    marker.bindPopup(
        "<b>" + monster.monster_name + "</b>" +
        "<br>Type: " + monster.monster_type +
        "<br>" + popupImg
    );

    marker.on('click', function() {
        showToast(monster.monster_name + ' found!');
    });

    // Save reference for zoom updates
    markerObjects.push({ marker: marker, monster: monster, glowColor: glowColor });
});

// Update all monster icon sizes whenever the map zooms
map.on('zoom', function() {
    var zoom = map.getZoom();
    var size = getIconSize(zoom);
    markerObjects.forEach(function(obj) {
        obj.marker.setIcon(buildIcon(obj.monster, size, obj.glowColor));
    });
});

/* ─── STARS ─────────────────────────────────── */
(function() {
  var c = document.getElementById('stars');
  for (var i = 0; i < 55; i++) {
    var s = document.createElement('div');
    s.className = 'star';
    s.style.left = Math.random() * 100 + 'vw';
    s.style.top  = Math.random() * 100 + 'vh';
    s.style.animationDuration = (1.5 + Math.random() * 2.5) + 's';
    s.style.animationDelay    = (Math.random() * 2.5) + 's';
    c.appendChild(s);
  }
})();

/* ─── FLOATING POKÉBALLS ─────────────────────── */
(function() {
  var c = document.getElementById('bgBalls');
  [60,90,50,120,70,80,55].forEach(function(sz, i) {
    var w = document.createElement('div');
    w.className = 'bg-ball';
    w.style.left = (8 + i * 13 + Math.random() * 10) + 'vw';
    w.style.bottom = '-120px';
    w.style.animationDuration = (18 + Math.random() * 14) + 's';
    w.style.animationDelay    = (i * 3 + Math.random() * 4) + 's';
    w.innerHTML =
      '<svg width="' + sz + '" height="' + sz + '" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">' +
        '<circle cx="50" cy="50" r="48" fill="none" stroke="white" stroke-width="5"/>' +
        '<path d="M2,50 A48,48 0 0,1 98,50 Z" fill="white" opacity=".7"/>' +
        '<rect x="2" y="46" width="96" height="8" fill="white"/>' +
        '<circle cx="50" cy="50" r="13" fill="none" stroke="white" stroke-width="5"/>' +
        '<circle cx="50" cy="50" r="7" fill="white"/>' +
      '</svg>';
    c.appendChild(w);
  });
})();
</script>
</body>
</html>