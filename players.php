<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("10.1.1.17", "badets", "badets@1234", "haumonstersDB");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$admin_name = $_SESSION['username'] ?? 'Admin';
$success_msg = '';
$error_msg   = '';

// CREATE
if (isset($_POST['create'])) {
    $name     = $_POST['player_name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $conn->prepare("INSERT INTO playerstbl (player_name, username, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $username, $password);
    $stmt->execute() ? $success_msg = 'Player added successfully.' : $error_msg = 'Failed to add player.';
}

// DELETE
if (isset($_GET['delete'])) {
    $id   = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM playerstbl WHERE player_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute() ? $success_msg = 'Player deleted.' : $error_msg = 'Failed to delete player.';
}

// UPDATE
if (isset($_POST['update'])) {
    $id       = (int) $_POST['id'];
    $name     = $_POST['player_name'];
    $username = $_POST['username'];
    $stmt = $conn->prepare("UPDATE playerstbl SET player_name = ?, username = ? WHERE player_id = ?");
    $stmt->bind_param("ssi", $name, $username, $id);
    $stmt->execute() ? $success_msg = 'Player updated successfully.' : $error_msg = 'Failed to update player.';
}

// FETCH ALL PLAYERS
$result  = $conn->query("SELECT * FROM playerstbl WHERE role != 'admin' ORDER BY player_id DESC");
$players = [];
while ($row = $result->fetch_assoc()) {
    $players[] = $row;
}
$total_players = count($players);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Manage Players — HAUPokémon</title>
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

.user-table thead th {
  font-size: 0.6rem;
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

#updateForm, form:has(.btn-primary) {
  display: flex;
  flex-direction: column;
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
  background: linear-gradient(180deg, var(--red-light), var(--red));
  color: #fff; padding: 4px 9px; border-radius: 4px;
  border: 2px solid #880000;
  box-shadow: 0 3px 0 #550000, inset 0 1px 0 rgba(255,255,255,0.2);
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
  background: linear-gradient(135deg, var(--red), var(--red-dark));
  border: 2px solid #550000;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.72rem;
}

/* ─── MAIN ───────────────────────────────────── */
.main {
  padding: 28px 28px 56px;
  max-width: 1100px; margin: 0 auto;
  position: relative; z-index: 1;
}

/* ─── PAGE HEADER ────────────────────────────── */
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
.back-btn {
  display: inline-flex; align-items: center; gap: 8px;
  font-family: 'Press Start 2P', monospace; font-size: 0.5rem;
  color: var(--blue-light); text-decoration: none;
  border: 2px solid var(--blue); border-radius: 8px;
  padding: 8px 14px;
  background: linear-gradient(180deg, #1e1640, #120e28);
  box-shadow: 0 3px 0 #1a2060;
  transition: transform .15s, box-shadow .15s;
  letter-spacing: .1em;
}
.back-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 0 #1a2060; }

/* ─── ALERT MESSAGES ─────────────────────────── */
.alert {
  padding: 14px 20px; border-radius: 12px; margin-bottom: 20px;
  font-size: .84rem; font-weight: 900; letter-spacing: .03em;
  animation: fadeUp .3s ease both;
  display: flex; align-items: center; gap: 10px;
}
.alert-success {
  background: linear-gradient(135deg,#0a2a18,#0d3a22);
  border: 2px solid var(--green); color: var(--green-light);
  box-shadow: 0 4px 0 #004420, 0 0 20px rgba(0,165,80,.2);
}
.alert-error {
  background: linear-gradient(135deg,#2a0a0a,#3a0d0d);
  border: 2px solid var(--red); color: var(--red-light);
  box-shadow: 0 4px 0 #550000, 0 0 20px rgba(204,0,0,.2);
}

/* TOP BAR BUTTON */
.topbar-btn {
  display: flex; align-items: center; gap: 6px;
  font-family: 'Press Start 2P', monospace; font-size: 0.3rem;
  letter-spacing: 0.08em; color:
#f0eaff;
  background: linear-gradient(180deg,
#1e1640,
#120e28);
  border: 2px solid var(--blue); border-radius: 8px;
  padding: 7px 12px; cursor: pointer; text-decoration: none;
  box-shadow: 0 3px 0
#1a2060;
  transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
  height: 40px;
  justify-content: center;
}
.topbar-btn:hover {
  border-color: var(--red);
  box-shadow: 0 5px 0
#aa2200, 0 8px 20px rgba(204,0,0,0.2);
  transform: translateY(-2px);
}
.topbar-btn svg { width: 14px; height: 14px; stroke: var(--yellow); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }


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
  font-family:'Press Start 2P',monospace; font-size:.6rem;
  color:var(--yellow); letter-spacing:.22em;
  text-shadow:0 0 14px var(--yellow-glow); white-space:nowrap;
}
.section-label-line {
  flex:1; height:1px;
  background:linear-gradient(90deg,rgba(59,76,202,0.4),transparent);
}

/* ─── CARD PANEL ─────────────────────────────── */
.panel {
  background: linear-gradient(155deg, #ffffff 0%, #f7f2e8 55%, #ede6d8 100%);
  border: 3px solid #ccc4b0; border-radius: 18px;
  padding: 28px;
  position: relative; overflow: hidden;
  animation: fadeUp 0.5s ease both;
  box-shadow: 0 6px 0 #a89880, 0 10px 28px rgba(0,0,0,0.28),
              inset 0 1px 0 #fff, inset 0 -2px 0 rgba(0,0,0,0.04);
  margin-bottom: 28px;
}
.panel-ribbon {
  position: absolute; top: 0; right: 0;
  width: 0; height: 0; border-style: solid;
  border-width: 0 60px 60px 0;
  border-color: transparent var(--red) transparent transparent; opacity: 0.15;
}
.panel-pokeball-bg {
  position: absolute; right: -28px; bottom: -28px;
  width: 130px; height: 130px; pointer-events: none; opacity: 0.05;
  z-index: 0;
}

/* ─── FORM STYLES ────────────────────────────── */
.form-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px; margin-bottom: 20px;
  position: relative; z-index: 1;
}
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-label {
  font-family: 'Press Start 2P', monospace; font-size: 0.5rem;
  color: #5a4a80; letter-spacing: .1em; text-transform: uppercase;
}
.form-input {
  padding: 11px 14px; border-radius: 10px;
  border: 2px solid #c8bea8; background: #faf8f2;
  font-family: 'Nunito', sans-serif; font-size: .9rem; font-weight: 800;
  color: #1a1030;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.06);
  transition: border-color .15s, box-shadow .15s;
  outline: none;
}
.form-input:focus {
  border-color: var(--blue);
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.06), 0 0 0 3px rgba(59,76,202,0.15);
}
.btn {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 12px 24px; border-radius: 10px;
  font-family: 'Press Start 2P', monospace; font-size: 0.6rem;
  font-weight: 900; letter-spacing: .08em;
  border: none; cursor: pointer;
  transition: transform .15s, box-shadow .15s;
  position: relative;
  z-index: 1;
}
.btn:hover { transform: translateY(-2px); }
.btn-primary {
  background: linear-gradient(180deg, var(--blue-light), var(--blue));
  color: #fff; box-shadow: 0 4px 0 #1a2a88, 0 8px 20px rgba(59,76,202,.3);
}
.btn-primary:hover { box-shadow: 0 6px 0 #1a2a88, 0 12px 28px rgba(59,76,202,.4); }
.btn-warning {
  background: linear-gradient(180deg, #ffd040, #c89000);
  color: #3a2000; box-shadow: 0 4px 0 #886000, 0 8px 20px rgba(200,144,0,.3);
}
.btn-warning:hover { box-shadow: 0 6px 0 #886000, 0 12px 28px rgba(200,144,0,.4); }

/* ─── PLAYER CARDS ───────────────────────────── */
.players-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 16px;
}

.player-card {
  background: linear-gradient(160deg, #faf8f4 0%, #ede8de 100%);
  border: 3px solid #d4c9b5;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 0 #b0a090, 0 6px 20px rgba(0,0,0,0.14);
  transition: transform .2s cubic-bezier(.34,1.56,.64,1), box-shadow .2s;
  animation: fadeUp 0.4s ease both;
}
.player-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 0 #b0a090, 0 14px 28px rgba(0,0,0,0.2);
}
.player-card-top {
  background: linear-gradient(180deg, #2a1a42, #1a1030);
  padding: 12px 16px;
  display: flex; align-items: center; justify-content: space-between;
}
.player-card-id {
  font-family: 'Press Start 2P', monospace; font-size: .42rem;
  color: var(--yellow); letter-spacing: .06em;
}
.player-card-joined {
  font-family: 'Press Start 2P', monospace; font-size: .28rem;
  color: #8878b0; letter-spacing: .04em;
}
.player-card-body {
  padding: 14px 16px 16px;
  display: flex; flex-direction: column; gap: 4px;
}
.player-card-name {
  font-size: 1rem; font-weight: 900; color: #1a1030;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.player-card-username {
  font-family: 'Press Start 2P', monospace; font-size: .32rem;
  color: #6a5a80; letter-spacing: .06em;
}
.player-card-actions {
  display: flex; gap: 8px; margin-top: 12px;
}
.btn-card {
  flex: 1; padding: 9px 6px; border-radius: 8px;
  font-family: 'Press Start 2P', monospace; font-size: .38rem;
  font-weight: 900; letter-spacing: .05em;
  border: none; cursor: pointer; text-decoration: none;
  display: flex; align-items: center; justify-content: center;
  transition: transform .12s, box-shadow .12s;
}
.btn-card:hover { transform: translateY(-2px); }
.btn-card-edit {
  background: linear-gradient(180deg,#ffd040,#c89000);
  color: #3a2000; box-shadow: 0 3px 0 #886000;
}
.btn-card-edit:hover { box-shadow: 0 5px 0 #886000; }
.btn-card-delete {
  background: linear-gradient(180deg,var(--red-light),var(--red));
  color: #fff; box-shadow: 0 3px 0 var(--red-dark);
}
.btn-card-delete:hover { box-shadow: 0 5px 0 var(--red-dark); }

.players-empty {
  grid-column: 1 / -1;
  text-align: center; padding: 50px 20px;
  font-family: 'Press Start 2P', monospace; font-size: .38rem;
  color: #8a7a60; letter-spacing: .12em; line-height: 2.5;
}

/* ─── BOTTOM BAR ─────────────────────────────── */
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

/* ─── TOAST ──────────────────────────────────── */
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
  .form-grid { grid-template-columns: 1fr; }
  .panel { padding: 18px; }
  .players-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
  .player-card-name { font-size: .85rem; }
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
    <div class="player-chip">
      <div class="player-avatar">👑</div>
      <?php echo htmlspecialchars($admin_name); ?>
    </div>
  </div>
</header>

<!-- MAIN -->
<main class="main">

  <!-- Page Header -->
  <div class="page-header">
    <div>
      <div class="page-eyebrow">// ADMIN PANEL</div>
      <div class="page-title-main">Manage <span>Players</span></div>
    </div>
    <a class="topbar-btn" href="admin_dashboard.php">
  <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
  BACK
</a>
  </div>

  <?php if ($success_msg): ?>
    <div class="alert alert-success">✔ <?php echo htmlspecialchars($success_msg); ?></div>
  <?php endif; ?>
  <?php if ($error_msg): ?>
    <div class="alert alert-error">✖ <?php echo htmlspecialchars($error_msg); ?></div>
  <?php endif; ?>

  <!-- ── ADD PLAYER ─────────────────────────── -->
  <div class="section-label">
    <div class="section-label-pip"></div>
    <span class="section-label-text">Add New Player</span>
    <div class="section-label-line"></div>
  </div>

  <div class="panel" style="animation-delay:.05s">
    <div class="panel-ribbon"></div>
    <svg class="panel-pokeball-bg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
      <circle cx="50" cy="50" r="48" fill="none" stroke="#cc0000" stroke-width="6"/>
      <path d="M2,50 A48,48 0 0,1 98,50" fill="#cc0000"/>
      <rect x="2" y="46" width="96" height="8" fill="#cc0000"/>
      <circle cx="50" cy="50" r="13" fill="none" stroke="#cc0000" stroke-width="6"/>
      <circle cx="50" cy="50" r="7" fill="#cc0000"/>
    </svg>
    <form method="POST">
      <div class="form-grid">
        <div class="form-group">
          <label class="form-label">Name</label>
          <input class="form-input" type="text" name="player_name" placeholder="e.g. Ash Ketchum" required>
        </div>
        <div class="form-group">
          <label class="form-label">Username</label>
          <input class="form-input" type="text" name="username" placeholder="e.g. ashketchum" required>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input class="form-input" type="password" name="password" placeholder="••••••••" required>
        </div>
      </div>
      <div style="text-align: right;">
  <button class="btn btn-primary" name="create">ADD PLAYER</button>
</div>
    </form>
  </div>

  <!-- ── PLAYERS LIST ───────────────────────── -->
  <div class="section-label" style="animation-delay:.1s">
    <div class="section-label-pip"></div>
    <span class="section-label-text">Players List (<?php echo $total_players; ?>)</span>
    <div class="section-label-line"></div>
  </div>

  <div class="panel" style="animation-delay:.15s">
    <div class="panel-ribbon"></div>
    <svg class="panel-pokeball-bg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
      <circle cx="50" cy="50" r="48" fill="none" stroke="#c89000" stroke-width="6"/>
      <path d="M2,50 A48,48 0 0,1 98,50" fill="#c89000"/>
      <rect x="2" y="46" width="96" height="8" fill="#c89000"/>
      <circle cx="50" cy="50" r="13" fill="none" stroke="#c89000" stroke-width="6"/>
      <circle cx="50" cy="50" r="7" fill="#c89000"/>
    </svg>
    <div class="players-grid">
      <?php if (empty($players)): ?>
        <div class="players-empty">NO PLAYERS FOUND</div>
      <?php else: ?>
        <?php foreach ($players as $i => $row): ?>
        <div class="player-card" style="animation-delay: <?php echo 0.05 + $i * 0.04; ?>s">
          <div class="player-card-top">
            <span class="player-card-id">#<?php echo str_pad($row['player_id'], 3, '0', STR_PAD_LEFT); ?></span>
            <span class="player-card-joined"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></span>
          </div>
          <div class="player-card-body">
            <div class="player-card-name"><?php echo htmlspecialchars($row['player_name']); ?></div>
            <div class="player-card-username">@<?php echo htmlspecialchars($row['username']); ?></div>
            <div class="player-card-actions">
              <button class="btn-card btn-card-edit"
                onclick="fillUpdate(<?php echo $row['player_id']; ?>,'<?php echo addslashes($row['player_name']); ?>','<?php echo addslashes($row['username']); ?>')">
                EDIT
              </button>
              <a class="btn-card btn-card-delete"
                href="?delete=<?php echo $row['player_id']; ?>"
                onclick="return confirm('Delete <?php echo addslashes($row['player_name']); ?>?')">
                DEL
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── UPDATE PLAYER ──────────────────────── -->
  <div class="section-label" style="animation-delay:.2s">
    <div class="section-label-pip"></div>
    <span class="section-label-text">Update Player</span>
    <div class="section-label-line"></div>
  </div>

  <div class="panel" style="animation-delay:.25s">
    <div class="panel-ribbon"></div>
    <svg class="panel-pokeball-bg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
      <circle cx="50" cy="50" r="48" fill="none" stroke="#3b4cca" stroke-width="6"/>
      <path d="M2,50 A48,48 0 0,1 98,50" fill="#3b4cca"/>
      <rect x="2" y="46" width="96" height="8" fill="#3b4cca"/>
      <circle cx="50" cy="50" r="13" fill="none" stroke="#3b4cca" stroke-width="6"/>
      <circle cx="50" cy="50" r="7" fill="#3b4cca"/>
    </svg>
    <form method="POST" id="updateForm">
      <div class="form-grid">
        <div class="form-group">
  <label class="form-label">Player ID</label>
  <div class="form-input" id="upd_id_display" style="color:#8a7a60; font-style:italic;">Auto-filled on Edit</div>
  <input type="hidden" name="id" id="upd_id">
</div>
        <div class="form-group">
          <label class="form-label">Name</label>
          <input class="form-input" type="text" name="player_name" id="upd_name" placeholder="New name" required>
        </div>
        <div class="form-group">
          <label class="form-label">Username</label>
          <input class="form-input" type="text" name="username" id="upd_username" placeholder="New username" required>
        </div>
      </div>
      <div style="text-align: right;">
  <button class="btn btn-warning" name="update">UPDATE PLAYER</button>
</div>
    </form>
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

function fillUpdate(id, name, username) {
  document.getElementById('upd_id').value               = id;
  document.getElementById('upd_id_display').textContent = '#' + String(id).padStart(3, '0');
  document.getElementById('upd_id_display').style.color = '#1a1030';
  document.getElementById('upd_id_display').style.fontStyle = 'normal';
  document.getElementById('upd_name').value             = name;
  document.getElementById('upd_username').value         = username;
  document.getElementById('updateForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
  showToast('Editing player #' + id);
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