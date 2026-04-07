<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("10.1.1.17", "badets", "badets@1234", "haumonstersDB");

$monsters = [];
$result = $conn->query("SELECT * FROM monsterstbl");
while ($row = $result->fetch_assoc()) {
    $monsters[] = $row;
}

$player_name = $_SESSION['username'] ?? 'Trainer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
  <title>Catch Monsters — HAUPokémon</title>
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

    /* Flash overlay */
    #flash-overlay {
      position: fixed; inset: 0;
      pointer-events: none; z-index: 998; opacity: 0;
      transition: opacity 0.15s;
    }
    #flash-overlay.flash { opacity: 1; }

    /* ─── TOP BAR ────────────────────────────────── */
    .topbar {
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 28px; height: 64px;
      background: linear-gradient(180deg, #1a0a22 0%, #0d0614 100%);
      border-bottom: 3px solid var(--blue);
      box-shadow: 0 4px 0 #1a2060, 0 6px 30px rgba(59,76,202,0.3);
      position: sticky; top: 0; z-index: 100;
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

    /* Player chip */
    .player-chip {
      display: flex; align-items: center; gap: 8px;
      background: linear-gradient(180deg, #1e1640, #120e28);
      border: 2px solid var(--blue); border-radius: 8px;
      padding: 5px 14px 5px 8px;
      font-size: 0.8rem; font-weight: 900; letter-spacing: 0.08em;
      box-shadow: 0 3px 0 #1a2060;
    }
    .player-avatar {
      width: 26px; height: 26px; border-radius: 6px;
      background: linear-gradient(135deg, var(--blue), var(--blue-light));
      border: 2px solid #2a3680;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.72rem;
    }

    /* 🔧 FORCE SAME SIZE */
.topbar-btn,
.player-chip {
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 14px;
}

    /* Back + Logout buttons */
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

    /* Reset button */
    .topbar-refresh-btn {
      background: none; border: none; cursor: pointer;
      padding: 6px; border-radius: 6px; transition: transform 0.2s;
    }
    .topbar-refresh-btn:hover { transform: rotate(90deg); }
    .topbar-refresh-btn svg { width: 18px; height: 18px; stroke: var(--blue-light); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

    /* ─── MAIN ───────────────────────────────────── */
    .main {
      padding: 28px 32px 56px;
      max-width: 1400px; margin: 0 auto;
      position: relative; z-index: 1;
      flex: 1;
    }

    /* ─── PAGE HEADER ────────────────────────────── */
    .page-header {
      display: flex; align-items: flex-end; justify-content: space-between;
      flex-wrap: wrap; gap: 12px;
      margin-bottom: 28px;
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
      font-size: 2.5rem; font-weight: 900;
      color: #f0eaff; letter-spacing: 0.03em; line-height: 1;
    }
    .page-title-main span {
      color: var(--yellow);
      -webkit-text-stroke: 1px #a08000; paint-order: stroke fill;
      text-shadow: 3px 3px 0 #2a36a0, 0 0 22px var(--yellow-glow);
    }

    /* ─── SECTION LABEL ──────────────────────────── */
    .section-label {
      display:flex; align-items:center; gap:10px;
      margin-bottom:16px; animation:fadeUp .5s .18s ease both;
    }
    .section-label-pip {
      width:8px; height:8px; border-radius:2px;
      background:var(--yellow); box-shadow:0 0 8px var(--yellow-glow);
    }
    .section-label-text {
      font-family:'Press Start 2P',monospace; font-size:.48rem;
      color:var(--yellow); letter-spacing:.22em;
      text-shadow:0 0 14px var(--yellow-glow); white-space:nowrap;
    }
    .section-label-line {
      flex:1; height:1px;
      background:linear-gradient(90deg,rgba(59,76,202,0.4),transparent);
    }

    /* ─── MAIN CONTENT GRID ──────────────────────── */
    .content-cols {
      display: flex;
      flex-direction: column;
      gap: 24px;
    }
    @media (min-width: 901px) {
      .content-cols {
        display: grid;
        grid-template-columns: 1.4fr 1fr;
        gap: 28px;
        align-items: start;
      }
    }

    /* ─── MAP CARD (hero element) ────────────────── */
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
      animation: fadeUp 0.5s 0.12s ease both;
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
      font-size: .78rem; color: #7a6a50; font-weight: 700;
      line-height: 1.4; margin-bottom: px;
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

    /* Scan button */
    .scan-btn {
      font-family: 'Press Start 2P', monospace; font-size: 0.38rem;
      letter-spacing: 0.1em; color: #fff;
      background: linear-gradient(180deg, var(--red-light), var(--red));
      border: 2px solid #660000; border-radius: 8px;
      padding: 11px 16px; cursor: pointer;
      box-shadow: 0 3px 0 #440000, inset 0 1px 0 rgba(255,255,255,0.2);
      transition: transform 0.12s, box-shadow 0.12s;
      white-space: nowrap; flex-shrink: 0;
      display: inline-flex; align-items: center; gap: 7px; line-height: 1;
    }
    .scan-btn:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 5px 0 #440000, 0 8px 20px rgba(204,0,0,0.3);
    }
    .scan-btn:active:not(:disabled) {
      transform: translateY(1px);
      box-shadow: 0 1px 0 #440000;
    }
    .scan-btn:disabled { opacity: .45; cursor: not-allowed; }
    .scan-btn.scanning {
      background: linear-gradient(180deg, #ffe060, var(--yellow-dk));
      border-color: #887000;
      box-shadow: 0 3px 0 #665000, inset 0 1px 0 rgba(255,255,255,0.2);
      color: #1a1000;
      animation: btn-pulse 0.8s ease-in-out infinite;
    }
    @keyframes btn-pulse { 0%,100%{opacity:1} 50%{opacity:0.6} }

    /* Map container */
    .map-wrap {
      position: relative; height: 480px; overflow: hidden;
    }
    #map { height: 100%; width: 100%; background: #1a1a2e; display: block; }
    .leaflet-tile { filter: brightness(0.58) saturate(0.45) hue-rotate(200deg); }

    /* Pokeball watermark on map card */
    .map-pb {
      position: absolute; right: -24px; bottom: -24px;
      width: 110px; height: 110px; pointer-events: none; opacity: .06; z-index: 1;
    }

    /* Radar idle overlay */
    .map-radar-idle {
      position: absolute; inset: 0;
      display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 16px;
      background: rgba(13,13,26,0.78);
      pointer-events: none; transition: opacity 0.4s; z-index: 400;
    }
    .map-radar-idle.hidden { opacity: 0; pointer-events: none; }
    .radar-icon {
      width: 110px; height: 110px; border-radius: 50%;
      border: 2px solid rgba(255,222,0,0.3);
      display: flex; align-items: center; justify-content: center;
      position: relative;
    }
    .radar-icon::before, .radar-icon::after {
      content: ''; position: absolute; border-radius: 50%;
      border: 1.5px solid rgba(255,222,0,0.15);
    }
    .radar-icon::before { width: 100%; height: 100%; animation: radar-pulse 2s ease-out infinite; }
    .radar-icon::after  { width: 140%; height: 140%; animation: radar-pulse 2s ease-out infinite 0.5s; }
    @keyframes radar-pulse {
      0%   { transform: scale(1); opacity: 0.5; }
      100% { transform: scale(1.8); opacity: 0; }
    }
    .radar-icon svg { width: 52px; height: 52px; stroke: var(--yellow); fill: none; stroke-width: 1.5; opacity: 0.7; }
    .radar-label {
      font-family: 'Press Start 2P', monospace; font-size: 0.44rem;
      color: rgba(240,234,255,0.5); letter-spacing: 0.18em; text-align: center; line-height: 2;
    }
    .radar-hint {
      font-family: 'Press Start 2P', monospace; font-size: 0.38rem;
      color: rgba(255,222,0,0.3); letter-spacing: 0.12em; text-align: center;
    }

    /* Scan rings */
    .map-scanning-ring {
      position: absolute; top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      pointer-events: none; z-index: 450; display: none;
    }
    .map-scanning-ring.active { display: block; }
    .scan-ring {
      position: absolute; top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      border-radius: 50%; border: 1.5px solid var(--yellow);
      animation: ring-expand 2s ease-out infinite;
    }
    .scan-ring:nth-child(2) { animation-delay: 0.66s; }
    .scan-ring:nth-child(3) { animation-delay: 1.32s; }
    @keyframes ring-expand {
      0%   { width: 20px; height: 20px; opacity: 0.9; }
      100% { width: 220px; height: 220px; opacity: 0; }
    }

    /* Status bar */
    .map-status-bar {
      padding: 12px 18px;
      background: linear-gradient(180deg, #f7f2e8 0%, #ede6d8 100%);
      border-top: 2px solid #e0d8cc;
      font-family: 'Press Start 2P', monospace; font-size: 0.42rem;
      letter-spacing: 0.1em; color: #8a7a60; text-align: center;
      min-height: 48px; display: flex; align-items: center; justify-content: center;
      gap: 8px; transition: color 0.3s;
    }
    .map-status-bar .status-led {
      width: 7px; height: 7px; border-radius: 50%;
      background: #c8b898; flex-shrink: 0; transition: background 0.3s, box-shadow 0.3s;
    }
    .map-status-bar.active  { color: #2240a0; }
    .map-status-bar.active  .status-led { background: var(--green-light); box-shadow: 0 0 6px var(--green-light); animation: led-blink 1s ease-in-out infinite; }
    .map-status-bar.alert   { color: var(--red); }
    .map-status-bar.alert   .status-led { background: var(--red); box-shadow: 0 0 8px var(--red-glow); animation: led-blink 0.4s ease-in-out infinite; }
    .map-status-bar.scanning{ color: #886600; }
    .map-status-bar.scanning .status-led { background: var(--yellow-dk); box-shadow: 0 0 8px var(--yellow-glow); animation: led-blink 0.8s ease-in-out infinite; }
    @keyframes led-blink { 0%,100%{opacity:1} 50%{opacity:0.3} }

    /* ─── MONSTER LIST PANEL ─────────────────────── */
    .monster-section { animation: fadeUp 0.5s 0.2s ease both; }

    /* Empty state card */
    .empty-card {
      background: linear-gradient(155deg, #ffffff 0%, #f7f2e8 55%, #ede6d8 100%);
      border: 3px solid #ccc4b0; border-radius: 18px;
      padding: 48px 20px;
      display: flex; flex-direction: column; align-items: center; gap: 16px;
      box-shadow: 0 6px 0 #a89880, 0 10px 28px rgba(0,0,0,0.28), inset 0 1px 0 #fff;
      text-align: center;
    }
    .empty-icon {
      width: 72px; height: 72px; border-radius: 50%;
      background: linear-gradient(145deg, #ede6d8, #e0d8cc);
      border: 2px solid #c8b898;
      display: flex; align-items: center; justify-content: center;
      font-size: 2rem;
      box-shadow: 0 3px 0 #a89880;
    }
    .empty-title {
      font-family: 'Press Start 2P', monospace; font-size: 0.3rem;
      color: #8a7a60; letter-spacing: 0.14em; line-height: 2;
    }
    .empty-sub {
      font-size: .78rem; color: #a89878; font-weight: 700; margin-top: -8px;
    }

    /* Monster grid — same structure as admin nav-grid */
    .monster-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 14px;
    }

    /* Monster card — styled identically to admin nav-card */
    .monster-card {
      display: flex; align-items: center; gap: 14px;
      background: linear-gradient(150deg, #ffffff 0%, #f7f2e8 55%, #ede6d8 100%);
      border: 3px solid #ccc4b0; border-radius: 18px;
      padding: 16px 16px;
      position: relative; overflow: hidden;
      transition: transform .18s, box-shadow .18s, border-color .18s;
      box-shadow:
        0 6px 0 #a89880,
        0 10px 24px rgba(0,0,0,0.24),
        inset 0 1px 0 #fff,
        inset 0 -2px 0 rgba(0,0,0,0.04);
      animation: card-in 0.35s ease both;
      text-decoration: none; cursor: default;
    }
    @keyframes card-in {
      from { opacity: 0; transform: translateY(12px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .monster-card.nearby {
      border-color: #90a8e8;
    }
    .monster-card.nearby:hover {
      transform: translateY(-4px);
      border-color: var(--blue);
      box-shadow: 0 10px 0 #6878c8, 0 18px 36px rgba(59,76,202,0.22), inset 0 1px 0 #fff;
    }
    .monster-card.far {
      opacity: 0.55;
      filter: grayscale(0.5);
    }

    /* Diagonal stripe watermark */
    .card-stripe {
      position: absolute; inset: 0; pointer-events: none; border-radius: 16px;
      background: repeating-linear-gradient(
        -45deg, transparent, transparent 18px,
        rgba(0,0,0,0.016) 18px, rgba(0,0,0,0.016) 19px
      );
    }

    /* Pokeball watermark */
    .card-pb {
      position: absolute; right: -18px; bottom: -18px;
      width: 84px; height: 84px; pointer-events: none; opacity: 0.07;
    }

    /* Bottom accent bar — matches nav-card .nav-accent */
    .card-accent {
      position: absolute; bottom: 0; left: 0; right: 0; height: 4px;
      border-radius: 0 0 16px 16px;
      background: linear-gradient(90deg, var(--blue), var(--blue-light) 60%, transparent 90%);
    }
    .monster-card.far   .card-accent  { background: linear-gradient(90deg, #a89880, #c8b898 60%, transparent 90%); }
    .monster-card.nearby .card-accent { background: linear-gradient(90deg, var(--blue), var(--blue-light) 60%, transparent 90%); }

    /* Corner ribbon */
    .card-ribbon {
      position: absolute; top: 0; right: 0;
      width: 0; height: 0; border-style: solid;
      border-width: 0 44px 44px 0;
      border-color: transparent var(--blue) transparent transparent;
      opacity: 0.18;
    }
    .monster-card.far .card-ribbon { border-color: transparent #a89880 transparent transparent; }

    /* Icon wrap — matches nav-card .nav-icon-wrap */
    .card-icon-wrap {
      width: 70px; height: 70px; border-radius: 14px; flex-shrink: 0;
      background: linear-gradient(145deg, #d8e4ff, #b8caff);
      border: 2px solid #90a8e8;
      display: flex; align-items: center; justify-content: center;
      font-size: 2rem;
      box-shadow: 0 4px 0 #8090d0, inset 0 1px 0 rgba(255,255,255,0.9);
      position: relative; z-index: 1;
      transition: transform .18s;
    }
    .monster-card.far .card-icon-wrap {
      background: linear-gradient(145deg, #ede6d8, #e0d8cc);
      border-color: #c8b898; box-shadow: 0 3px 0 #a89880;
    }
    .monster-card.nearby:hover .card-icon-wrap { transform: scale(1.1) rotate(-5deg); }

    /* Card content — matches nav-card .nav-content */
    .card-content { flex: 1; position: relative; z-index: 1; min-width: 0; }
    .card-name {
      font-size: 1.15rem; font-weight: 900; letter-spacing: .06em;
      color: #1a1030; text-transform: uppercase; margin-bottom: 1px;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .card-dist {
      font-size: .88rem; color: #7a6a50; font-weight: 700; margin-bottom: 1px;
    }
    .card-dist.nearby { color: #4460c0; }
    .card-dist.far    { color: #8a7a60; }
    .card-type-badge {
      display: inline-flex; align-items: center;
      font-family: 'Press Start 2P', monospace; font-size: .3rem;
      background: #d8e4ff; color: #2240a0;
      border: 1px solid #90a8e8; border-radius: 4px;
      padding: 4px 9px; letter-spacing: .05em;
    }
    .monster-card.far  .card-type-badge { background: #ede6d8; color: #8a7a60; border-color: #c8b898; }

    /* Catch button — matches .nav-card style with red accent */
    .catch-btn {
      font-family: 'Press Start 2P', monospace; font-size: 0.38rem;
      letter-spacing: 0.06em; color: #fff;
      background: linear-gradient(180deg, var(--red-light), var(--red));
      border: 2px solid #660000; border-radius: 8px;
      padding: 8px 14px; cursor: pointer; white-space: nowrap;
      box-shadow: 0 3px 0 #440000, inset 0 1px 0 rgba(255,255,255,0.2);
      transition: transform .12s, box-shadow .12s;
      position: relative; z-index: 1; flex-shrink: 0;
    }
    .catch-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 0 #440000, 0 8px 20px rgba(204,0,0,0.3);
    }
    .catch-btn:active {
      transform: translateY(1px);
      box-shadow: 0 1px 0 #440000;
    }

    /* ─── LEAFLET POPUP STYLING ──────────────────── */
    .leaflet-popup-content-wrapper {
      background: #fefcf8 !important;
      border: 2px solid #90a8e8 !important;
      color: #1a1030 !important;
      border-radius: 12px !important;
      box-shadow: 0 6px 24px rgba(0,0,0,0.3) !important;
    }
    .leaflet-popup-tip { background: #fefcf8 !important; }
    .leaflet-popup-content {
      font-family: 'Nunito', sans-serif; font-weight: 700;
      font-size: 0.85rem; line-height: 1.5;
    }
    .leaflet-popup-content b { color: var(--blue); }
    .popup-catch-btn {
      display: inline-block; margin-top: 6px;
      font-family: 'Press Start 2P', monospace; font-size: 0.28rem;
      letter-spacing: 0.06em; color: #fff;
      background: linear-gradient(180deg, var(--red-light), var(--red));
      border: 2px solid #660000; border-radius: 6px;
      padding: 6px 10px; cursor: pointer;
      box-shadow: 0 3px 0 #440000;
    }

    /* ─── TOAST ──────────────────────────────────── */
    .toast {
      position: fixed; bottom: 28px; left: 50%;
      transform: translateX(-50%) translateY(80px);
      background: linear-gradient(145deg, #1a1840, #0e0c28);
      border: 2px solid var(--blue); border-radius: 10px;
      padding: 12px 24px;
      font-family: 'Press Start 2P', monospace; font-size: .38rem; color: var(--yellow);
      z-index: 9999; pointer-events: none; white-space: nowrap; letter-spacing: .1em;
      transition: transform .4s cubic-bezier(.34,1.56,.64,1);
      box-shadow: 0 4px 0 #1a2060, 0 12px 40px rgba(0,0,0,.7), 0 0 30px rgba(59,76,202,.35);
    }
    .toast::before { content: '▶ '; color: var(--blue-light); }
    .toast.show { transform: translateX(-50%) translateY(0); }

    /* ─── BOTTOM BAR ─────────────────────────────── */
    .bottom-bar {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 28px;
      background: rgba(8,6,20,0.92);
      border-top: 2px solid #1e2060;
      font-size: .68rem; color: #4a4a7a; letter-spacing: .08em;
      animation: fadeUp .5s .5s ease both;
      position: relative; z-index: 1;
    }
    .bottom-bar-left { display: flex; align-items: center; gap: 10px; }
    .pixel-dots { display: flex; gap: 5px; }
    .pixel-dot { width: 7px; height: 7px; border-radius: 2px; }
    .pixel-dot:nth-child(1){ background: #1e2060; }
    .pixel-dot:nth-child(2){ background: var(--blue); }
    .pixel-dot:nth-child(3){ background: var(--blue-light); opacity: .7; }
    .version-tag {
      font-family: 'Press Start 2P', monospace; font-size: .3rem;
      color: var(--yellow); letter-spacing: .08em; opacity: .7;
    }

    /* ─── ANIMATIONS ─────────────────────────────── */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(18px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ─── RESPONSIVE ─────────────────────────────── */

    /* Desktop (901px+) */
    @media (min-width: 901px) {
      .map-wrap { height: 500px; }
      .monster-grid { grid-template-columns: 1fr; }
    }

    /* Tablet (481–900px) */
    @media (min-width: 481px) and (max-width: 900px) {
      .main { padding: 22px 20px 48px; }
      .topbar { padding: 0 20px; }
      .map-wrap { height: 420px; }
    }

    /* Mobile (≤480px) */
    @media (max-width: 480px) {
      .topbar { padding: 0 14px; height: 56px; }
      .brand-logo { font-size: .85rem; }
      .brand-badge { display: none; }
      .main { padding: 14px 12px 40px; }

      /* Page header — tighten title and shrink BACK button */
      .page-header { margin-bottom: 16px; gap: 8px; align-items: center; }
      .page-eyebrow { font-size: 0.28rem; margin-bottom: 4px; }
      .page-title-main { font-size: 1.4rem; }
      .topbar-btn { font-size: 0.26rem; padding: 0 10px; height: 34px; }
      .topbar-btn svg { width: 12px; height: 12px; }

      /* Map card header — ensure SCAN stays inline, never wraps */
      .map-card-header { padding: 12px 14px; gap: 10px; flex-wrap: nowrap; }
      .map-icon-pill { width: 40px; height: 40px; font-size: 1.1rem; }
      .map-card-title { font-size: .95rem; }
      .map-card-sub { font-size: .72rem; margin-bottom: 1px; }
      .map-type-badge { font-size: .22rem; padding: 3px 7px; }
      .scan-btn { font-size: 0.32rem; padding: 9px 12px; gap: 5px; }

      .map-wrap { height: 340px; }
      .bottom-bar { padding: 12px 14px; font-size: .6rem; }
      .monster-card { gap: 10px; padding: 12px 12px; }
      .card-icon-wrap { width: 44px; height: 44px; }

      .card-name { margin-bottom: 0; }
      .card-dist { margin-bottom: 2px; }
    }

    /* Very small screens (≤360px) */
    @media (max-width: 360px) {
      .topbar-btn span { display: none; }
    }
  </style>
</head>
<body>

<div class="stars" id="stars"></div>
<div class="bg-balls" id="bgBalls"></div>
<div id="flash-overlay"></div>
<div class="toast" id="toast"></div>

<!-- ═══ TOP BAR ════════════════════════════════════ -->
<header class="topbar">
  <div class="brand">
    <div class="brand-logo">HAUPokémon</div>
    <div class="brand-badge">FIELD</div>
  </div>
  <div class="topbar-right">
    <div class="power-led"></div>
    <div class="player-chip">
      <div class="player-avatar">🧢</div>
      <?php echo htmlspecialchars($player_name); ?>
    </div>
    <button class="topbar-refresh-btn" onclick="resetScan()" title="Reset Scan">
      <svg viewBox="0 0 24 24"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
    </button>
  </div>
</header>

<!-- ═══ MAIN ════════════════════════════════════════ -->
<main class="main">

  <!-- Page Header -->
  <div class="page-header">

  <!-- LEFT SIDE -->
  <div>
    <div class="page-eyebrow">// FIELD SCANNER</div>
    <div class="page-title-main">Catch <span>Monsters</span>.</div>
  </div>

  <!-- RIGHT SIDE -->
  <a class="topbar-btn" href="player_dashboard.php">
    <svg viewBox="0 0 24 24">
      <polyline points="15 18 9 12 15 6"/>
    </svg>
    <span>BACK</span>
  </a>

</div>

  <!-- ── MAIN CONTENT COLUMNS ───────────────────── -->
  <div class="content-cols">

    <!-- ── MAP CARD (hero) ───────────────────────── -->
    <div class="map-card" style="animation: fadeUp 0.5s 0.08s ease both;">

      <!-- Map Header -->
      <div class="map-card-header">
        <div class="map-card-title-row">
          <div class="map-icon-pill">🗺️</div>
          <div>
            <div class="map-card-title">Monster Radar</div>
            <div class="map-card-sub">GPS-based detection zone</div>
            <div class="map-type-badge" style="margin-top:2px;display:inline-flex;">EXPLORER</div>
          </div>
        </div>
        <div class="map-ctrl-row">
          <button class="scan-btn" id="scan-btn" onclick="scanMonsters()">⚡ SCAN</button>
        </div>
      </div>

      <!-- Map -->
      <div class="map-wrap">
        <div id="map"></div>

        <!-- Idle overlay -->
        <div class="map-radar-idle" id="radarIdle">
          <div class="radar-icon">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="2"/><path d="M16.24 7.76a6 6 0 0 1 0 8.49m-8.48-.01a6 6 0 0 1 0-8.49m11.31-2.82a10 10 0 0 1 0 14.14m-14.14 0a10 10 0 0 1 0-14.14"/></svg>
          </div>
          <div class="radar-label">MONSTER RADAR OFFLINE</div>
          <div class="radar-hint">TAP ⚡ SCAN TO DETECT</div>
        </div>

        <!-- Scan rings -->
        <div class="map-scanning-ring" id="scanRings">
          <div class="scan-ring"></div>
          <div class="scan-ring"></div>
          <div class="scan-ring"></div>
        </div>

        <!-- Pokeball watermark -->
        <svg class="map-pb" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
          <circle cx="50" cy="50" r="48" fill="none" stroke="#3b4cca" stroke-width="6"/>
          <path d="M2,50 A48,48 0 0,1 98,50" fill="#3b4cca"/>
          <rect x="2" y="46" width="96" height="8" fill="#3b4cca"/>
          <circle cx="50" cy="50" r="13" fill="none" stroke="#3b4cca" stroke-width="6"/>
          <circle cx="50" cy="50" r="7" fill="#3b4cca"/>
        </svg>
      </div>

      <!-- Status bar -->
      <div class="map-status-bar" id="status-bar">
        <div class="status-led"></div>
        <span id="status-text">READY — HIT SCAN TO BEGIN</span>
      </div>
    </div><!-- /.map-card -->

    <!-- ── MONSTER LIST PANEL ─────────────────────── -->
    <div class="monster-section">
      <div class="section-label">
        <div class="section-label-pip"></div>
        <span class="section-label-text" id="panelTitle">Nearby Monsters (0)</span>
        <div class="section-label-line"></div>
      </div>

      <div id="monster-list">
        <div class="empty-card" id="emptyState">
          <div class="empty-icon">📡</div>
          <div class="empty-title">ENABLE GPS<br>&amp; PRESS SCAN</div>
          <div class="empty-sub">Monsters are waiting nearby...</div>
        </div>
      </div>
    </div><!-- /.monster-section -->

  </div><!-- /.content-cols -->

</main>

<!-- ═══ BOTTOM BAR ══════════════════════════════════ -->
<footer class="bottom-bar">
  <div class="bottom-bar-left">
    <div class="pixel-dots">
      <div class="pixel-dot"></div>
      <div class="pixel-dot"></div>
      <div class="pixel-dot"></div>
    </div>
    BADETS © CLOUD SYSTEMS INTL
  </div>
  <div class="version-tag">HAUPokémon</div>
</footer>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
var monsters = <?php echo json_encode($monsters ?? []); ?>;

/* ═══ STATE ════════════════════════════════════════ */
var map = null;
var found = false;
var audioCtx = null;
var audioUnlocked = false;
var pendingAlert = false;

/* ═══ TOAST ════════════════════════════════════════ */
function showToast(msg) {
  var t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(t._tid);
  t._tid = setTimeout(function() { t.classList.remove('show'); }, 2600);
}

/* ═══ STATUS HELPERS ═══════════════════════════════ */
function setStatus(msg, cls) {
  var bar  = document.getElementById('status-bar');
  var text = document.getElementById('status-text');
  text.textContent = msg;
  bar.className = 'map-status-bar' + (cls ? ' ' + cls : '');
}
function setPanelTitle(msg) {
  document.getElementById('panelTitle').textContent = msg;
}

/* ═══ RESET ════════════════════════════════════════ */
function resetScan() {
  document.getElementById('monster-list').innerHTML =
    '<div class="empty-card" id="emptyState">' +
      '<div class="empty-icon">📡</div>' +
      '<div class="empty-title">ENABLE GPS<br>&amp; PRESS SCAN</div>' +
      '<div class="empty-sub">Monsters are waiting nearby...</div>' +
    '</div>';
  setStatus('READY — HIT SCAN TO BEGIN', '');
  setPanelTitle('Nearby Monsters (0)');
  var btn = document.getElementById('scan-btn');
  btn.disabled = false;
  btn.classList.remove('scanning');
  btn.textContent = '⚡ SCAN';
  document.getElementById('radarIdle').classList.remove('hidden');
  document.getElementById('scanRings').classList.remove('active');
}

/* ═══ AUDIO UNLOCK ═════════════════════════════════ */
function unlockAudio() {
  if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
  audioCtx.resume().then(function() {
    if (audioCtx.state === 'running') {
      var buf = audioCtx.createBuffer(1, 1, 22050);
      var src = audioCtx.createBufferSource();
      src.buffer = buf;
      src.connect(audioCtx.destination);
      src.start(0);
      audioUnlocked = true;
    }
  });
}

function playBeep(frequency, duration, startTime) {
  if (!audioCtx) return;
  var osc  = audioCtx.createOscillator();
  var gain = audioCtx.createGain();
  osc.connect(gain);
  gain.connect(audioCtx.destination);
  osc.type = 'square';
  osc.frequency.value = frequency;
  gain.gain.setValueAtTime(0.3, audioCtx.currentTime + startTime);
  gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + startTime + duration);
  osc.start(audioCtx.currentTime + startTime);
  osc.stop(audioCtx.currentTime + startTime + duration);
}

/* ═══ ALERT ════════════════════════════════════════ */
async function triggerAlert() {
  for (var i = 0; i < 10; i++) playBeep(880, 0.3, i * 0.5);
  if (navigator.vibrate) navigator.vibrate([500,200,500,200,500,200,500,200,500]);

  var flashes = 0;
  var overlay = document.getElementById('flash-overlay');
  var flashInterval = setInterval(function() {
    if (flashes % 2 === 0) {
      overlay.style.background = 'rgba(255,222,0,0.1)';
      overlay.classList.add('flash');
    } else {
      overlay.classList.remove('flash');
    }
    flashes++;
    if (flashes >= 10) { clearInterval(flashInterval); overlay.classList.remove('flash'); }
  }, 300);

  try {
    var stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
    var track  = stream.getVideoTracks()[0];
    await new Promise(function(r) { setTimeout(r, 500); });
    var capabilities = track.getCapabilities();
    if (capabilities.torch) {
      var endTime = Date.now() + 5000;
      var torchOn = false;
      var ti = setInterval(async function() {
        if (Date.now() >= endTime) {
          clearInterval(ti);
          await track.applyConstraints({ advanced: [{ torch: false }] });
          stream.getTracks().forEach(function(t) { t.stop(); });
          return;
        }
        torchOn = !torchOn;
        try { await track.applyConstraints({ advanced: [{ torch: torchOn }] }); } catch(e) {}
      }, 300);
    } else {
      stream.getTracks().forEach(function(t) { t.stop(); });
    }
  } catch(err) {}
}

/* ═══ SCAN ══════════════════════════════════════════ */
function scanMonsters() {
  if (!navigator.geolocation) {
    showToast('❌ GEOLOCATION NOT SUPPORTED');
    return;
  }

  unlockAudio();
  if (navigator.vibrate) navigator.vibrate(0);
  pendingAlert = true;

  setStatus('📡 ACQUIRING GPS SIGNAL...', 'scanning');
  setPanelTitle('Scanning...');

  var btn = document.getElementById('scan-btn');
  btn.disabled = true;
  btn.classList.add('scanning');
  btn.textContent = '...';

  document.getElementById('radarIdle').classList.add('hidden');
  document.getElementById('scanRings').classList.add('active');

  navigator.geolocation.getCurrentPosition(function(position) {
    var playerLat = position.coords.latitude;
    var playerLng = position.coords.longitude;

    /* Init or re-center map */
    if (!map) {
      map = L.map('map', { zoomControl: false, attributionControl: false })
               .setView([playerLat, playerLng], 16);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    } else {
      map.setView([playerLat, playerLng], 16);
    }

    /* Player marker */
    var playerIcon = L.divIcon({
      className: '',
      html: '<div style="width:14px;height:14px;border-radius:50%;background:#ffde00;border:3px solid #fff;box-shadow:0 0 14px rgba(255,222,0,0.9);"></div>',
      iconSize: [14, 14], iconAnchor: [7, 7]
    });
    L.marker([playerLat, playerLng], { icon: playerIcon }).addTo(map)
      .bindPopup('<b>📍 YOU ARE HERE</b>');

    found = false;
    var nearbyCount = 0;
    var nearbyHTML  = '';

    monsters.forEach(function(monster) {
      var mLat   = parseFloat(monster.spawn_latitude);
      var mLng   = parseFloat(monster.spawn_longitude);
      var radius = monster.spawn_radius_meters || 500;
      var monsterType = monster.type || 'WILD TYPE';

      var distance = 111139 * Math.sqrt(
        Math.pow(playerLat - mLat, 2) + Math.pow(playerLng - mLng, 2)
      );
      var isNearby = distance <= radius;

      /* Monster map marker — show all on map regardless of range */
      var mIcon = L.divIcon({
        className: '',
        html: '<div style="font-size:32px;filter:drop-shadow(0 0 8px ' + (isNearby ? '#ffde00' : '#444') + ');">' +
              (monster.picture_url
                ? '<img src="' + monster.picture_url + '" style="width:36px;height:36px;object-fit:contain;">'
                : '🦖') +
              '</div>',
        iconSize: [44, 44], iconAnchor: [22, 22]
      });
      var marker = L.marker([mLat, mLng], { icon: mIcon, opacity: isNearby ? 1 : 0.4 }).addTo(map);

      /* Only build cards for nearby monsters */
      if (isNearby) {
        found = true;
        nearbyCount++;

        var pbColor = '#3b4cca';
        var cardHTML =
          '<div class="monster-card nearby">' +
            '<div class="card-stripe"></div>' +
            '<div class="card-ribbon"></div>' +
            '<svg class="card-pb" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">' +
              '<circle cx="50" cy="50" r="48" fill="none" stroke="' + pbColor + '" stroke-width="6"/>' +
              '<path d="M2,50 A48,48 0 0,1 98,50" fill="' + pbColor + '"/>' +
              '<rect x="2" y="46" width="96" height="8" fill="' + pbColor + '"/>' +
              '<circle cx="50" cy="50" r="13" fill="none" stroke="' + pbColor + '" stroke-width="6"/>' +
              '<circle cx="50" cy="50" r="7" fill="' + pbColor + '"/>' +
            '</svg>' +
            '<div class="card-accent"></div>' +
            '<div class="card-icon-wrap">' +
              (monster.picture_url
                ? '<img src="' + monster.picture_url + '" style="width:36px;height:36px;object-fit:contain;">'
                : '🦖') +
            '</div>' +
            '<div class="card-content">' +
              '<div class="card-name">' + monster.monster_name + '</div>' +
              '<div class="card-dist nearby">📏 ' + distance.toFixed(1) + 'M AWAY</div>' +
              '<div class="card-type-badge">' + monsterType + '</div>' +
            '</div>' +
            '<button class="catch-btn" onclick="catchMonster(' +
              monster.monster_id + ',' + playerLat + ',' + playerLng + ')">⚡ CATCH</button>' +
          '</div>';

        nearbyHTML += cardHTML;

        marker.bindPopup(
          '<b>' + monster.monster_name + '</b><br>' +
          '<span style="color:#3b4cca;font-size:0.78rem;">📏 ' + distance.toFixed(1) + 'm away</span><br>' +
          '<button class="popup-catch-btn" onclick="catchMonster(' + monster.monster_id + ',' + playerLat + ',' + playerLng + ')">⚡ CATCH</button>'
        );
      } else {
        marker.bindPopup(
          '<b>' + monster.monster_name + '</b><br>' +
          '<span style="color:#8a7a60;font-size:0.78rem;">📏 ' + distance.toFixed(1) + 'm away — out of range</span>'
        );
      }
    });

    document.getElementById('scanRings').classList.remove('active');

    if (!found) {
      pendingAlert = false;
      document.getElementById('monster-list').innerHTML =
        '<div class="empty-card">' +
          '<div class="empty-icon">🔍</div>' +
          '<div class="empty-title">NO MONSTERS<br>NEARBY</div>' +
          '<div class="empty-sub">Try moving to a different area</div>' +
        '</div>';
      setStatus('NO MONSTERS DETECTED IN YOUR AREA', '');
      setPanelTitle('Nearby Monsters (0)');
      showToast('❌ NO MONSTERS NEARBY');
    } else {
      var gridHTML = '<div class="monster-grid">' + nearbyHTML + '</div>';
      document.getElementById('monster-list').innerHTML = gridHTML;
      setStatus('⚠️ ' + nearbyCount + ' MONSTER' + (nearbyCount > 1 ? 'S' : '') + ' DETECTED NEARBY!', 'alert');
      setPanelTitle('Nearby Monsters (' + nearbyCount + ')');
      showToast('⚡ ' + nearbyCount + ' MONSTER' + (nearbyCount > 1 ? 'S' : '') + ' DETECTED!');
      if (pendingAlert) { triggerAlert(); pendingAlert = false; }
    }

    btn.disabled = false;
    btn.classList.remove('scanning');
    btn.textContent = '⚡ SCAN';

  }, function(error) {
    pendingAlert = false;
    document.getElementById('scanRings').classList.remove('active');
    document.getElementById('radarIdle').classList.remove('hidden');
    setStatus('❌ LOCATION ACCESS DENIED', '');
    setPanelTitle('Nearby Monsters (0)');
    showToast('❌ GPS ACCESS DENIED');
    var btn = document.getElementById('scan-btn');
    btn.disabled = false;
    btn.classList.remove('scanning');
    btn.textContent = '⚡ SCAN';
  }, {
    enableHighAccuracy: true,
    timeout: 10000,
    maximumAge: 0
  });
}

/* ═══ CATCH ═════════════════════════════════════════ */
function catchMonster(monsterId, lat, lng) {
  window.location.href = "catch.php?monster_id=" + monsterId + "&lat=" + lat + "&lng=" + lng;
}

/* ═══ STARS ═════════════════════════════════════════ */
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

/* ═══ FLOATING POKÉBALLS ════════════════════════════ */
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