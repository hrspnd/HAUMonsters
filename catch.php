<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}
$conn = new mysqli("10.1.1.17", "badets", "badets@1234", "haumonstersDB");
$player_id = $_SESSION['player_id'];
$monster_id = $_GET['monster_id'];
$lat = $_GET['lat'];
$lng = $_GET['lng'];

// Fetch monster info for display
$monsterResult = $conn->query("SELECT * FROM monsterstbl WHERE monster_id = $monster_id");
$monster = $monsterResult ? $monsterResult->fetch_assoc() : null;

// Prevent duplicate catch
$check = $conn->query("
    SELECT * FROM monster_catchestbl
    WHERE player_id = $player_id
    AND monster_id = $monster_id
");

if ($check->num_rows > 0) {
    $success = false;
    $message = "Already Caught!";
    $sub = "You already have this monster in your collection.";
} else {
    $conn->query("
        INSERT INTO monster_catchestbl
        (player_id, monster_id, latitude, longitude)
        VALUES ($player_id, $monster_id, $lat, $lng)
    ");
    $success = true;
    $message = "Monster Caught!";
    $sub = "Added to your collection successfully.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Catch Result — HAUPokémon</title>
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
  font-family: 'Nunito', sans-serif; font-weight: 700;
  color: #f0eaff; overflow-x: hidden;
  display: flex; flex-direction: column;
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

/* TOP BAR */
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
@keyframes popInOut {
  0%   { opacity:0; transform: scale(0.3); }
  40%  { opacity:1; transform: scale(1); }
  70%  { opacity:1; transform: scale(1); }
  100% { opacity:0; transform: scale(0.1); }
}

.pokeball-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.75);
  z-index: 998;
  pointer-events: none;
  animation: fadeOut 1.8s 0.3s ease forwards;
}

@keyframes fadeOut {
  0%   { opacity: 1; }
  70%  { opacity: 1; }
  100% { opacity: 0; }
}

.pokeball-throw {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  z-index: 999;
  pointer-events: none;
  animation: popInOut 1.8s 0.3s ease both;
}

@keyframes popInOut {
  0%   { opacity:0; transform: translate(-50%, -50%) scale(0.3); }
  40%  { opacity:1; transform: translate(-50%, -50%) scale(1); }
  70%  { opacity:1; transform: translate(-50%, -50%) scale(1); }
  100% { opacity:0; transform: translate(-50%, -50%) scale(0.1); }
}
/* MAIN */
.main {
  flex: 1; display: flex; align-items: center; justify-content: center;
  padding: 40px 28px 56px;
  position: relative; z-index: 1;
}

/* RESULT CARD */
.result-card {
  background: linear-gradient(155deg, #ffffff 0%, #f7f2e8 55%, #ede6d8 100%);
  border: 3px solid #ccc4b0; border-radius: 24px;
  padding: 55px 36px 55px;
  text-align: center;
  max-width: 420px; width: 100%;
  position: relative; overflow: hidden;
  box-shadow: 0 10px 0 #a89880, 0 18px 50px rgba(0,0,0,0.35), inset 0 1px 0 #fff;
  animation: popIn 0.5s cubic-bezier(.34,1.56,.64,1) both;
}

.result-card.success {
  border-color: #80d8b0;
  box-shadow: 0 10px 0 #3a9060, 0 18px 50px rgba(0,180,100,0.2), inset 0 1px 0 #effffa;
}
.result-card.duplicate {
  border-color: #d8b080;
  box-shadow: 0 10px 0 #a07040, 0 18px 50px rgba(180,120,0,0.2), inset 0 1px 0 #fffbf0;
}

/* Accent bar at top */
.result-card::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 5px;
  border-radius: 22px 22px 0 0;
}
.result-card.success::before  { background: linear-gradient(90deg, var(--green), var(--green-light)); }
.result-card.duplicate::before { background: linear-gradient(90deg, #c89000, #ffde00); }

/* Big result icon */
.result-icon {
  font-size: 4rem; margin-bottom: 16px; display: block;
  line-height: 1;
}
.pokeball-throw {
  display: inline-block;
  animation: popInOut 1.8s 0.2s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes throwAtFace {
  0%   { opacity:0; transform: scale(0.3); }
  40%  { opacity:1; transform: scale(1); }
  70%  { opacity:1; transform: scale(1.05); }
  100% { opacity:0; transform: scale(4); }
}

/* Monster image */
.monster-img-wrap {
  width: 220px; height: 220px; border-radius: 20px;
  margin: 0 auto 20px;
  display: flex; align-items: center; justify-content: center;
  position: relative;
}
.result-card.success  .monster-img-wrap { background: linear-gradient(145deg, #d0f8e8, #b0eed8); border: 3px solid #70c8a0; box-shadow: 0 4px 0 #3a9060, 0 0 24px rgba(0,180,100,0.25); }
.result-card.duplicate .monster-img-wrap { background: linear-gradient(145deg, #fff3d0, #ffe8a0); border: 3px solid #d8b060; box-shadow: 0 4px 0 #a07040, 0 0 24px rgba(200,150,0,0.2); }

.monster-img-wrap img {
  width: 160px; height: 160px; object-fit: contain;
  filter: drop-shadow(0 2px 8px rgba(0,0,0,0.2));
}
.monster-img-wrap .dino-fallback { font-size: 3rem; }

/* Status label */
.result-status {
  font-family: 'Press Start 2P', monospace; font-size: 0.34rem;
  letter-spacing: 0.18em; margin-bottom: 10px;
}
.result-card.success  .result-status { color: var(--green); }
.result-card.duplicate .result-status { color: #c89000; }

/* Main title */
.result-title {
  font-size: 1.6rem; font-weight: 900;
  color: #1a1030; letter-spacing: 0.03em; line-height: 1.1;
  margin-bottom: 6px;
}

/* Monster name */
.result-monster-name {
  font-family: 'Press Start 2P', monospace; font-size: 1rem;
  margin-bottom: 4px;
}
.result-card.success  .result-monster-name { color: var(--blue); }
.result-card.duplicate .result-monster-name { color: #886600; }

/* Type badge */
.result-type-badge {
  display: inline-block;
  font-family: 'Press Start 2P', monospace; font-size: 0.3rem;
  padding: 4px 10px; border-radius: 5px; letter-spacing: 0.1em;
  margin-bottom: 16px;
  background: linear-gradient(145deg, #e8e0f8, #d8d0f0);
  border: 1px solid #b0a8d0; color: var(--blue);
}

/* Sub message */
.result-sub {
  font-size: 0.82rem; color: #6a5a50; font-weight: 700;
  margin-bottom: 28px; line-height: 1.5;
}

/* Divider */
.result-divider {
  height: 1px; background: rgba(0,0,0,0.08);
  margin: 0 0 24px;
}

/* Action buttons */
.result-actions {
  display: flex; flex-direction: column; gap: 10px;
}

.btn-primary {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  background: linear-gradient(145deg, #6878f0, var(--blue));
  border: 2px solid #2030a0; border-radius: 8px;
  padding: 12px 20px; text-decoration: none;
  font-family: 'Press Start 2P', monospace; font-size: 0.6rem;
  color: #fff; letter-spacing: 0.1em;
  box-shadow: 0 4px 0 #1a2060, inset 0 1px 0 rgba(255,255,255,0.2);
  transition: transform .15s, box-shadow .15s, filter .15s;
}
.btn-primary:hover {
  transform: translateY(-2px); filter: brightness(1.12);
  box-shadow: 0 6px 0 #1a2060, 0 0 20px rgba(59,76,202,0.4), inset 0 1px 0 rgba(255,255,255,0.2);
}
.btn-primary:active { transform: translateY(2px); box-shadow: 0 2px 0 #1a2060; }

.btn-secondary {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  background: linear-gradient(145deg, #f0ecff, #e4dff8);
  border: 2px solid #c0b8d8; border-radius: 8px;
  padding: 10px 20px; text-decoration: none;
  font-family: 'Press Start 2P', monospace; font-size: 0.5rem;
  color: #5a4a70; letter-spacing: 0.1em;
  box-shadow: 0 3px 0 #a898c0;
  transition: transform .15s, box-shadow .15s;
}
.btn-secondary:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 0 #a898c0, 0 0 14px rgba(100,80,160,0.15);
}
.btn-secondary:active { transform: translateY(1px); box-shadow: 0 1px 0 #a898c0; }

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

@keyframes fadeUp {
  from { opacity:0; transform:translateY(18px); }
  to   { opacity:1; transform:translateY(0); }
}
@keyframes popIn {
  from { opacity:0; transform:scale(0.85) translateY(24px); }
  to   { opacity:1; transform:scale(1) translateY(0); }
}
@keyframes bounceIn {
  from { opacity:0; transform:scale(0.4); }
  to   { opacity:1; transform:scale(1); }
}

@media (max-width: 480px) {
  .result-card { padding: 40px 20px 40px; max-width: 340px; }
  .result-title { font-size: 1.3rem; }
  .result-icon { font-size: 2.5rem; margin-bottom: 10px; }
  .result-sub { margin-bottom: 16px; }
  .result-divider { margin: 0 0 14px; }
}
</style>
</head>
<body>

<div class="stars" id="stars"></div>
<div class="bg-balls" id="bgBalls"></div>

<header class="topbar">
  <div class="brand">
    <div class="brand-logo">HAUPokémon</div>
    <div class="brand-badge">CATCH</div>
  </div>
  <div class="topbar-right">
    <div class="power-led"></div>
  </div>
</header>

<main class="main">
  <div class="result-card <?php echo $success ? 'success' : 'duplicate'; ?>">
  <?php if ($success): ?>
  <div class="pokeball-overlay"></div>
  <div class="pokeball-throw">
    <img src="https://archives.bulbagarden.net/media/upload/b/b3/Pok%C3%A9_Ball_ZA_Art.png"
         onerror="this.style.display='none'"
         alt="Poké Ball"
         style="width:16rem;height:16rem;object-fit:contain;filter:drop-shadow(0 2px 20px #ffde00);display:block;">
    <div style="width:14rem;height:1rem;background:radial-gradient(ellipse, rgba(0,0,0,0.45) 0%, transparent 70%);margin:0 auto;margin-top:-0.5rem;"></div>
  </div>
  <?php endif; ?>

<?php if ($success): ?>
<?php else: ?>
<?php endif; ?>

    <?php if ($monster): ?>
    <div class="monster-img-wrap">
      <?php if (!empty($monster['picture_url'])): ?>
        <img src="<?php echo htmlspecialchars($monster['picture_url']); ?>"
             onerror="this.outerHTML='<span class=\'dino-fallback\'>🦕</span>'"
             alt="<?php echo htmlspecialchars($monster['monster_name']); ?>">
      <?php else: ?>
        <span class="dino-fallback">🦕</span>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="result-status"><?php echo $success ? '// CAPTURE SUCCESS' : '// ALREADY OWNED'; ?></div>
    <div class="result-title"><?php echo htmlspecialchars($message); ?></div>

    <?php if ($monster): ?>
      <div class="result-monster-name"><?php echo htmlspecialchars($monster['monster_name']); ?></div>
      <?php if (!empty($monster['monster_type'])): ?>
        <div class="result-type-badge"><?php echo htmlspecialchars(strtoupper($monster['monster_type'])); ?> TYPE</div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="result-sub"><?php echo htmlspecialchars($sub); ?></div>

    <div class="result-divider"></div>

    <div class="result-actions">
      <a class="btn-primary" href="player_monsters.php">VIEW MY MONSTERS</a>
      <a class="btn-secondary" href="catch_monsters.php">BACK TO HUNT</a>
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

<script>
(function() {
  const c = document.getElementById('stars');
  for (let i = 0; i < 55; i++) {
    const s = document.createElement('div'); s.className = 'star';
    s.style.left = Math.random() * 100 + 'vw'; s.style.top = Math.random() * 100 + 'vh';
    s.style.animationDuration = (1.5 + Math.random() * 2.5) + 's';
    s.style.animationDelay = (Math.random() * 2.5) + 's'; c.appendChild(s);
  }
})();
(function() {
  const c = document.getElementById('bgBalls');
  [60,90,50,120,70,80,55].forEach((sz, i) => {
    const w = document.createElement('div'); w.className = 'bg-ball';
    w.style.left = (8 + i * 13 + Math.random() * 10) + 'vw'; w.style.bottom = '-120px';
    w.style.animationDuration = (18 + Math.random() * 14) + 's';
    w.style.animationDelay = (i * 3 + Math.random() * 4) + 's';
    w.innerHTML = `<svg width="${sz}" height="${sz}" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="48" fill="none" stroke="white" stroke-width="5"/><path d="M2,50 A48,48 0 0,1 98,50 Z" fill="white" opacity=".7"/><rect x="2" y="46" width="96" height="8" fill="white"/><circle cx="50" cy="50" r="13" fill="none" stroke="white" stroke-width="5"/><circle cx="50" cy="50" r="7" fill="white"/></svg>`;
    c.appendChild(w);
  });
})();
</script>
</body>
</html>