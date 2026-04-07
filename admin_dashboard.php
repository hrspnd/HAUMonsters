<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("10.1.1.17", "badets", "badets@1234", "haumonstersDB");

$result          = $conn->query("SELECT COUNT(*) AS total FROM monsterstbl");
$total_monsters  = $result ? $result->fetch_assoc()['total'] : 0;

$result          = $conn->query("SELECT COUNT(*) AS total FROM playerstbl WHERE role != 'admin'");
$active_trainers = $result ? $result->fetch_assoc()['total'] : 0;

$result          = $conn->query("SELECT COUNT(*) AS total FROM monster_catchestbl");
$total_catches   = $result ? $result->fetch_assoc()['total'] : 0;

$admin_name      = $_SESSION['username'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin Dashboard — HAUPokémon</title>
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

/* ─── BODY — matches EC2-Boy exactly ────────── */
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
.topbar-right { display: flex; align-items: center; gap: 14px; }
.power-led {
  width: 10px; height: 10px; border-radius: 50%;
  background: var(--green-light);
  box-shadow: 0 0 8px var(--green-light), 0 0 18px rgba(74,222,128,.5);
  animation: led-pulse 2s ease-in-out infinite;
}
@keyframes led-pulse { 0%,100%{opacity:1} 50%{opacity:.3} }
.admin-chip {
  display: flex; align-items: center; gap: 8px;
  background: linear-gradient(180deg, #1e1640, #120e28);
  border: 2px solid var(--blue); border-radius: 8px;
  padding: 5px 14px 5px 8px;
  font-size: 0.8rem; font-weight: 900; letter-spacing: 0.08em;
  box-shadow: 0 3px 0 #1a2060;
}
.admin-avatar {
  width: 26px; height: 26px; border-radius: 6px;
  background: linear-gradient(135deg, var(--red), var(--red-dark));
  border: 2px solid #660000;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.72rem;
}

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
.clock-box {
  background: linear-gradient(145deg, #0a0a18, #111130);
  border: 2px solid var(--blue); border-radius: 10px;
  padding: 10px 18px; text-align: center;
  box-shadow: inset 0 2px 8px rgba(0,0,0,0.5), 0 4px 0 #1a2060, 0 0 20px rgba(59,76,202,0.2);
}
.clock-time {
  font-family: 'Press Start 2P', monospace; font-size: 0.62rem;
  color: var(--green-light); text-shadow: 0 0 12px var(--green-light);
  letter-spacing: 0.08em;
}
.clock-label {
  font-family: 'Press Start 2P', monospace; font-size: 0.28rem;
  color: #5a6090; letter-spacing: 0.14em; margin-top: 4px;
}

/* ─── STAT STRIP ────────────────────────────── */
.stat-strip {
  display: grid; grid-template-columns: repeat(3,1fr);
  gap: 16px; margin-bottom: 32px;
}

.stat-card {
  background: linear-gradient(155deg, #ffffff 0%, #f7f2e8 55%, #ede6d8 100%);
  border: 3px solid #ccc4b0;
  border-radius: 18px;
  padding: 20px 20px 16px;
  position: relative; overflow: hidden;
  animation: fadeUp 0.5s ease both;
  transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
  box-shadow:
    0 6px 0 #a89880,
    0 10px 28px rgba(0,0,0,0.28),
    inset 0 1px 0 #fff,
    inset 0 -2px 0 rgba(0,0,0,0.04);
}
.stat-card:nth-child(1) { animation-delay: .05s; }
.stat-card:nth-child(2) { animation-delay: .10s; }
.stat-card:nth-child(3) { animation-delay: .15s; }


/* Coloured corner triangle ribbon */
.stat-ribbon {
  position: absolute; top: 0; right: 0;
  width: 0; height: 0;
  border-style: solid;
  border-width: 0 52px 52px 0;
  opacity: 0.2;
}
.stat-card:nth-child(1) .stat-ribbon { border-color: transparent var(--red) transparent transparent; }
.stat-card:nth-child(2) .stat-ribbon { border-color: transparent #c89000 transparent transparent; }
.stat-card:nth-child(3) .stat-ribbon { border-color: transparent var(--blue) transparent transparent; }

/* Pokeball SVG watermark bottom-right */
.stat-pokeball-bg {
  position: absolute; right: -22px; bottom: -22px;
  width: 100px; height: 100px; pointer-events: none; opacity: 0.07;
}

.stat-top-row {
  display: flex; align-items: flex-start; justify-content: space-between;
  margin-bottom: 12px;
}
.stat-icon-pill {
  width: 46px; height: 46px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem;
  box-shadow: 0 3px 0 rgba(0,0,0,0.12), inset 0 1px 0 rgba(255,255,255,0.8);
}
.stat-card:nth-child(1) .stat-icon-pill { background: linear-gradient(145deg,#ffe0e0,#ffc8c8); border:2px solid #f0a8a8; }
.stat-card:nth-child(2) .stat-icon-pill { background: linear-gradient(145deg,#fff8d0,#ffe898); border:2px solid #e8d060; }
.stat-card:nth-child(3) .stat-icon-pill { background: linear-gradient(145deg,#d8e4ff,#b8caff); border:2px solid #90a8e8; }

.stat-type-chip {
  font-family: 'Press Start 2P', monospace; font-size: 0.24rem;
  letter-spacing: 0.06em; padding: 4px 8px; border-radius: 4px;
}
.stat-card:nth-child(1) .stat-type-chip { background:#ffe0e0; color:#aa2020; border:1px solid #f0b0b0; }
.stat-card:nth-child(2) .stat-type-chip { background:#fff3b0; color:#886600; border:1px solid #e8cc60; }
.stat-card:nth-child(3) .stat-type-chip { background:#d8e4ff; color:#2240a0; border:1px solid #90a8e8; }

.stat-value {
  font-family: 'Press Start 2P', monospace; font-size: 1.6rem;
  line-height: 1; margin-bottom: 4px;
}
.stat-card:nth-child(1) .stat-value { color:var(--red); text-shadow:2px 2px 0 rgba(180,0,0,0.15); }
.stat-card:nth-child(2) .stat-value { color:#aa8000; text-shadow:2px 2px 0 rgba(150,100,0,0.15); }
.stat-card:nth-child(3) .stat-value { color:var(--blue); text-shadow:2px 2px 0 rgba(40,60,180,0.15); }

.stat-label { font-size:.88rem; font-weight:900; color:#1a1030; letter-spacing:.03em; margin-bottom:2px; }
.stat-sub   { font-size:.72rem; color:#8a7a60; font-weight:700; }

.stat-bar-wrap { margin-top:12px; display:flex; align-items:center; gap:7px; }
.stat-bar-label { font-family:'Press Start 2P',monospace; font-size:.24rem; color:#8a7a60; letter-spacing:.1em; }
.stat-bar-track {
  flex:1; height:7px; border-radius:4px;
  background:rgba(0,0,0,0.09); border:1px solid rgba(0,0,0,0.1); overflow:hidden;
}
.stat-bar-fill { height:100%; border-radius:4px; transition:width 1.2s cubic-bezier(.4,0,.2,1); }
.stat-card:nth-child(1) .stat-bar-fill { background:linear-gradient(90deg,#ff6060,var(--red)); }
.stat-card:nth-child(2) .stat-bar-fill { background:linear-gradient(90deg,#ffd040,#c89000); }
.stat-card:nth-child(3) .stat-bar-fill { background:linear-gradient(90deg,var(--blue-light),var(--blue)); }

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
  font-family:'Press Start 2P',monospace; font-size:.36rem;
  color:var(--yellow); letter-spacing:.22em;
  text-shadow:0 0 14px var(--yellow-glow); white-space:nowrap;
}
.section-label-line {
  flex:1; height:1px;
  background:linear-gradient(90deg,rgba(59,76,202,0.4),transparent);
}

/* ─── NAV GRID ──────────────────────────────── */
.nav-grid {
  display:grid; grid-template-columns:repeat(2,1fr);
  gap:16px; margin-bottom:16px;
}

.nav-card {
  display:flex; align-items:center; gap:16px;
  background:linear-gradient(150deg,#ffffff 0%,#f7f2e8 55%,#ede6d8 100%);
  border:3px solid #ccc4b0; border-radius:18px;
  padding:20px 18px;
  text-decoration:none; color:#2a2040;
  position:relative; overflow:hidden;
  transition:transform .18s,box-shadow .18s,border-color .18s;
  cursor:pointer; animation:fadeUp .5s ease both;
  box-shadow:
    0 6px 0 #a89880,
    0 10px 24px rgba(0,0,0,0.24),
    inset 0 1px 0 #fff,
    inset 0 -2px 0 rgba(0,0,0,0.04);
}
.nav-card:nth-child(1){animation-delay:.22s}
.nav-card:nth-child(2){animation-delay:.28s}
.nav-card:nth-child(3){animation-delay:.34s}
.nav-card:nth-child(4){animation-delay:.40s}
.nav-card:hover {
  transform:translateY(-5px); border-color:var(--red);
  box-shadow:0 10px 0 #aa2200,0 18px 40px rgba(204,0,0,0.22),inset 0 1px 0 #fff;
}

/* Subtle diagonal stripe watermark */
.nav-stripe {
  position:absolute; inset:0; pointer-events:none; border-radius:16px;
  background:repeating-linear-gradient(
    -45deg, transparent, transparent 18px,
    rgba(0,0,0,0.016) 18px, rgba(0,0,0,0.016) 19px
  );
}

/* Pokeball SVG watermark */
.nav-pb {
  position:absolute; right:-20px; bottom:-20px;
  width:84px; height:84px; pointer-events:none; opacity:0.08;
}

/* Bottom accent bar */
.nav-accent {
  position:absolute; bottom:0; left:0; right:0; height:4px;
  background:linear-gradient(90deg,var(--red),#ff9090 60%,transparent 90%);
  border-radius:0 0 16px 16px;
}

.nav-icon-wrap {
  width:54px; height:54px; border-radius:14px; flex-shrink:0;
  background:linear-gradient(145deg,#ffe8e8,#ffd0d0);
  border:2px solid #f0b0b0;
  display:flex; align-items:center; justify-content:center;
  font-size:1.5rem; position:relative; z-index:1;
  box-shadow:0 4px 0 #d09090,inset 0 1px 0 rgba(255,255,255,0.9);
  transition:transform .18s;
}
.nav-card:hover .nav-icon-wrap { transform:scale(1.1) rotate(-5deg); }

.nav-content { flex:1; position:relative; z-index:1; min-width:0; }
.nav-title {
  font-size:1rem; font-weight:900; letter-spacing:.06em;
  color:#1a1030; text-transform:uppercase; margin-bottom:3px;
}
.nav-desc { font-size:.74rem; color:#7a6a50; font-weight:700; margin-bottom:6px; }
.nav-type-badge {
  display:inline-flex; align-items:center;
  font-family:'Press Start 2P',monospace; font-size:.22rem;
  background:#ffe0e0; color:#aa2020;
  border:1px solid #f0b0b0; border-radius:4px;
  padding:3px 7px; letter-spacing:.05em;
}

.nav-arrow { display:none; }

/* Danger card */
.nav-card.danger {
  background:linear-gradient(150deg,#fff5f5 0%,#fce8e8 55%,#f5d8d8 100%);
  border-color:#e8b8b8;
}
.nav-card.danger:hover { border-color:#f87171; box-shadow:0 10px 0 #cc7070,0 18px 40px rgba(248,113,113,0.2),inset 0 1px 0 #fff; }
.nav-card.danger .nav-icon-wrap { background:linear-gradient(145deg,#ffe0e0,#ffc8c8); border-color:#f0a0a0; box-shadow:0 4px 0 #d08080; }
.nav-card.danger .nav-title { color:#aa1818; }
.nav-card.danger .nav-desc { color:#a07070; }
.nav-card.danger .nav-type-badge { background:#ffe0e0; color:#cc2222; border-color:#f0a8a8; }
.nav-card.danger .nav-accent { background:linear-gradient(90deg,#f87171,#ffb0b0 60%,transparent 90%); }
.nav-card.danger .nav-arrow { color:#f87171; }

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

/* ─── ANIMATIONS ────────────────────────────── */
@keyframes fadeUp {
  from { opacity:0; transform:translateY(18px); }
  to   { opacity:1; transform:translateY(0); }
}

/* ─── RESPONSIVE ────────────────────────────── */
@media (max-width:600px) {
  .stat-strip { grid-template-columns:repeat(3,1fr); gap:8px; }
  .stat-card { padding:12px 10px 10px; }
  .stat-value { font-size:1rem; }
  .stat-icon-pill { width:36px; height:36px; font-size:1.1rem; }
  .stat-label { font-size:.72rem; }
  .stat-sub { font-size:.62rem; }
  .nav-grid   { grid-template-columns:1fr; }
  .topbar { padding:0 16px; }
  .main   { padding:20px 14px 36px; }
  .brand-logo { font-size:.88rem; }
  .page-title-main { font-size:1.5rem; }
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
    <div class="brand-badge">ADMIN</div>
  </div>
  <div class="topbar-right">
    <div class="power-led"></div>
    <div class="admin-chip">
      <div class="admin-avatar">🧢</div>
      <?php echo htmlspecialchars($admin_name); ?>
    </div>
  </div>
</header>

<!-- MAIN -->
<main class="main">

  <!-- Page Header -->
  <div class="page-header">
    <div>
      <div class="page-eyebrow">// CONTROL PANEL</div>
      <div class="page-title-main">Welcome, <span><?php echo htmlspecialchars($admin_name); ?></span>.</div>
    </div>
  </div>

  <!-- Stat Strip -->
  <div class="stat-strip">

    <div class="stat-card">
      <div class="stat-ribbon"></div>
      <svg class="stat-pokeball-bg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <circle cx="50" cy="50" r="48" fill="none" stroke="#cc0000" stroke-width="6"/>
        <path d="M2,50 A48,48 0 0,1 98,50" fill="#cc0000"/>
        <rect x="2" y="46" width="96" height="8" fill="#cc0000"/>
        <circle cx="50" cy="50" r="13" fill="none" stroke="#cc0000" stroke-width="6"/>
        <circle cx="50" cy="50" r="7" fill="#cc0000"/>
      </svg>
      <div class="stat-top-row">
        <div class="stat-icon-pill">🦖</div>
        <div class="stat-type-chip">DATABASE</div>
      </div>
      <div class="stat-value"><?php echo htmlspecialchars($total_monsters); ?></div>
      <div class="stat-label">Total Monsters</div>
      <div class="stat-sub">In database</div>
      <div class="stat-bar-wrap">
        <span class="stat-bar-label">HP</span>
        <div class="stat-bar-track"><div class="stat-bar-fill" style="width:82%"></div></div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-ribbon"></div>
      <svg class="stat-pokeball-bg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <circle cx="50" cy="50" r="48" fill="none" stroke="#c89000" stroke-width="6"/>
        <path d="M2,50 A48,48 0 0,1 98,50" fill="#c89000"/>
        <rect x="2" y="46" width="96" height="8" fill="#c89000"/>
        <circle cx="50" cy="50" r="13" fill="none" stroke="#c89000" stroke-width="6"/>
        <circle cx="50" cy="50" r="7" fill="#c89000"/>
      </svg>
      <div class="stat-top-row">
        <div class="stat-icon-pill">👥</div>
        <div class="stat-type-chip">PLAYERS</div>
      </div>
      <div class="stat-value"><?php echo htmlspecialchars($active_trainers); ?></div>
      <div class="stat-label">Active Trainers</div>
      <div class="stat-sub">Registered users</div>
      <div class="stat-bar-wrap">
        <span class="stat-bar-label">HP</span>
        <div class="stat-bar-track"><div class="stat-bar-fill" style="width:60%"></div></div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-ribbon"></div>
      <svg class="stat-pokeball-bg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <circle cx="50" cy="50" r="48" fill="none" stroke="#3b4cca" stroke-width="6"/>
        <path d="M2,50 A48,48 0 0,1 98,50" fill="#3b4cca"/>
        <rect x="2" y="46" width="96" height="8" fill="#3b4cca"/>
        <circle cx="50" cy="50" r="13" fill="none" stroke="#3b4cca" stroke-width="6"/>
        <circle cx="50" cy="50" r="7" fill="#3b4cca"/>
      </svg>
      <div class="stat-top-row">
        <div class="stat-icon-pill">🎯</div>
        <div class="stat-type-chip">ALL TIME</div>
      </div>
      <div class="stat-value"><?php echo htmlspecialchars($total_catches); ?></div>
      <div class="stat-label">Total Catches</div>
      <div class="stat-sub">Global records</div>
      <div class="stat-bar-wrap">
        <span class="stat-bar-label">HP</span>
        <div class="stat-bar-track"><div class="stat-bar-fill" style="width:95%"></div></div>
      </div>
    </div>

  </div>

  <!-- Section Label -->
  <div class="section-label">
    <div class="section-label-pip"></div>
    <span class="section-label-text">Quick Actions</span>
    <div class="section-label-line"></div>
  </div>

  <!-- Nav Grid -->
  <div class="nav-grid">

    <a class="nav-card" href="monsters.php" onclick="nav('Manage Monsters')">
      <div class="nav-stripe"></div>
      <svg class="nav-pb" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <circle cx="50" cy="50" r="48" fill="none" stroke="#cc0000" stroke-width="6"/>
        <path d="M2,50 A48,48 0 0,1 98,50" fill="#cc0000"/>
        <rect x="2" y="46" width="96" height="8" fill="#cc0000"/>
        <circle cx="50" cy="50" r="13" fill="none" stroke="#cc0000" stroke-width="6"/>
        <circle cx="50" cy="50" r="7" fill="#cc0000"/>
      </svg>
      <div class="nav-accent"></div>
      <div class="nav-icon-wrap">🦖</div>
      <div class="nav-content">
        <div class="nav-title">Manage Monsters</div>
        <div class="nav-desc">Add, edit & remove monsters</div>
        <div class="nav-type-badge">GOTTA CATCH 'EM ALL!</div>
      </div>
      <div class="nav-arrow">▶</div>
    </a>

        <a class="nav-card" href="players.php" onclick="nav('Manage Players')">
      <div class="nav-stripe"></div>
      <svg class="nav-pb" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <circle cx="50" cy="50" r="48" fill="none" stroke="#cc0000" stroke-width="6"/>
        <path d="M2,50 A48,48 0 0,1 98,50" fill="#cc0000"/>
        <rect x="2" y="46" width="96" height="8" fill="#cc0000"/>
        <circle cx="50" cy="50" r="13" fill="none" stroke="#cc0000" stroke-width="6"/>
        <circle cx="50" cy="50" r="7" fill="#cc0000"/>
      </svg>
      <div class="nav-accent"></div>
      <div class="nav-icon-wrap">👥</div>
      <div class="nav-content">
        <div class="nav-title">Manage Players</div>
        <div class="nav-desc">View & manage trainers</div>
        <div class="nav-type-badge">TRAINER HUB</div>
      </div>
      <div class="nav-arrow">▶</div>
    </a>

    <a class="nav-card" href="leaderboard.php" onclick="nav('Top 10 Hunters')">
      <div class="nav-stripe"></div>
      <svg class="nav-pb" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <circle cx="50" cy="50" r="48" fill="none" stroke="#cc0000" stroke-width="6"/>
        <path d="M2,50 A48,48 0 0,1 98,50" fill="#cc0000"/>
        <rect x="2" y="46" width="96" height="8" fill="#cc0000"/>
        <circle cx="50" cy="50" r="13" fill="none" stroke="#cc0000" stroke-width="6"/>
        <circle cx="50" cy="50" r="7" fill="#cc0000"/>
      </svg>
      <div class="nav-accent"></div>
      <div class="nav-icon-wrap">🏆</div>
      <div class="nav-content">
        <div class="nav-title">Top 10 Hunters</div>
        <div class="nav-desc">View player rankings</div>
        <div class="nav-type-badge">RANKINGS</div>
      </div>
      <div class="nav-arrow">▶</div>
    </a>

    <a class="nav-card" href="map.php" onclick="nav('Monster Map')">
      <div class="nav-stripe"></div>
      <svg class="nav-pb" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <circle cx="50" cy="50" r="48" fill="none" stroke="#cc0000" stroke-width="6"/>
        <path d="M2,50 A48,48 0 0,1 98,50" fill="#cc0000"/>
        <rect x="2" y="46" width="96" height="8" fill="#cc0000"/>
        <circle cx="50" cy="50" r="13" fill="none" stroke="#cc0000" stroke-width="6"/>
        <circle cx="50" cy="50" r="7" fill="#cc0000"/>
      </svg>
      <div class="nav-accent"></div>
      <div class="nav-icon-wrap">🗺️</div>
      <div class="nav-content">
        <div class="nav-title">Monster Map</div>
        <div class="nav-desc">Spawn locations & zones</div>
        <div class="nav-type-badge">EXPLORER</div>
      </div>
      <div class="nav-arrow">▶</div>
    </a>

  </div>

  <!-- Logout -->
  <div style="animation:fadeUp .5s .44s ease both;">
    <a class="nav-card danger" href="logout.php" onclick="nav('Logging out...')">
      <div class="nav-stripe"></div>
      <svg class="nav-pb" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
        <circle cx="50" cy="50" r="48" fill="none" stroke="#f87171" stroke-width="6"/>
        <path d="M2,50 A48,48 0 0,1 98,50" fill="#f87171"/>
        <rect x="2" y="46" width="96" height="8" fill="#f87171"/>
        <circle cx="50" cy="50" r="13" fill="none" stroke="#f87171" stroke-width="6"/>
        <circle cx="50" cy="50" r="7" fill="#f87171"/>
      </svg>
      <div class="nav-accent"></div>
      <div class="nav-icon-wrap">⬅️</div>
      <div class="nav-content">
        <div class="nav-title">Logout</div>
        <div class="nav-desc">End admin session</div>
        <div class="nav-type-badge">SESSION END</div>
      </div>
      <div class="nav-arrow">▶</div>
    </a>
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
function nav(label) { showToast(label); }

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