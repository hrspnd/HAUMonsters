<?php
session_start();
$conn = new mysqli("10.1.1.17", "badets", "badets@1234", "haumonstersDB");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['signup'])) {
    $name = $_POST['player_name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = 'user';

    $check = $conn->prepare("SELECT * FROM playerstbl WHERE username=?");
    $check->bind_param("s", $username);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "Username already exists!";
    } else {
        // Hash the password before storing
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO playerstbl (player_name, username, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $username, $hashedPassword, $role);
        $stmt->execute();

        $_SESSION['username'] = $username;
        $_SESSION['role'] = 'user';
        header("Location: player_dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Sign Up — HAUPokémon</title>
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
  display: flex;
  flex-direction: column;
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
  background: linear-gradient(180deg, var(--green-light), var(--green));
  color: #fff; padding: 4px 9px; border-radius: 4px;
  border: 2px solid #006030;
  box-shadow: 0 3px 0 #004020, inset 0 1px 0 rgba(255,255,255,0.2);
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

.main {
  flex: 1;
  padding: 48px 28px 56px;
  max-width: 520px; margin: 0 auto; width: 100%;
  position: relative; z-index: 1;
  display: flex; flex-direction: column; justify-content: center;
}

.form-card {
  background: linear-gradient(155deg, #ffffff 0%, #f7f2e8 55%, #ede6d8 100%);
  border: 3px solid #ccc4b0;
  border-radius: 22px;
  padding: 36px 32px 32px;
  position: relative; overflow: hidden;
  box-shadow:
    0 8px 0 #a89880,
    0 14px 40px rgba(0,0,0,0.35),
    inset 0 1px 0 #fff,
    inset 0 -2px 0 rgba(0,0,0,0.04);
  animation: fadeUp 0.45s ease both;
}

.form-ribbon {
  position: absolute; top: 0; right: 0;
  width: 0; height: 0; border-style: solid;
  border-width: 0 70px 70px 0;
  border-color: transparent var(--blue) transparent transparent;
  opacity: 0.15;
}

.form-pokeball-bg {
  position: absolute; right: -28px; bottom: -28px;
  width: 130px; height: 130px; pointer-events: none; opacity: 0.07;
}

.form-accent-top {
  position: absolute; top: 0; left: 0; right: 0; height: 5px;
  background: linear-gradient(90deg, var(--blue), var(--blue-light) 60%, transparent 90%);
  border-radius: 20px 20px 0 0;
}

.form-eyebrow {
  font-family: 'Press Start 2P', monospace; font-size: 0.32rem;
  color: var(--blue); letter-spacing: 0.22em;
  margin-bottom: 10px;
  display: flex; align-items: center; gap: 8px;
  position: relative; z-index: 1;
}
.form-eyebrow::before {
  content: ''; display: inline-block; width: 14px; height: 2px;
  background: var(--blue);
}

.form-title {
  font-size: 1.7rem; font-weight: 900;
  color: #1a1030; letter-spacing: 0.03em; line-height: 1.1;
  margin-bottom: 6px;
  position: relative; z-index: 1;
}
.form-title span { color: var(--blue); text-shadow: 2px 2px 0 rgba(40,60,180,0.15); }
.form-subtitle {
  font-size: 0.8rem; color: #7a6a50; font-weight: 700;
  margin-bottom: 28px; position: relative; z-index: 1;
}

.field-group { margin-bottom: 18px; position: relative; z-index: 1; }
.field-label {
  display: flex; align-items: center; gap: 7px;
  font-family: 'Press Start 2P', monospace; font-size: 0.3rem;
  color: #5a4a70; letter-spacing: 0.14em; margin-bottom: 7px;
}
.field-label-icon {
  width: 20px; height: 20px; border-radius: 5px;
  background: linear-gradient(145deg, #d8e4ff, #b8caff);
  border: 1px solid #90a8e8;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.75rem;
}
.field-input {
  width: 100%; padding: 12px 16px;
  background: linear-gradient(145deg, #faf8ff, #f0ecf8);
  border: 2px solid #c8c0d8; border-radius: 10px;
  font-family: 'Nunito', sans-serif; font-size: 0.9rem; font-weight: 800;
  color: #1a1030; outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.06), 0 2px 0 #b8b0c8;
}
.field-input::placeholder { color: #a898c0; font-weight: 700; }
.field-input:focus {
  border-color: var(--blue);
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.06), 0 2px 0 #1a2060, 0 0 0 3px rgba(59,76,202,0.15);
}

.btn-submit {
  width: 100%; padding: 14px;
  background: linear-gradient(180deg, var(--blue-light), var(--blue));
  border: 3px solid #2030a0; border-radius: 12px;
  font-family: 'Press Start 2P', monospace; font-size: 0.46rem;
  color: #fff; letter-spacing: 0.12em; cursor: pointer;
  box-shadow: 0 5px 0 #1a2060, 0 8px 20px rgba(59,76,202,0.3), inset 0 1px 0 rgba(255,255,255,0.2);
  transition: transform 0.15s, box-shadow 0.15s;
  position: relative; z-index: 1; margin-top: 8px;
}
.btn-submit:hover {
  transform: translateY(-2px);
  box-shadow: 0 7px 0 #1a2060, 0 12px 28px rgba(59,76,202,0.4), inset 0 1px 0 rgba(255,255,255,0.2);
}
.btn-submit:active {
  transform: translateY(3px);
  box-shadow: 0 2px 0 #1a2060, 0 4px 12px rgba(59,76,202,0.2);
}

.form-divider {
  display: flex; align-items: center; gap: 10px;
  margin: 20px 0; position: relative; z-index: 1;
}
.form-divider-line { flex: 1; height: 1px; background: rgba(90,74,112,0.2); }
.form-divider-text {
  font-family: 'Press Start 2P', monospace; font-size: 0.26rem;
  color: #a898c0; letter-spacing: 0.1em;
}

.back-link {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  text-decoration: none;
  font-family: 'Press Start 2P', monospace; font-size: 0.3rem;
  color: var(--blue); letter-spacing: 0.1em; padding: 11px;
  background: linear-gradient(145deg, #e8ecff, #d8dfff);
  border: 2px solid #b0bcf0; border-radius: 10px;
  transition: transform 0.15s, box-shadow 0.15s;
  box-shadow: 0 3px 0 #9098d0; position: relative; z-index: 1;
}
.back-link:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 0 #9098d0, 0 8px 20px rgba(59,76,202,0.15);
}

.bottom-bar {
  display:flex; align-items:center; justify-content:space-between;
  padding:14px 28px;
  background:rgba(8,6,20,0.92); border-top:2px solid #1e2060;
  font-size:.68rem; color:#4a4a7a; letter-spacing:.08em;
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

@media (max-width: 480px) {
  .main { padding: 28px 14px 36px; }
  .form-card { padding: 28px 18px 24px; }
  .form-title { font-size: 1.3rem; }
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
    <div class="brand-badge">SIGN UP</div>
  </div>
  <div class="topbar-right">
    <div class="power-led"></div>
  </div>
</header>

<main class="main">
  <div class="form-card">
    <div class="form-accent-top"></div>
    <div class="form-ribbon"></div>
    <svg class="form-pokeball-bg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
      <circle cx="50" cy="50" r="48" fill="none" stroke="#3b4cca" stroke-width="6"/>
      <path d="M2,50 A48,48 0 0,1 98,50" fill="#3b4cca"/>
      <rect x="2" y="46" width="96" height="8" fill="#3b4cca"/>
      <circle cx="50" cy="50" r="13" fill="none" stroke="#3b4cca" stroke-width="6"/>
      <circle cx="50" cy="50" r="7" fill="#3b4cca"/>
    </svg>

    <div class="form-eyebrow">// NEW TRAINER</div>
    <div class="form-title">Create <span>Account</span></div>
    <div class="form-subtitle">Register to start your monster journey</div>

    <form method="POST">
      <div class="field-group">
        <label class="field-label">
          <span class="field-label-icon">🧑</span>
          NAME
        </label>
        <input class="field-input" type="text" name="player_name" placeholder="Enter your name" required>
      </div>

      <div class="field-group">
        <label class="field-label">
          <span class="field-label-icon">🪪</span>
          USERNAME
        </label>
        <input class="field-input" type="text" name="username" placeholder="Choose a username" required>
      </div>

      <div class="field-group">
        <label class="field-label">
          <span class="field-label-icon">🔑</span>
          PASSWORD
        </label>
        <input class="field-input" type="password" name="password" placeholder="Create a password" required>
      </div>

      <button class="btn-submit" name="signup" type="submit" onclick="showToast('Creating account...')">
        ▶ SIGN UP
      </button>
    </form>

    <div class="form-divider">
      <div class="form-divider-line"></div>
      <span class="form-divider-text">OR</span>
      <div class="form-divider-line"></div>
    </div>

    <a class="back-link" href="login.php">◀ BACK TO LOGIN</a>
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