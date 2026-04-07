<?php
session_start();
// Only users allowed
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}
$conn = new mysqli("10.1.1.17", "badets", "badets@1234", "haumonstersDB");
$player_id = $_SESSION['player_id'];

// 🔥 HANDLE RELEASE
if (isset($_GET['release'])) {
    $catch_id = $_GET['release'];
    // Make sure player owns it
    $conn->query("
        DELETE FROM monster_catchestbl
        WHERE catch_id = $catch_id
        AND player_id = $player_id
    ");
}

// Get caught monsters
$result = $conn->query("
    SELECT
        c.catch_id,
        m.monster_name,
        m.monster_type,
        m.picture_url,
        c.latitude,
        c.longitude
    FROM monster_catchestbl c
    JOIN monsterstbl m
        ON c.monster_id = m.monster_id
    WHERE c.player_id = $player_id
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>My Monsters — HAUPokémon</title>
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
html, body {
height: 100%;
}
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
flex-shrink: 0;
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
max-width: 1100px; margin: 0 auto; width: 100%;
position: relative; z-index: 1;
flex: 1;
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
.monster-card-stripe { height: 6px; width: 100%; }
.monster-card-img {
width: 100%; height: 120px; object-fit: contain;
background: linear-gradient(160deg, #f0eade 0%, #e4ddd0 100%);
display: block; padding: 8px;
}
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
.monster-card-coord-row { display: flex; align-items: center; gap: 5px; }
.monster-card-coord-label { color: #6a5a40; font-size: .28rem; }
.monster-card-actions { display: flex; gap: 8px; margin-top: 10px; }
.btn-card {
flex: 1;
padding: 8px 6px; border-radius: 8px;
font-family: 'Press Start 2P', monospace; font-size: .38rem;
font-weight: 900; letter-spacing: .05em;
border: none; cursor: pointer; text-align: center;
transition: transform .12s, box-shadow .12s;
display: flex; align-items: center; justify-content: center; gap: 4px;
text-decoration: none;
}
.btn-card:hover { transform: translateY(-2px); }
.btn-card-release {
background: linear-gradient(180deg,var(--red-light),var(--red));
color: #fff; box-shadow: 0 3px 0 var(--red-dark);
}
.btn-card-release:hover { box-shadow: 0 5px 0 var(--red-dark); }
.cards-empty {
grid-column: 1 / -1;
text-align: center; padding: 50px 20px;
font-family: 'Press Start 2P', monospace; font-size: .38rem;
color: #8a7a60; letter-spacing: .12em;
line-height: 2.5;
}
.cards-empty-icon { font-size: 2.5rem; display: block; margin-bottom: 14px; opacity: .5; }
/* ─── BOTTOM BAR ─────────────────────────────── */
.bottom-bar {
display:flex; align-items:center; justify-content:space-between;
padding:14px 28px;
background:rgba(8,6,20,0.92);
border-top:2px solid #1e2060;
font-size:.68rem; color:#4a4a7a; letter-spacing:.08em;
animation:fadeUp .5s .5s ease both;
position:relative; z-index:1;
flex-shrink: 0;
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
@keyframes fadeUp {
  from { opacity:0; transform:translateY(18px); }
  to   { opacity:1; transform:translateY(0); }
}
@media (max-width: 600px) {
  .topbar { padding:0 16px; }
  .main   { padding:20px 14px 36px; }
  .brand-logo { font-size:.88rem; }
  .page-title-main { font-size:1.5rem; }
  .panel { padding: 18px; }
  .monsters-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }
}
</style>
</head>
<body>
<div class="stars" id="stars"></div>
<div class="bg-balls" id="bgBalls"></div>

<!-- TOP BAR -->
<header class="topbar">
  <div class="brand">
    <div class="brand-logo">HAUPokémon</div>
    <div class="brand-badge">PLAYER</div>
  </div>
  <div class="topbar-right">
    <div class="power-led"></div>
    <div class="player-chip">
      <div class="player-avatar">🎮</div>
      <?php echo htmlspecialchars($_SESSION['username'] ?? 'Trainer'); ?>
    </div>
  </div>
</header>

<!-- MAIN -->
<main class="main">

  <!-- Page Header -->
  <div class="page-header">
    <div>
      <div class="page-eyebrow">// TRAINER PROFILE</div>
      <div class="page-title-main">My <span>Monsters</span> 🧾</div>
    </div>
    <a class="topbar-btn" href="player_dashboard.php">
      <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
      <span>BACK</span>
    </a>
  </div>

  <!-- ── CAUGHT MONSTERS LIST ─────────────────── -->
  <div class="section-label">
    <div class="section-label-pip"></div>
    <span class="section-label-text">Caught Monsters (<?php echo $result->num_rows; ?>)</span>
    <div class="section-label-line"></div>
  </div>

  <div class="panel" style="animation-delay:.05s; padding: 24px;">
    <div class="panel-ribbon"></div>
    <svg class="panel-pokeball-bg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
      <circle cx="50" cy="50" r="48" fill="none" stroke="#c89000" stroke-width="6"/>
      <path d="M2,50 A48,48 0 0,1 98,50" fill="#c89000"/>
      <rect x="2" y="46" width="96" height="8" fill="#c89000"/>
      <circle cx="50" cy="50" r="13" fill="none" stroke="#c89000" stroke-width="6"/>
      <circle cx="50" cy="50" r="7" fill="#c89000"/>
    </svg>

    <div class="monsters-grid">
      <?php
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

      if ($result->num_rows > 0):
          $i = 0;
          while ($row = $result->fetch_assoc()):
              $type   = $row['monster_type'] ?? 'Normal';
              $colors = $type_colors[$type] ?? ['#a8a878','#6d6d4e'];
      ?>
        <div class="monster-card" style="animation-delay: <?php echo 0.05 + $i * 0.04; ?>s">
          <div class="monster-card-stripe" style="background: linear-gradient(90deg, <?php echo $colors[0]; ?>, <?php echo $colors[1]; ?>);"></div>
          <?php if (!empty($row['picture_url'])): ?>
          <img class="monster-card-img"
               src="<?php echo htmlspecialchars($row['picture_url']); ?>"
               alt="<?php echo htmlspecialchars($row['monster_name']); ?>">
          <?php else: ?>
          <div class="monster-card-img" style="display:flex;align-items:center;justify-content:center;font-size:2.5rem;">👾</div>
          <?php endif; ?>
          <div class="monster-card-body">
            <div class="monster-card-name"><?php echo htmlspecialchars($row['monster_name']); ?></div>
            <div class="monster-card-type"
                 style="background: linear-gradient(135deg, <?php echo $colors[0]; ?>, <?php echo $colors[1]; ?>);">
              <?php echo htmlspecialchars($type); ?>
            </div>
            <div class="monster-card-coords">
              <div class="monster-card-coord-row">
                <span class="monster-card-coord-label">LAT</span>
                <?php echo htmlspecialchars($row['latitude']); ?>
              </div>
              <div class="monster-card-coord-row">
                <span class="monster-card-coord-label">LNG</span>
                <?php echo htmlspecialchars($row['longitude']); ?>
              </div>
            </div>
            <div class="monster-card-actions">
              <a class="btn-card btn-card-release"
                 href="?release=<?php echo $row['catch_id']; ?>"
                 onclick="return confirm('Release <?php echo addslashes($row['monster_name']); ?>?')">
                RELEASE
              </a>
            </div>
          </div>
        </div>
      <?php $i++; endwhile; ?>
      <?php else: ?>
        <div class="cards-empty">
          <span class="cards-empty-icon">👾</span>
          NO MONSTERS CAUGHT YET<br>GO CATCH SOME!
        </div>
      <?php endif; ?>
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