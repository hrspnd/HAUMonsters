<?php
session_start();
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}
$conn = new mysqli("10.1.1.17", "badets", "badets@1234", "haumonstersDB");
$result = $conn->query("
SELECT p.username, COUNT(c.catch_id) AS total
FROM monster_catchestbl c
JOIN playerstbl p ON c.player_id = p.player_id
GROUP BY p.player_id
ORDER BY total DESC
LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Leaderboard — HAUPokémon</title>
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
  background: linear-gradient(180deg, #ffd040, #c89000);
  color: #3a2000; padding: 4px 9px; border-radius: 4px;
  border: 2px solid #886000;
  box-shadow: 0 3px 0 #664000, inset 0 1px 0 rgba(255,255,255,0.3);
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

/* MAIN */
.main {
  flex: 1; padding: 28px 28px 56px;
  max-width: 820px; margin: 0 auto; width: 100%;
  position: relative; z-index: 1;
}

/* PAGE HEADER */
.page-header {
  display: flex; align-items: flex-end; justify-content: space-between;
  flex-wrap: wrap; gap: 12px; margin-bottom: 24px;
  animation: fadeUp 0.4s ease both;
}
.page-eyebrow {
  font-family: 'Press Start 2P', monospace; font-size: 0.36rem;
  color: var(--blue-light); letter-spacing: 0.22em;
  margin-bottom: 8px; display: flex; align-items: center; gap: 8px;
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

/* TOP 3 PODIUM */
.podium-row {
  display: grid; grid-template-columns: 1fr 1.15fr 1fr;
  gap: 14px; margin-bottom: 24px;
  animation: fadeUp 0.5s 0.15s ease both;
}

.podium-card {
  background: linear-gradient(155deg, #ffffff 0%, #f7f2e8 55%, #ede6d8 100%);
  border: 3px solid #ccc4b0; border-radius: 18px;
  padding: 20px 14px 16px;
  text-align: center; position: relative; overflow: hidden;
  box-shadow: 0 6px 0 #a89880, 0 10px 28px rgba(0,0,0,0.28), inset 0 1px 0 #fff;
  transition: transform 0.2s;
}
.podium-card:hover { transform: translateY(-4px); }

/* Gold - 1st place (middle, taller) */
.podium-card.rank-1 {
  background: linear-gradient(155deg, #fffdf0 0%, #fff8d0 55%, #ffeea0 100%);
  border-color: #e8d060;
  box-shadow: 0 8px 0 #c8a800, 0 14px 36px rgba(200,168,0,0.3), inset 0 1px 0 #fffce0;
}
.podium-card.rank-2 {
  background: linear-gradient(155deg, #f8f8ff 0%, #f0f0f8 55%, #e0e0f0 100%);
  border-color: #b8b8d8;
  box-shadow: 0 6px 0 #909090, 0 10px 24px rgba(100,100,160,0.2), inset 0 1px 0 #fff;
}
.podium-card.rank-3 {
  background: linear-gradient(155deg, #fff8f0 0%, #ffeee0 55%, #ffddc8 100%);
  border-color: #d8a888;
  box-shadow: 0 6px 0 #a87040, 0 10px 24px rgba(168,112,64,0.2), inset 0 1px 0 #fff;
}

.podium-medal {
  font-size: 2.2rem; margin-bottom: 8px;
  display: block; line-height: 1;
  filter: drop-shadow(0 3px 6px rgba(0,0,0,0.2));
}
.podium-card.rank-1 .podium-medal { font-size: 2.8rem; }

.podium-rank-label {
  font-family: 'Press Start 2P', monospace; font-size: 0.34rem;
  letter-spacing: 0.1em; margin-bottom: 8px;
}
.podium-card.rank-1 .podium-rank-label { color: #886600; }
.podium-card.rank-2 .podium-rank-label { color: #606080; }
.podium-card.rank-3 .podium-rank-label { color: #885030; }

.podium-username {
  font-size: 0.92rem; font-weight: 900; color: #1a1030;
  margin-bottom: 4px; letter-spacing: 0.04em;
  word-break: break-all;
}
.podium-catches {
  font-family: 'Press Start 2P', monospace; font-size: 0.58rem;
  margin-bottom: 4px;
}
.podium-card.rank-1 .podium-catches { color: #aa8800; }
.podium-card.rank-2 .podium-catches { color: #606080; }
.podium-card.rank-3 .podium-catches { color: #885030; }

.podium-catch-label {
  font-size: 0.68rem; color: #8a7a60; font-weight: 700;
}

/* Ribbon watermark */
.podium-ribbon {
  position: absolute; top: 0; right: 0;
  width: 0; height: 0; border-style: solid;
  border-width: 0 44px 44px 0; opacity: 0.12;
}
.podium-card.rank-1 .podium-ribbon { border-color: transparent #c89000 transparent transparent; }
.podium-card.rank-2 .podium-ribbon { border-color: transparent #8090a0 transparent transparent; }
.podium-card.rank-3 .podium-ribbon { border-color: transparent #a07050 transparent transparent; }

/* TABLE CARD */
.table-card {
  background: linear-gradient(155deg, #ffffff 0%, #f7f2e8 55%, #ede6d8 100%);
  border: 3px solid #ccc4b0; border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 8px 0 #a89880, 0 14px 40px rgba(0,0,0,0.3), inset 0 1px 0 #fff;
  animation: fadeUp 0.5s 0.22s ease both;
  position: relative;
}

.table-card-header {
  padding: 14px 20px;
  background: linear-gradient(145deg, #f0ebff, #e8e2f8);
  border-bottom: 2px solid #d0c8e0;
  display: flex; align-items: center; justify-content: space-between;
}
.table-card-title {
  font-family: 'Press Start 2P', monospace; font-size: 0.8rem;
  color: var(--blue); letter-spacing: 0.14em;
  display: flex; align-items: center; gap: 8px;
}
.table-card-title::before { content: '🏆'; font-size: 0.9rem; }

.table-count-badge {
  font-family: 'Press Start 2P', monospace; font-size: 0.26rem;
  background: linear-gradient(145deg, #d8e4ff, #b8caff);
  border: 1px solid #90a8e8; color: var(--blue);
  border-radius: 5px; padding: 4px 9px; letter-spacing: 0.08em;
}

/* Table */
.rank-table { width: 100%; border-collapse: collapse; }

.rank-table thead tr {
  background: linear-gradient(145deg, #e8e0f8, #ddd6f0);
  border-bottom: 2px solid #c8c0e0;
}
.rank-table th {
  padding: 12px 20px;
  font-family: 'Press Start 2P', monospace; font-size: 0.5rem;
  color: var(--blue); letter-spacing: 0.14em;
  text-align: left; font-weight: 400;
}
.rank-table th:last-child { text-align: right; }

.rank-table tbody tr {
  border-bottom: 1px solid rgba(180,170,200,0.3);
  transition: background 0.15s;
}
.rank-table tbody tr:last-child { border-bottom: none; }
.rank-table tbody tr:hover { background: rgba(59,76,202,0.04); }

.rank-table td {
  padding: 13px 20px;
  font-size: 0.88rem; color: #2a2040;
}
.rank-table td:last-child { text-align: right; }

/* Rank number cell */
.rank-num {
  display: inline-flex; align-items: center; justify-content: center;
  width: 28px; height: 28px; border-radius: 7px;
  font-family: 'Press Start 2P', monospace; font-size: 0.36rem;
  font-weight: 400;
}
.rank-num.top1 { background: linear-gradient(145deg,#fff3b0,#ffd840); border:2px solid #d8a800; color:#886600; box-shadow:0 2px 0 #a87800; }
.rank-num.top2 { background: linear-gradient(145deg,#e8e8f8,#d0d0e8); border:2px solid #a0a0c0; color:#505070; box-shadow:0 2px 0 #808090; }
.rank-num.top3 { background: linear-gradient(145deg,#ffe8d8,#ffd0b8); border:2px solid #d09070; color:#885030; box-shadow:0 2px 0 #a07050; }
.rank-num.other { background: linear-gradient(145deg,#f0ecff,#e4dff8); border:2px solid #c0b8d8; color:#6a5a80; box-shadow:0 2px 0 #a898c0; }

/* Username cell */
.username-cell {
  display: flex; align-items: center; gap: 10px;
}
.trainer-avatar {
  width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
  background: linear-gradient(135deg, var(--red-light), var(--red));
  border: 2px solid #880000;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; box-shadow: 0 2px 0 #660000;
}
.username-text { font-weight: 900; color: #1a1030; }

/* Catch count cell */
.catch-count {
  font-family: 'Press Start 2P', monospace; font-size: 0.58rem;
  color: var(--blue);
}
.catch-bar-wrap { display: flex; align-items: center; gap: 8px; margin-top: 4px; }
.catch-bar-track {
  width: 80px; height: 6px; border-radius: 3px;
  background: rgba(0,0,0,0.08); border: 1px solid rgba(0,0,0,0.08); overflow: hidden;
}
.catch-bar-fill {
  height: 100%; border-radius: 3px;
  background: linear-gradient(90deg, var(--blue-light), var(--blue));
  transition: width 1s cubic-bezier(.4,0,.2,1);
}

.nav-card-back {
  display: inline-flex; align-items: center; gap: 8px;
  background: linear-gradient(145deg, #6878f0, var(--blue));
  border: 2px solid #2030a0; border-radius: 8px;
  padding: 10px 18px;
  text-decoration: none;
  font-family: 'Press Start 2P', monospace; font-size: 0.48rem;
  color: #fff; letter-spacing: 0.1em;
  box-shadow: 0 4px 0 #1a2060, inset 0 1px 0 rgba(255,255,255,0.2);
  transition: transform .15s, box-shadow .15s, filter .15s;
  cursor: pointer;
}
.nav-card-back:hover {
  transform: translateY(-2px);
  filter: brightness(1.15);
  box-shadow: 0 6px 0 #1a2060, 0 0 20px rgba(59,76,202,0.4), inset 0 1px 0 rgba(255,255,255,0.2);
}
.nav-card-back:active { transform: translateY(2px); box-shadow: 0 2px 0 #1a2060; }

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
  .main { padding: 20px 14px 36px; }
  .page-title-main { font-size: 1.5rem; }
  .podium-row { grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
  .podium-card { padding: 14px 8px 12px; }
  .podium-medal { font-size: 1.6rem; }
  .podium-card.rank-1 .podium-medal { font-size: 2rem; }
  .catch-bar-wrap { display: none; }
  .rank-table th, .rank-table td { padding: 10px 12px; }
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
    <div class="brand-badge">RANKINGS</div>
  </div>
  <div class="topbar-right">
    <div class="power-led"></div>
  </div>
</header>

<main class="main">

  <div class="page-header">
    <div>
      <div class="page-eyebrow">// HALL OF FAME</div>
    </div>
    <?php
    $dashboard = ($_SESSION['role'] == 'admin') ? 'admin_dashboard.php' : 'player_dashboard.php';
    echo '<a class="nav-card-back" href="'.$dashboard.'" onclick="showToast(\'Going back...\')">◀ BACK</a>';
    ?>
  </div>

  <?php
  // Collect all rows for rendering
  $rows = [];
  while ($row = $result->fetch_assoc()) {
      $rows[] = $row;
  }
  $maxCatches = !empty($rows) ? max(array_column($rows, 'total')) : 1;
  $avatars = ['🧢','🎩','👑','🧣','🎓','🎭','🎪','🏅','🎯'];
  ?>

  <!-- Podium: Top 3 -->
  <?php if (count($rows) >= 3): ?>
  <div class="section-label">
    <div class="section-label-pip"></div>
    <span class="section-label-text">Top Trainers</span>
    <div class="section-label-line"></div>
  </div>

  <div class="podium-row">
    <!-- 2nd place (left) -->
    <?php if (isset($rows[1])): ?>
    <div class="podium-card rank-2">
      <div class="podium-ribbon"></div>
      <span class="podium-medal">🥈</span>
      <div class="podium-rank-label">2ND PLACE</div>
      <div class="podium-username"><?php echo htmlspecialchars($rows[1]['username']); ?></div>
      <div class="podium-catches"><?php echo htmlspecialchars($rows[1]['total']); ?></div>
      <div class="podium-catch-label">catches</div>
    </div>
    <?php endif; ?>

    <!-- 1st place (center) -->
    <div class="podium-card rank-1">
      <div class="podium-ribbon"></div>
      <span class="podium-medal">🥇</span>
      <div class="podium-rank-label">1ST PLACE</div>
      <div class="podium-username"><?php echo htmlspecialchars($rows[0]['username']); ?></div>
      <div class="podium-catches"><?php echo htmlspecialchars($rows[0]['total']); ?></div>
      <div class="podium-catch-label">catches</div>
    </div>

    <!-- 3rd place (right) -->
    <?php if (isset($rows[2])): ?>
    <div class="podium-card rank-3">
      <div class="podium-ribbon"></div>
      <span class="podium-medal">🥉</span>
      <div class="podium-rank-label">3RD PLACE</div>
      <div class="podium-username"><?php echo htmlspecialchars($rows[2]['username']); ?></div>
      <div class="podium-catches"><?php echo htmlspecialchars($rows[2]['total']); ?></div>
      <div class="podium-catch-label">catches</div>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Full Rankings Table -->
  <div class="section-label">
    <div class="section-label-pip"></div>
    <span class="section-label-text">Full Rankings</span>
    <div class="section-label-line"></div>
  </div>

  <div class="table-card">
    <div class="table-card-header">
      <div class="table-card-title">Monster Hunters</div>
      <div class="table-count-badge">TOP <?php echo count($rows); ?></div>
    </div>

    <table class="rank-table">
      <thead>
        <tr>
          <th>RANK</th>
          <th>TRAINER</th>
          <th>CATCHES</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $i => $row):
          $rank = $i + 1;
          $rankClass = $rank === 1 ? 'top1' : ($rank === 2 ? 'top2' : ($rank === 3 ? 'top3' : 'other'));
          $barWidth = $maxCatches > 0 ? round(($row['total'] / $maxCatches) * 100) : 0;
          $avatar = $avatars[$i % count($avatars)];
        ?>
        <tr>
          <td><span class="rank-num <?php echo $rankClass; ?>"><?php echo $rank; ?></span></td>
          <td>
            <div class="username-cell">
              <div class="trainer-avatar"><?php echo $avatar; ?></div>
              <span class="username-text"><?php echo htmlspecialchars($row['username']); ?></span>
            </div>
          </td>
          <td>
            <div class="catch-count"><?php echo htmlspecialchars($row['total']); ?></div>
            <div class="catch-bar-wrap">
              <div class="catch-bar-track">
                <div class="catch-bar-fill" style="width:<?php echo $barWidth; ?>%"></div>
              </div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
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
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg.toUpperCase();
  t.classList.add('show');
  clearTimeout(t._tid);
  t._tid = setTimeout(() => t.classList.remove('show'), 2500);
}
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