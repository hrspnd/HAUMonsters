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
    $name    = $_POST['name'];
    $type    = $_POST['type'];
    $lat     = $_POST['lat'];
    $lng     = $_POST['lng'];
    $picture = $_POST['picture_url'];
    $stmt = $conn->prepare("INSERT INTO monsterstbl (monster_name, monster_type, spawn_latitude, spawn_longitude, picture_url) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $type, $lat, $lng, $picture);
    $stmt->execute() ? $success_msg = 'Monster added successfully.' : $error_msg = 'Failed to add monster.';
}

// DELETE
if (isset($_POST['delete'])) {
    $id = (int) $_POST['delete_id'];

    // Delete child rows first
    $stmt1 = $conn->prepare("DELETE FROM monster_catchestbl WHERE monster_id = ?");
    $stmt1->bind_param("i", $id);
    $stmt1->execute();

    // Then delete the monster
    $stmt2 = $conn->prepare("DELETE FROM monsterstbl WHERE monster_id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute() ? $success_msg = 'Monster deleted.' : $error_msg = 'Failed to delete monster.';
}

// UPDATE
if (isset($_POST['update'])) {
    $id      = (int) $_POST['id'];
    $name    = $_POST['name'];
    $type    = $_POST['type'];
    $picture = $_POST['picture_url'];
    $stmt = $conn->prepare("UPDATE monsterstbl SET monster_name=?, monster_type=?, picture_url=? WHERE monster_id=?");
    $stmt->bind_param("sssi", $name, $type, $picture, $id);
    $stmt->execute();
    if ($stmt->affected_rows === 0) {
        $error_msg = 'Monster #' . $id . ' does not exist.';
    } else {
        $success_msg = 'Monster updated successfully.';
    }
}

// FETCH ALL MONSTERS
$result   = $conn->query("SELECT * FROM monsterstbl ORDER BY monster_id DESC");
$monsters = [];
while ($row = $result->fetch_assoc()) {
    $monsters[] = $row;
}
$total_monsters = count($monsters);

// Type → color mapping
$type_colors = [
    'Normal'   => ['#a8a878','#6d6d4e'],
    'Fire'     => ['#f08030','#9c531f'],
    'Water'    => ['#6890f0','#445e9c'],
    'Electric' => ['#f8d030','#a1871f'],
    'Grass'    => ['#78c850','#4e8234'],
    'Ice'      => ['#98d8d8','#638d8d'],
    'Fighting' => ['#c03028','#7d1f1a'],
    'Poison'   => ['#a040a0','#682a68'],
    'Ground'   => ['#e0c068','#927d44'],
    'Flying'   => ['#a890f0','#6d5e9c'],
    'Psychic'  => ['#f85888','#a13959'],
    'Bug'      => ['#a8b820','#6d7815'],
    'Rock'     => ['#b8a038','#786824'],
    'Ghost'    => ['#705898','#493963'],
    'Dragon'   => ['#7038f8','#4924a1'],
    'Dark'     => ['#705848','#49392f'],
    'Steel'    => ['#b8b8d0','#787887'],
    'Fairy'    => ['#ee99ac','#9b6470'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Manage Monsters — HAUPokémon</title>
<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Nunito:wght@700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
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
  font-family: 'Press Start 2P', monospace; font-size: 0.6rem;
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

/* TOP BAR BUTTON */
.topbar-btn {
  display: flex; align-items: center; gap: 6px;
  font-family: 'Press Start 2P', monospace; font-size: 0.3rem;
  letter-spacing: 0.08em; color: #f0eaff;
  background: linear-gradient(180deg, #1e1640, #120e28);
  border: 2px solid var(--blue); border-radius: 8px;
  padding: 7px 12px; cursor: pointer; text-decoration: none;
  box-shadow: 0 3px 0 #1a2060;
  transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
  height: 40px;
  justify-content: center;
}

.topbar-btn:hover {
  border-color: var(--red);
  box-shadow: 0 5px 0 #aa2200, 0 8px 20px rgba(204,0,0,0.2);
  transform: translateY(-2px);
}
.topbar-btn svg { width: 14px; height: 14px; stroke: var(--yellow); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; flex-shrink: 0; }

/* ─── MONSTER CARDS GRID ──────────────────────── */
.monsters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 20px;
}

.monster-card {
  background: linear-gradient(160deg, #faf8f4 0%, #ede8de 100%);
  border: 3px solid #d4c9b5;
  border-radius: 20px;
  overflow: hidden;
  position: relative;
  box-shadow: 0 5px 0 #b0a090, 0 8px 24px rgba(0,0,0,0.18);
  transition: transform .2s cubic-bezier(.34,1.56,.64,1), box-shadow .2s;
  animation: fadeUp 0.4s ease both;
}
.monster-card:hover {
  transform: translateY(-6px) scale(1.02);
  box-shadow: 0 10px 0 #b0a090, 0 16px 36px rgba(0,0,0,0.24);
}

/* Colored top stripe based on type */
.monster-card-stripe {
  height: 6px;
  width: 100%;
}

/* Image area */
.monster-card-img-wrap {
  background: linear-gradient(160deg, #f0ebe0, #e8e0d0);
  display: flex; align-items: center; justify-content: center;
  padding: 18px 10px 12px;
  position: relative;
  min-height: 13rem;
}
.monster-card-img-wrap::after {
  content: '';
  position: absolute; bottom: 0; left: 0; right: 0; height: 20px;
  background: linear-gradient(to bottom, transparent, rgba(0,0,0,0.04));
}
.monster-card-img {
  width: 10rem; height: 10rem;
  object-fit: contain;
  image-rendering: pixelated;
  filter: drop-shadow(0 4px 8px rgba(0,0,0,0.18));
  transition: transform .3s;
}
.monster-card:hover .monster-card-img {
  transform: translateY(-4px) scale(1.06);
}
.monster-card-id {
  position: absolute; top: 10px; left: 10px;
  font-family: 'Press Start 2P', monospace; font-size: .42rem;
  background: rgba(0,0,0,0.55); color: #fff;
  padding: 4px 7px; border-radius: 6px;
  letter-spacing: .06em;
}

/* Body */
.monster-card-body {
  padding: 12px 14px 14px;
  display: flex; flex-direction: column; gap: 6px;
}
.monster-card-name {
  font-size: .95rem; font-weight: 900; color: #1a1030;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  letter-spacing: .02em;
}
.monster-card-type {
  display: inline-flex; align-items: center;
  font-family: 'Press Start 2P', monospace; font-size: .38rem;
  padding: 4px 9px; border-radius: 20px;
  color: #fff; letter-spacing: .08em;
  width: fit-content;
  box-shadow: 0 2px 0 rgba(0,0,0,0.25);
  text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}
.monster-card-coords {
  display: flex; flex-direction: column; gap: 2px;
  font-family: 'Press Start 2P', monospace; font-size: .3rem;
  color: #8a7a60; letter-spacing: .04em;
  margin-top: 2px;
}
.monster-card-coord-row {
  display: flex; align-items: center; gap: 5px;
}
.monster-card-coord-label {
  color: #6a5a40; font-size: .28rem;
}

/* Card action buttons */
.monster-card-actions {
  display: flex; gap: 8px; margin-top: 10px;
}
.btn-card {
  flex: 1;
  padding: 8px 6px; border-radius: 8px;
  font-family: 'Press Start 2P', monospace; font-size: .38rem;
  font-weight: 900; letter-spacing: .05em;
  border: none; cursor: pointer; text-align: center;
  transition: transform .12s, box-shadow .12s;
  display: flex; align-items: center; justify-content: center; gap: 4px;
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

/* Empty state */
.cards-empty {
  grid-column: 1 / -1;
  text-align: center; padding: 50px 20px;
  font-family: 'Press Start 2P', monospace; font-size: .38rem;
  color: #8a7a60; letter-spacing: .12em;
  line-height: 2.5;
}
.cards-empty-icon { font-size: 2.5rem; display: block; margin-bottom: 14px; opacity: .5; }

/* ── Map tile dark filter (matches map.php) ── */
#add-map .leaflet-tile { filter: brightness(0.58) saturate(0.45) hue-rotate(200deg); }
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
  display: block !important;
  margin-bottom: 4px !important;
}
.leaflet-popup-content {
  color: #c0b8e0 !important;
  font-size: 0.78rem !important;
}

.type-select {
  background-color: #faf8f2 !important;
  color: #1a1030;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  padding-right: 45px;
  background-image: url("data:image/svg+xml;utf8,<svg fill='%231a1030' height='20' viewBox='0 0 20 20' width='20' xmlns='http://www.w3.org/2000/svg'><path d='M5 7l5 5 5-5z'/></svg>");
  background-repeat: no-repeat;
  background-position: right 14px center;
  background-size: 16px;
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

@media (max-width: 600px) {
  .topbar { padding:0 16px; }
  .main   { padding:20px 14px 36px; }
  .brand-logo { font-size:.88rem; }
  .page-title-main { font-size:1.5rem; }
  .form-grid { grid-template-columns: 1fr; }
  .panel { padding: 18px; }
  .monsters-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
  }
  .monster-card-img { width: 6.8rem; height: 6.8rem; }
  .monster-card-img-wrap { min-height: 10rem; padding: 14px 8px 10px; }
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
      <div class="page-title-main">Manage <span>Monsters</span></div>
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

  <!-- ── ADD MONSTER ────────────────────────── -->
  <div class="section-label">
    <div class="section-label-pip"></div>
    <span class="section-label-text">Add New Monster</span>
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
          <input class="form-input" type="text" name="name" placeholder="e.g. Bulbasaur" required>
        </div>
        <div class="form-group">
            <label class="form-label">Type</label>
            <select class="form-input type-select" name="type" required>
            <option value="">Select Type</option>
            <option>Normal</option><option>Fire</option><option>Water</option>
            <option>Electric</option><option>Grass</option><option>Ice</option>
            <option>Fighting</option><option>Poison</option><option>Ground</option>
            <option>Flying</option><option>Psychic</option><option>Bug</option>
            <option>Rock</option><option>Ghost</option><option>Dragon</option>
            <option>Dark</option><option>Steel</option><option>Fairy</option>
            </select>
        </div>
        <div class="form-group" style="grid-column: 1 / -1;">
          <label class="form-label">Spawn Location</label>
          <div style="display:flex; gap:10px; margin-bottom:0.6rem; flex-wrap:wrap;">
            <div>
              <label class="form-label" style="font-size:0.4rem; margin-bottom:4px; display:block;">Latitude</label>
              <input class="form-input" type="text" id="add_lat_display" name="lat" placeholder="Click map to set…" readonly required>
            </div>
            <div>
              <label class="form-label" style="font-size:0.4rem; margin-bottom:4px; display:block;">Longitude</label>
              <input class="form-input" type="text" id="add_lng_display" name="lng" placeholder="Click map to set…" readonly required>
            </div>
          </div>
          <div id="add-map" style="height:320px; border-radius:12px; border:2px solid #c8bea8; overflow:hidden; box-shadow:inset 0 2px 4px rgba(0,0,0,0.1);"></div>
          <p style="font-size:0.55rem; color:#8a7a60; margin-top:6px; font-family:'Press Start 2P',monospace; letter-spacing:0.06em;">TAP ON MAP TO SET SPAWN POINT</p>
        </div>
        <div class="form-group">
          <label class="form-label">Image URL</label>
          <input class="form-input" type="text" name="picture_url" placeholder="https://...">
        </div>
      </div>
      <div style="display:flex; justify-content:flex-end; width:100%;">
  <button class="btn btn-primary" name="create">ADD MONSTER</button>
</div>
    </form>

  </div>

  <!-- ── MONSTERS CARD LIST ─────────────────── -->
  <div class="section-label" style="animation-delay:.1s">
    <div class="section-label-pip"></div>
    <span class="section-label-text">Monsters List (<?php echo $total_monsters; ?>)</span>
    <div class="section-label-line"></div>
  </div>

  <div class="panel" style="animation-delay:.15s; padding: 24px;">
    <div class="panel-ribbon"></div>
    <svg class="panel-pokeball-bg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
      <circle cx="50" cy="50" r="48" fill="none" stroke="#c89000" stroke-width="6"/>
      <path d="M2,50 A48,48 0 0,1 98,50" fill="#c89000"/>
      <rect x="2" y="46" width="96" height="8" fill="#c89000"/>
      <circle cx="50" cy="50" r="13" fill="none" stroke="#c89000" stroke-width="6"/>
      <circle cx="50" cy="50" r="7" fill="#c89000"/>
    </svg>

    <div class="monsters-grid">
      <?php if (empty($monsters)): ?>
        <div class="cards-empty">
          <span class="cards-empty-icon">👾</span>
          NO MONSTERS FOUND<br>ADD ONE ABOVE!
        </div>
      <?php else: ?>
        <?php foreach ($monsters as $i => $row):
          $type = $row['monster_type'] ?? 'Normal';
          $colors = $type_colors[$type] ?? ['#a8a878','#6d6d4e'];
          $img = $row['picture_url'] ?: null;
        ?>
        <div class="monster-card" style="animation-delay: <?php echo 0.05 + $i * 0.04; ?>s">
          <!-- Colored type stripe -->
          <div class="monster-card-stripe" style="background: linear-gradient(90deg, <?php echo $colors[0]; ?>, <?php echo $colors[1]; ?>);"></div>

          <!-- Image area -->
          <div class="monster-card-img-wrap">
            <span class="monster-card-id">#<?php echo str_pad($row['monster_id'], 3, '0', STR_PAD_LEFT); ?></span>
            <?php if ($img): ?>
  <img class="monster-card-img"
       src="<?php echo htmlspecialchars($img); ?>"
       alt="<?php echo htmlspecialchars($row['monster_name']); ?>"
       onerror="this.outerHTML='<span style=\'font-size:5rem\'>🦕</span>'">
<?php else: ?>
  <span style="font-size:6rem; line-height:1; display:block;">🦕</span>
<?php endif; ?>
          </div>

          <!-- Body -->
          <div class="monster-card-body">
            <div class="monster-card-name"><?php echo htmlspecialchars($row['monster_name']); ?></div>
            <div class="monster-card-type"
                 style="background: linear-gradient(135deg, <?php echo $colors[0]; ?>, <?php echo $colors[1]; ?>);">
              <?php echo htmlspecialchars($type); ?>
            </div>
            <div class="monster-card-coords">
              <div class="monster-card-coord-row">
                <span class="monster-card-coord-label">LAT</span>
                <?php echo htmlspecialchars($row['spawn_latitude']); ?>
              </div>
              <div class="monster-card-coord-row">
                <span class="monster-card-coord-label">LNG</span>
                <?php echo htmlspecialchars($row['spawn_longitude']); ?>
              </div>
            </div>
            <div class="monster-card-actions">
              <button class="btn-card btn-card-edit"
                onclick="fillUpdate(<?php echo $row['monster_id']; ?>,'<?php echo addslashes($row['monster_name']); ?>','<?php echo addslashes($type); ?>','<?php echo addslashes($row['picture_url']); ?>')">
                EDIT
              </button>
              <form method="POST" style="flex:1; display:flex;" onsubmit="return confirm('Delete <?php echo addslashes($row['monster_name']); ?>?')">
                <input type="hidden" name="delete_id" value="<?php echo $row['monster_id']; ?>">
                <button type="submit" name="delete" class="btn-card btn-card-delete" style="width:100%;">DEL</button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── UPDATE MONSTER ─────────────────────── -->
  <div class="section-label" style="animation-delay:.2s">
    <div class="section-label-pip"></div>
    <span class="section-label-text">Update Monster</span>
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
          <label class="form-label">Monster ID</label>
          <div class="form-input" id="upd_id_display" style="color:#8a7a60; font-style:italic;">Auto-filled on Edit</div>
          <input type="hidden" name="id" id="upd_id">
        </div>
        <div class="form-group">
          <label class="form-label">Name</label>
          <input class="form-input" type="text" name="name" id="upd_name" placeholder="New name" required>
        </div>
        <div class="form-group">
            <label class="form-label">Type</label>
            <select class="form-input type-select" name="type" id="upd_type" required>
            <option value="">Select Type</option>
            <option>Normal</option><option>Fire</option><option>Water</option>
            <option>Electric</option><option>Grass</option><option>Ice</option>
            <option>Fighting</option><option>Poison</option><option>Ground</option>
            <option>Flying</option><option>Psychic</option><option>Bug</option>
            <option>Rock</option><option>Ghost</option><option>Dragon</option>
            <option>Dark</option><option>Steel</option><option>Fairy</option>
            </select>
        </div>
        <div class="form-group">
          <label class="form-label">Image URL</label>
          <input class="form-input" type="text" name="picture_url" id="upd_picture" placeholder="New image URL">
        </div>
      </div>
      <div style="display:flex; justify-content:flex-end; width:100%;">
  <button class="btn btn-warning" name="update">UPDATE MONSTER</button>
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

function fillUpdate(id, name, type, picture) {
  document.getElementById('upd_id').value               = id;
  document.getElementById('upd_id_display').textContent = '#' + String(id).padStart(3, '0');
  document.getElementById('upd_id_display').style.color = '#1a1030';
  document.getElementById('upd_id_display').style.fontStyle = 'normal';
  document.getElementById('upd_name').value             = name;
  document.getElementById('upd_type').value             = type;
  document.getElementById('upd_picture').value          = picture;
  document.getElementById('updateForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
  showToast('Editing monster #' + id);
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

/* ── Add Monster Map ── */
(function() {
  var defaultLat = 15.1450, defaultLng = 120.5887;
  var addMap = L.map('add-map').setView([defaultLat, defaultLng], 16);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19
  }).addTo(addMap);

  var addMarker = null;

  var pokéIcon = L.divIcon({
    html: '<div style="font-size:28px;line-height:1;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.5));">📍</div>',
    iconSize: [28, 28], iconAnchor: [14, 28], className: ''
  });

  function setAddPin(latlng) {
    if (addMarker) { addMap.removeLayer(addMarker); }
    addMarker = L.marker(latlng, { icon: pokéIcon, draggable: true }).addTo(addMap);
    document.getElementById('add_lat_display').value = latlng.lat.toFixed(6);
    document.getElementById('add_lng_display').value = latlng.lng.toFixed(6);
    addMarker.on('dragend', function(e) {
      var p = e.target.getLatLng();
      document.getElementById('add_lat_display').value = p.lat.toFixed(6);
      document.getElementById('add_lng_display').value = p.lng.toFixed(6);
    });
  }

  addMap.on('click', function(e) { setAddPin(e.latlng); });

  /* ── Existing monsters as markers (map.php aesthetic) ── */
  var typeGlowColors = {
    fire:'#ff4422',water:'#3399ff',grass:'#44dd44',electric:'#ffdd00',
    psychic:'#ff66cc',ice:'#66eeff',rock:'#bbaa77',ground:'#ddaa55',
    flying:'#aabbff',bug:'#88cc00',poison:'#cc44ff',ghost:'#8855cc',
    dragon:'#7755ff',dark:'#886644',steel:'#aabbcc',fairy:'#ffaacc',
    fighting:'#cc5500',normal:'#bbbbbb'
  };

  function getTypeGlow(type) {
    return typeGlowColors[(type||'').toLowerCase().trim()] || '#ffffff';
  }

  function getIconSize(zoom) {
    var s = Math.round(16 + (zoom - 6) * 3);
    return Math.max(14, Math.min(s, 56));
  }

  function buildExistingIcon(m, size, glowColor) {
    var glow = 'drop-shadow(0 0 5px ' + glowColor + ')';
    var img = m.img
      ? '<img src="' + m.img + '" onerror="this.outerHTML=\'<span style=&quot;font-size:' + Math.round(size*.7) + 'px&quot;>🦕</span>\'" style="width:' + size + 'px;height:' + size + 'px;object-fit:contain;image-rendering:pixelated;">'
      : '<span style="font-size:' + Math.round(size*.8) + 'px;">🦕</span>';
    return L.divIcon({
      html: '<div style="font-size:' + Math.round(size*.7) + 'px;filter:' + glow + ';display:flex;align-items:center;justify-content:center;">' + img + '</div>',
      className: '',
      iconSize:   [size, size],
      iconAnchor: [Math.round(size/2), Math.round(size/2)],
      popupAnchor:[0, -Math.round(size/2)]
    });
  }

  var existingMonsters = <?php echo json_encode(array_map(function($m) {
    return [
      'id'   => $m['monster_id'],
      'name' => $m['monster_name'],
      'type' => $m['monster_type'],
      'lat'  => (float) $m['spawn_latitude'],
      'lng'  => (float) $m['spawn_longitude'],
      'img'  => $m['picture_url'],
    ];
  }, $monsters)); ?>;

  var existingMarkerObjects = [];

  existingMonsters.forEach(function(m) {
    if (!m.lat && !m.lng) return;
    var glowColor = getTypeGlow(m.type);
    var size = getIconSize(addMap.getZoom());

    var popupImg = m.img
      ? '<img src="' + m.img + '" onerror="this.outerHTML=\'<span style=&quot;font-size:2rem&quot;>�<</span>\'" style="width:80px;height:auto;margin-top:6px;border-radius:8px;filter:drop-shadow(0 0 6px ' + glowColor + ');">'
      : '<span style="font-size:2rem;filter:drop-shadow(0 0 6px ' + glowColor + ');">🦕</span>';

    var marker = L.marker([m.lat, m.lng], { icon: buildExistingIcon(m, size, glowColor) })
      .addTo(addMap)
      .bindPopup(
        '<b>' + m.name + '</b>' +
        '<br>Type: ' + m.type +
        '<br>#' + String(m.id).padStart(3,'0') +
        '<br>' + popupImg
      );

    existingMarkerObjects.push({ marker: marker, m: m, glowColor: glowColor });
  });

  addMap.on('zoom', function() {
    var size = getIconSize(addMap.getZoom());
    existingMarkerObjects.forEach(function(obj) {
      obj.marker.setIcon(buildExistingIcon(obj.m, size, obj.glowColor));
    });
  });
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