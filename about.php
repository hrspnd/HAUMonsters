<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}
$username = $_SESSION['username'] ?? 'Trainer';
$role     = $_SESSION['role'] ?? 'user';
$back     = ($role === 'admin') ? 'admin_dashboard.php' : 'player_dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>About Us — HAUPokémon</title>
<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Nunito:wght@700;800;900&display=swap" rel="stylesheet">
<link href="https://fonts.cdnfonts.com/css/pokemon-solid" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --red:        #cc0000;
  --red-dark:   #8b0000;
  --red-light:  #ff4444;
  --red-glow:   rgba(204,0,0,0.5);
  --yellow:     #ffde00;
  --yellow-dk:  #b8a000;
  --yellow-glow:rgba(255,222,0,0.4);
  --blue:       #3b4cca;
  --blue-light: #6878f0;
  --blue-glow:  rgba(59,76,202,0.4);
  --green:      #00a550;
  --green-light:#4ade80;
}

body {
  min-height: 100vh;
  background: #0d0d1a;
  background-image:
    radial-gradient(ellipse at 15% 20%, rgba(59,76,202,0.28) 0%, transparent 45%),
    radial-gradient(ellipse at 85% 80%, rgba(204,0,0,0.25) 0%, transparent 45%);
  font-family: 'Nunito', sans-serif;
  font-weight: 700;
  color: #f0eaff;
  overflow-x: hidden;
}

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

.bg-balls { position: fixed; inset: 0; pointer-events: none; z-index: 0; overflow: hidden; }
.bg-ball { position: absolute; opacity:.05; animation: bg-float linear infinite; }
@keyframes bg-float {
  0%   { transform: translateY(110vh) rotate(0deg); }
  100% { transform: translateY(-20vh) rotate(720deg); }
}

/* ─── TOP BAR ───────────────────────────────── */
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
  font-size: 1.1rem; color: var(--yellow);
  -webkit-text-stroke: 2px #3b4cca; paint-order: stroke fill;
  text-shadow: 3px 3px 0 #2a36a0, 0 0 28px var(--yellow-glow);
}
.brand-badge {
  font-family: 'Press Start 2P', monospace; font-size: 0.34rem;
  background: linear-gradient(180deg, var(--green-light), var(--green));
  color: #fff; padding: 4px 9px; border-radius: 4px;
  border: 2px solid #006630;
  box-shadow: 0 3px 0 #004420, inset 0 1px 0 rgba(255,255,255,0.2);
  letter-spacing: 0.1em;
}
.topbar-right { display: flex; align-items: center; gap: 14px; }
.power-led {
  width: 10px; height: 10px; border-radius: 50%;
  background: var(--green-light);
  box-shadow: 0 0 8px var(--green-light), 0 0 18px rgba(74,222,128,.5);
  animation: led-pulse 2s ease-in-out infinite;
}
@keyframes led-pulse { 0%,100%{opacity:1} 50%{opacity:.3} }
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
  background: linear-gradient(135deg, var(--green), #006630);
  border: 2px solid #004420;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.72rem;
}
.topbar-btn {
  display: flex; align-items: center; gap: 6px;
  font-family: 'Press Start 2P', monospace; font-size: 0.3rem;
  letter-spacing: 0.08em; color: #f0eaff;
  background: linear-gradient(180deg, #1e1640, #120e28);
  border: 2px solid var(--blue); border-radius: 8px;
  padding: 7px 12px; cursor: pointer; text-decoration: none;
  box-shadow: 0 3px 0 #1a2060; height: 40px;
  transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
}
.topbar-btn:hover {
  border-color: var(--red);
  box-shadow: 0 5px 0 #aa2200, 0 8px 20px rgba(204,0,0,0.2);
  transform: translateY(-2px);
}
.topbar-btn svg { width:14px; height:14px; stroke:var(--yellow); fill:none; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }

/* ─── MAIN ──────────────────────────────────── */
.main {
  padding: 28px 28px 56px;
  max-width: 1100px; margin: 0 auto;
  position: relative; z-index: 1;
}

/* ─── PAGE HEADER ───────────────────────────── */
.page-header {
  display: flex; align-items: flex-end; justify-content: space-between;
  flex-wrap: wrap; gap: 12px;
  margin-bottom: 32px;
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

/* ─── SECTION LABEL ─────────────────────────── */
.section-label {
  display:flex; align-items:center; gap:10px;
  margin-bottom:16px; animation:fadeUp .5s .18s ease both;
}
.section-label-pip {
  width:8px; height:8px; border-radius:2px;
  background:var(--yellow); box-shadow:0 0 8px var(--yellow-glow);
}
.section-label-text {
  font-family:'Press Start 2P',monospace; font-size:.6rem;
  color:var(--yellow); letter-spacing:.22em;
  text-shadow:0 0 14px var(--yellow-glow); white-space:nowrap;
}
.section-label-line {
  flex:1; height:1px;
  background:linear-gradient(90deg,rgba(59,76,202,0.4),transparent);
}

/* ─── BASE CARD ─────────────────────────────── */
.about-card {
  background: linear-gradient(155deg, #ffffff 0%, #f7f2e8 55%, #ede6d8 100%);
  border: 3px solid #ccc4b0;
  border-radius: 18px;
  padding: 24px 26px;
  position: relative; overflow: hidden;
  animation: fadeUp 0.5s ease both;
  box-shadow:
    0 6px 0 #a89880,
    0 10px 28px rgba(0,0,0,0.28),
    inset 0 1px 0 #fff,
    inset 0 -2px 0 rgba(0,0,0,0.04);
  margin-bottom: 20px;
}
.about-card .card-ribbon {
  position: absolute; top: 0; right: 0;
  width: 0; height: 0; border-style: solid;
  border-width: 0 56px 56px 0; opacity: 0.14;
}
.about-card .card-pb {
  position: absolute; right: -22px; bottom: -22px;
  width: 100px; height: 100px; pointer-events: none; opacity: 0.06;
}
.card-header-row {
  display: flex; align-items: center; gap: 14px; margin-bottom: 16px;
}
.card-icon-pill {
  width: 48px; height: 48px; border-radius: 12px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem;
  box-shadow: 0 3px 0 rgba(0,0,0,0.12), inset 0 1px 0 rgba(255,255,255,0.8);
}
.card-icon-pill.red   { background:linear-gradient(145deg,#ffe0e0,#ffc8c8); border:2px solid #f0a8a8; box-shadow:0 3px 0 #d09090,inset 0 1px 0 rgba(255,255,255,0.9); }
.card-icon-pill.blue  { background:linear-gradient(145deg,#d8e4ff,#b8caff); border:2px solid #90a8e8; box-shadow:0 3px 0 #7088c8,inset 0 1px 0 rgba(255,255,255,0.9); }
.card-icon-pill.gold  { background:linear-gradient(145deg,#fff8d0,#ffe898); border:2px solid #e8d060; box-shadow:0 3px 0 #c8a840,inset 0 1px 0 rgba(255,255,255,0.9); }
.card-icon-pill.green { background:linear-gradient(145deg,#d0f8e0,#a8f0c8); border:2px solid #60d890; box-shadow:0 3px 0 #40b870,inset 0 1px 0 rgba(255,255,255,0.9); }
.card-icon-pill.purple{ background:linear-gradient(145deg,#eed8ff,#ddb8ff); border:2px solid #bb88ee; box-shadow:0 3px 0 #9960cc,inset 0 1px 0 rgba(255,255,255,0.9); }

.card-title-block {}
.card-title {
  font-size: 1rem; font-weight: 900; color: #1a1030;
  letter-spacing: .04em; margin-bottom: 3px;
}
.card-chip {
  display: inline-flex; align-items: center;
  font-family: 'Press Start 2P', monospace; font-size: .45rem;
  padding: 3px 8px; border-radius: 4px; letter-spacing: .05em;
}
.chip-red    { background:#ffe0e0; color:#aa2020; border:1px solid #f0b0b0; }
.chip-blue   { background:#d8e4ff; color:#2240a0; border:1px solid #90a8e8; }
.chip-gold   { background:#fff3b0; color:#886600; border:1px solid #e8cc60; }
.chip-green  { background:#d0f8e0; color:#006630; border:1px solid #60d890; }
.chip-purple { background:#f0d8ff; color:#6620a0; border:1px solid #cc88ee; }

.card-body {
  font-size: .86rem; color: #3a2e50; line-height: 1.75;
  position: relative; z-index: 1;
}
.card-body p { margin-bottom: 8px; }
.card-body p:last-child { margin-bottom: 0; }

/* Feature bullets */
.feature-list {
  list-style: none; display: flex; flex-direction: column; gap: 8px;
  position: relative; z-index: 1;
}
.feature-list li {
  display: flex; align-items: flex-start; gap: 10px;
  font-size: .86rem; color: #3a2e50; line-height: 1.5;
}
.feature-list li::before {
  content: '▶';
  font-family: monospace; font-size: .6rem;
  color: var(--red); margin-top: 3px; flex-shrink: 0;
}

/* Team grid */
.team-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 14px; position: relative; z-index: 1;
}
.member-card {
  background: linear-gradient(145deg, #faf6ff, #f0e8ff);
  border: 2px solid #d0b8f0;
  border-radius: 14px; padding: 16px;
  text-align: center;
  box-shadow: 0 4px 0 #b898d8, inset 0 1px 0 #fff;
}
.member-avatar {
  width: 52px; height: 52px; border-radius: 50%; margin: 0 auto 10px;
  background: linear-gradient(135deg, #c890f8, #9060d8);
  border: 3px solid #b070e8;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem;
  box-shadow: 0 3px 0 #7848b8;
}
.member-name {
  font-size: .88rem; font-weight: 900; color: #2a1050;
  margin-bottom: 4px; letter-spacing: .02em;
}
.member-role {
  font-family: 'Press Start 2P', monospace; font-size: .4rem;
  color: #7040b0; letter-spacing: .06em; line-height: 1.6;
}

/* Tech badges */
.tech-wrap {
  display: flex; flex-wrap: wrap; gap: 10px;
  position: relative; z-index: 1;
}
.tech-badge {
  display: inline-flex; align-items: center; gap: 6px;
  background: linear-gradient(145deg, #1e1640, #120e28);
  border: 2px solid var(--blue); border-radius: 8px;
  padding: 7px 14px;
  font-family: 'Press Start 2P', monospace; font-size: .4rem;
  color: var(--yellow); letter-spacing: .07em;
  box-shadow: 0 3px 0 #1a2060;
}
.tech-badge span { font-size: .9rem; }

/* Disclaimer card */
.disclaimer-card {
  background: linear-gradient(155deg, #fff8f0, #fdf0e0);
  border: 2px solid #e8c880; border-left: 5px solid var(--yellow);
  border-radius: 14px; padding: 18px 20px;
  display: flex; align-items: flex-start; gap: 14px;
  position: relative; z-index: 1;
  box-shadow: 0 4px 0 #c8a840, inset 0 1px 0 #fff;
  animation: fadeUp .5s .38s ease both;
  margin-bottom: 20px;
}
.disclaimer-icon { font-size: 1.6rem; flex-shrink: 0; margin-top: 2px; }
.disclaimer-text {
  font-size: .82rem; color: #5a4010; line-height: 1.7;
}
.disclaimer-title {
  font-family: 'Press Start 2P', monospace; font-size: .28rem;
  color: #886600; letter-spacing: .1em; margin-bottom: 6px;
}

/* Version pill */
.version-pill {
  display: inline-flex; align-items: center; gap: 8px;
  background: linear-gradient(145deg, #1e1640, #120e28);
  border: 2px solid var(--blue); border-radius: 30px;
  padding: 8px 20px;
  font-family: 'Press Start 2P', monospace; font-size: .32rem;
  color: var(--yellow); letter-spacing: .1em;
  box-shadow: 0 3px 0 #1a2060, 0 0 18px rgba(59,76,202,0.25);
  animation: fadeUp .5s .42s ease both;
  margin-bottom: 28px;
}
.version-dot {
  width: 8px; height: 8px; border-radius: 50%;
  background: var(--green-light);
  box-shadow: 0 0 6px var(--green-light);
  animation: led-pulse 2s ease-in-out infinite;
}

/* ─── BOTTOM BAR ────────────────────────────── */
.bottom-bar {
  display:flex; align-items:center; justify-content:space-between;
  padding:14px 28px;
  background:rgba(8,6,20,0.92);
  border-top:2px solid #1e2060;
  font-size:.68rem; color:#4a4a7a; letter-spacing:.08em;
  animation:fadeUp .5s .5s ease both;
  position:relative; z-index:1;
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

/* ─── TOAST ─────────────────────────────────── */
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

@keyframes fadeUp {
  from { opacity:0; transform:translateY(18px); }
  to   { opacity:1; transform:translateY(0); }
}

@media (max-width:600px) {
  .topbar { padding:0 16px; }
  .main   { padding:20px 14px 36px; }
  .brand-logo { font-size:.88rem; }
  .page-title-main { font-size:1.5rem; }
  .team-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
</head>
<body>

<div class="stars" id="stars"></div>
<div class="bg-balls" id="bgBalls"></div>
<div class="toast" id="toast"></div>

<!-- TOP BAR -->
<header class="topbar">
  <div class="brand">
    <div class="brand-logo">HAUPokémon</div>
    <div class="brand-badge">PLAYER</div>
  </div>
  <div class="topbar-right">
    <div class="power-led"></div>
    <div class="player-chip">
      <div class="player-avatar">🧢</div>
      <?php echo htmlspecialchars($username); ?>
    </div>
  </div>
</header>

<!-- MAIN -->
<main class="main">

  <!-- Page Header -->
  <div class="page-header">
    <div>
      <div class="page-eyebrow">// INFO</div>
      <div class="page-title-main">About <span>Us</span></div>
    </div>
    <a class="topbar-btn" href="<?php echo $back; ?>">
      <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
      BACK
    </a>
  </div>

  <!-- Version pill -->
  <div class="version-pill">
    <div class="version-dot"></div>
    VERSION 1.0 — STABLE
  </div>

  <!-- ── 1. ABOUT HAUMONSTERS ── -->
  <div class="section-label" style="animation-delay:.05s">
    <div class="section-label-pip"></div>
    <span class="section-label-text">About HAUMonsters</span>
    <div class="section-label-line"></div>
  </div>

  <div class="about-card" style="animation-delay:.08s">
    <div class="card-ribbon" style="border-color:transparent var(--red) transparent transparent;"></div>
    <svg class="card-pb" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
      <circle cx="50" cy="50" r="48" fill="none" stroke="#cc0000" stroke-width="6"/>
      <path d="M2,50 A48,48 0 0,1 98,50" fill="#cc0000"/>
      <rect x="2" y="46" width="96" height="8" fill="#cc0000"/>
      <circle cx="50" cy="50" r="13" fill="none" stroke="#cc0000" stroke-width="6"/>
      <circle cx="50" cy="50" r="7" fill="#cc0000"/>
    </svg>
    <div class="card-header-row">
      <div class="card-icon-pill red">🦕</div>
      <div class="card-title-block">
        <div class="card-title">What is HAUMonsters?</div>
        <span class="card-chip chip-red">OVERVIEW</span>
      </div>
    </div>
    <div class="card-body">
      <p>HAUMonsters is a location-based monster-catching web application developed by students of Holy Angel University. Inspired by the mechanics of classic monster-catching games, it allows players to discover, catch, and collect monsters that are spawned at real-world coordinates on an interactive map.</p>
      <p>The system demonstrates the integration of web technologies with cloud infrastructure, featuring a distributed architecture deployed across multiple AWS regions for availability and scalability.</p>
    </div>
  </div>

  <!-- ── 2. PURPOSE ── -->
  <div class="section-label" style="animation-delay:.10s">
    <div class="section-label-pip"></div>
    <span class="section-label-text">Purpose</span>
    <div class="section-label-line"></div>
  </div>

  <div class="about-card" style="animation-delay:.12s">
    <div class="card-ribbon" style="border-color:transparent #3b4cca transparent transparent;"></div>
    <svg class="card-pb" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
      <circle cx="50" cy="50" r="48" fill="none" stroke="#3b4cca" stroke-width="6"/>
      <path d="M2,50 A48,48 0 0,1 98,50" fill="#3b4cca"/>
      <rect x="2" y="46" width="96" height="8" fill="#3b4cca"/>
      <circle cx="50" cy="50" r="13" fill="none" stroke="#3b4cca" stroke-width="6"/>
      <circle cx="50" cy="50" r="7" fill="#3b4cca"/>
    </svg>
    <div class="card-header-row">
      <div class="card-icon-pill blue">🎯</div>
      <div class="card-title-block">
        <div class="card-title">Purpose of the System</div>
        <span class="card-chip chip-blue">OBJECTIVES</span>
      </div>
    </div>
    <div class="card-body">
      <p>HAUMonsters was developed as an academic project to demonstrate the practical application of cloud computing concepts, distributed systems, and full-stack web development. The system serves as a hands-on implementation of AWS services including EC2, VPC, VPC Peering, Application Load Balancing, and cross-region deployment.</p>
      <p>It also showcases how a real-time, interactive web application can be designed, secured, and deployed using industry-standard technologies in a cloud environment.</p>
    </div>
  </div>

  <!-- ── 3. KEY FEATURES ── -->
  <div class="section-label" style="animation-delay:.14s">
    <div class="section-label-pip"></div>
    <span class="section-label-text">Key Features</span>
    <div class="section-label-line"></div>
  </div>

  <div class="about-card" style="animation-delay:.16s">
    <div class="card-ribbon" style="border-color:transparent #c89000 transparent transparent;"></div>
    <svg class="card-pb" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
      <circle cx="50" cy="50" r="48" fill="none" stroke="#c89000" stroke-width="6"/>
      <path d="M2,50 A48,48 0 0,1 98,50" fill="#c89000"/>
      <rect x="2" y="46" width="96" height="8" fill="#c89000"/>
      <circle cx="50" cy="50" r="13" fill="none" stroke="#c89000" stroke-width="6"/>
      <circle cx="50" cy="50" r="7" fill="#c89000"/>
    </svg>
    <div class="card-header-row">
      <div class="card-icon-pill gold">⭐</div>
      <div class="card-title-block">
        <div class="card-title">What You Can Do</div>
        <span class="card-chip chip-gold">FEATURES</span>
      </div>
    </div>
    <ul class="feature-list">
      <li>Interactive map with real-world GPS-based monster spawn locations powered by Leaflet.js</li>
      <li>Player registration and secure login with bcrypt-hashed passwords</li>
      <li>Monster catching system — encounter and collect monsters near your location</li>
      <li>Personal Pokédex to view all monsters you have caught</li>
      <li>Global leaderboard ranking players by total monsters caught</li>
      <li>Admin panel for managing monsters, players, and spawn locations</li>
      <li>Distributed cloud architecture across two AWS regions (EU–Paris and US–Virginia)</li>
      <li>Application Load Balancer for traffic distribution and high availability</li>
    </ul>
  </div>

  <!-- ── 4. DEVELOPMENT TEAM ── -->
  <div class="section-label" style="animation-delay:.18s">
    <div class="section-label-pip"></div>
    <span class="section-label-text">Development Team</span>
    <div class="section-label-line"></div>
  </div>

  <div class="about-card" style="animation-delay:.20s">
    <div class="card-ribbon" style="border-color:transparent #9940cc transparent transparent;"></div>
    <svg class="card-pb" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
      <circle cx="50" cy="50" r="48" fill="none" stroke="#9940cc" stroke-width="6"/>
      <path d="M2,50 A48,48 0 0,1 98,50" fill="#9940cc"/>
      <rect x="2" y="46" width="96" height="8" fill="#9940cc"/>
      <circle cx="50" cy="50" r="13" fill="none" stroke="#9940cc" stroke-width="6"/>
      <circle cx="50" cy="50" r="7" fill="#9940cc"/>
    </svg>
    <div class="card-header-row">
      <div class="card-icon-pill purple">👥</div>
      <div class="card-title-block">
        <div class="card-title">Meet the Team</div>
        <span class="card-chip chip-purple">CS - 302</span>
      </div>
    </div>
    <div class="team-grid">
      <div class="member-card">
        <div class="member-avatar">👩🏻‍🎨</div>
        <div class="member-name">Bermudo, Jeanne Clarisse T.</div>
        <div class="member-role">Frontend Dev</div>
      </div>
      <div class="member-card">
        <div class="member-avatar">👩🏻‍🏫</div>
        <div class="member-name">Magat, Maria Josephine M.</div>
        <div class="member-role">UX Dev &<br>Documentation</div>
      </div>
      <div class="member-card">
        <div class="member-avatar">🤹🏻‍♀️</div>
        <div class="member-name">Pineda, Mary Alexa Ysabelle V.</div>
        <div class="member-role">Full-Stack Dev &<br>Documentation</div>
      </div>
      <div class="member-card">
        <div class="member-avatar">👩🏻‍🔬</div>
        <div class="member-name">Rebusa, Amber Kaia J.</div>
        <div class="member-role">Lead Frontend Dev</div>
      </div>
    </div>
  </div>

  <!-- ── 5. TECHNOLOGIES USED ── -->
  <div class="section-label" style="animation-delay:.24s">
    <div class="section-label-pip"></div>
    <span class="section-label-text">Technologies Used</span>
    <div class="section-label-line"></div>
  </div>

  <div class="about-card" style="animation-delay:.26s">
    <div class="card-ribbon" style="border-color:transparent var(--green) transparent transparent;"></div>
    <svg class="card-pb" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
      <circle cx="50" cy="50" r="48" fill="none" stroke="#00a550" stroke-width="6"/>
      <path d="M2,50 A48,48 0 0,1 98,50" fill="#00a550"/>
      <rect x="2" y="46" width="96" height="8" fill="#00a550"/>
      <circle cx="50" cy="50" r="13" fill="none" stroke="#00a550" stroke-width="6"/>
      <circle cx="50" cy="50" r="7" fill="#00a550"/>
    </svg>
    <div class="card-header-row">
      <div class="card-icon-pill green">🛠️</div>
      <div class="card-title-block">
        <div class="card-title">Tech Stack</div>
        <span class="card-chip chip-green">STACK</span>
      </div>
    </div>
    <div class="tech-wrap">
      <div class="tech-badge"><span>🐘</span> PHP</div>
      <div class="tech-badge"><span>🐬</span> MySQL / MariaDB</div>
      <div class="tech-badge"><span>🌐</span> HTML / CSS / JS</div>
      <div class="tech-badge"><span>🗺️</span> Leaflet.js</div>
      <div class="tech-badge"><span>☁️</span> AWS EC2</div>
      <div class="tech-badge"><span>⚖️</span> AWS ALB</div>
      <div class="tech-badge"><span>🔗</span> AWS VPC Peering</div>
      <div class="tech-badge"><span>💾</span> AWS EBS</div>
      <div class="tech-badge"><span>🔒</span> Apache</div>
      <div class="tech-badge"><span>🔑</span> bcrypt</div>
    </div>
  </div>

  <!-- ── 6. DISCLAIMER ── -->
  <div class="section-label" style="animation-delay:.30s">
    <div class="section-label-pip"></div>
    <span class="section-label-text">Disclaimer</span>
    <div class="section-label-line"></div>
  </div>

  <div class="disclaimer-card">
    <div class="disclaimer-icon">⚠️</div>
    <div class="disclaimer-text">
      <div class="disclaimer-title">ACADEMIC USE ONLY</div>
      This system was developed solely for academic purposes as a course requirement at Holy Angel University. It is not intended for commercial use, public deployment, or any form of monetization. All game mechanics, monster designs, and system concepts are original work created for educational demonstration. Any resemblance to existing commercial products is purely coincidental.
    </div>
  </div>

</main>

<!-- BOTTOM BAR -->
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

<script>
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg.toUpperCase();
  t.classList.add('show');
  clearTimeout(t._tid);
  t._tid = setTimeout(() => t.classList.remove('show'), 2500);
}

/* Stars */
(function() {
  const c = document.getElementById('stars');
  for (let i = 0; i < 55; i++) {
    const s = document.createElement('div');
    s.className = 'star';
    s.style.left = Math.random() * 100 + 'vw';
    s.style.top  = Math.random() * 100 + 'vh';
    s.style.animationDuration = (1.5 + Math.random() * 2.5) + 's';
    s.style.animationDelay    = (Math.random() * 2.5) + 's';
    c.appendChild(s);
  }
})();

/* Floating pokéballs */
(function() {
  const c = document.getElementById('bgBalls');
  [60,90,50,120,70,80,55].forEach((sz, i) => {
    const w = document.createElement('div');
    w.className = 'bg-ball';
    w.style.left = (8 + i * 13 + Math.random() * 10) + 'vw';
    w.style.bottom = '-120px';
    w.style.animationDuration = (18 + Math.random() * 14) + 's';
    w.style.animationDelay    = (i * 3 + Math.random() * 4) + 's';
    w.innerHTML = `<svg width="${sz}" height="${sz}" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
      <circle cx="50" cy="50" r="48" fill="none" stroke="white" stroke-width="5"/>
      <path d="M2,50 A48,48 0 0,1 98,50 Z" fill="white" opacity=".7"/>
      <rect x="2" y="46" width="96" height="8" fill="white"/>
      <circle cx="50" cy="50" r="13" fill="none" stroke="white" stroke-width="5"/>
      <circle cx="50" cy="50" r="7" fill="white"/>
    </svg>`;
    c.appendChild(w);
  });
})();
</script>
</body>
</html>