<?php
session_start();

$conn = new mysqli("10.1.1.17", "badets", "badets@1234", "haumonstersDB");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // ONLY get user by username
    $stmt = $conn->prepare("SELECT * FROM playerstbl WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // VERIFY HASHED PASSWORD
    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['player_id'] = $user['player_id'];

        if ($user['role'] == 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: player_dashboard.php");
        }
        exit();

    } else {
        $message = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Login — HAUPokémon</title>
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
  min-height: 100vh;
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

.main {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  position: relative; z-index: 1;
}

.login-card {
  width: 100%;
  max-width: 420px;
  background: linear-gradient(155deg, #ffffff 0%, #f7f2e8 55%, #ede6d8 100%);
  border: 3px solid #ccc4b0;
  border-radius: 24px;
  padding: 36px 32px 32px;
  position: relative; overflow: hidden;
  animation: fadeUp 0.5s ease both;
  box-shadow:
    0 8px 0 #a89880,
    0 14px 40px rgba(0,0,0,0.35),
    inset 0 1px 0 #fff,
    inset 0 -2px 0 rgba(0,0,0,0.04);
}

.login-ribbon {
  position: absolute; top: 0; right: 0;
  width: 0; height: 0; border-style: solid;
  border-width: 0 72px 72px 0;
  border-color: transparent var(--red) transparent transparent;
  opacity: 0.18;
}

.login-pb-bg {
  position: absolute; right: -30px; bottom: -30px;
  width: 140px; height: 140px; pointer-events: none; opacity: 0.07;
}

.login-stripe {
  position: absolute; inset: 0; pointer-events: none; border-radius: 22px;
  background: repeating-linear-gradient(
    -45deg, transparent, transparent 18px,
    rgba(0,0,0,0.016) 18px, rgba(0,0,0,0.016) 19px
  );
}

.login-accent {
  position: absolute; bottom: 0; left: 0; right: 0; height: 5px;
  background: linear-gradient(90deg, var(--red), #ff9090 60%, transparent 90%);
  border-radius: 0 0 22px 22px;
}

.login-header {
  text-align: center;
  margin-bottom: 28px;
  position: relative; z-index: 1;
}
.login-icon {
  width: 64px; height: 64px; border-radius: 18px;
  background: linear-gradient(145deg, #ffe8e8, #ffd0d0);
  border: 2px solid #f0b0b0;
  box-shadow: 0 4px 0 #d09090, inset 0 1px 0 rgba(255,255,255,0.9);
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem;
  margin: 0 auto 16px;
}
.login-eyebrow {
  font-family: 'Press Start 2P', monospace; font-size: 0.32rem;
  color: var(--blue); letter-spacing: 0.22em;
  margin-bottom: 8px;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.login-eyebrow::before, .login-eyebrow::after {
  content: ''; display: inline-block; width: 14px; height: 2px;
  background: var(--blue);
}
.login-title {
  font-size: 1.5rem; font-weight: 900;
  color: #1a1030; letter-spacing: 0.04em;
}
.login-title span { color: var(--red); }
.login-sub { font-size: 0.78rem; color: #8a7a60; margin-top: 4px; }

.error-box {
  background: #fff0f0;
  border: 2px solid #f0b0b0;
  border-radius: 10px;
  padding: 10px 14px;
  margin-bottom: 20px;
  display: flex; align-items: center; gap: 10px;
  position: relative; z-index: 1;
  animation: shake 0.4s ease;
}
@keyframes shake {
  0%,100%{transform:translateX(0)}
  20%{transform:translateX(-6px)}
  40%{transform:translateX(6px)}
  60%{transform:translateX(-4px)}
  80%{transform:translateX(4px)}
}
.error-icon { font-size: 1.1rem; flex-shrink: 0; }
.error-text {
  font-family: 'Press Start 2P', monospace; font-size: 0.28rem;
  color: #cc2222; letter-spacing: 0.05em; line-height: 1.6;
}

.login-form { position: relative; z-index: 1; }

.field-group { margin-bottom: 18px; }
.field-label {
  display: flex; align-items: center; gap: 6px;
  font-family: 'Press Start 2P', monospace; font-size: 0.3rem;
  color: #5a4a30; letter-spacing: 0.12em;
  margin-bottom: 8px;
}
.field-label-pip { width: 6px; height: 6px; border-radius: 1px; background: var(--red); opacity: 0.6; }
.field-input-wrap { position: relative; }
.field-input-icon {
  position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
  font-size: 1rem; pointer-events: none; z-index: 1;
}
.field-input {
  width: 100%;
  padding: 12px 14px 12px 40px;
  background: linear-gradient(180deg, #faf8f4, #f5f0e8);
  border: 2px solid #c8bea8;
  border-radius: 10px;
  font-family: 'Nunito', sans-serif; font-size: 0.95rem; font-weight: 800;
  color: #1a1030;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.06), 0 2px 0 #d8cdb8;
}
.field-input::placeholder { color: #b0a090; font-weight: 700; }
.field-input:focus {
  border-color: var(--blue);
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.06), 0 2px 0 #d8cdb8, 0 0 0 3px rgba(59,76,202,0.15);
}

.login-btn {
  width: 100%; margin-top: 8px;
  padding: 14px 20px;
  background: linear-gradient(180deg, var(--red-light) 0%, var(--red) 50%, var(--red-dark) 100%);
  border: 3px solid #660000; border-radius: 12px;
  color: #fff;
  font-family: 'Press Start 2P', monospace; font-size: 0.5rem;
  letter-spacing: 0.12em; cursor: pointer;
  box-shadow: 0 5px 0 #440000, 0 8px 20px rgba(204,0,0,0.3), inset 0 1px 0 rgba(255,255,255,0.25);
  transition: transform 0.12s, box-shadow 0.12s;
  position: relative; overflow: hidden;
}
.login-btn::before {
  content: ''; position: absolute; inset: 0;
  background: linear-gradient(180deg, rgba(255,255,255,0.15) 0%, transparent 50%);
  border-radius: 10px;
}
.login-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 7px 0 #440000, 0 12px 28px rgba(204,0,0,0.4), inset 0 1px 0 rgba(255,255,255,0.25);
}
.login-btn:active {
  transform: translateY(3px);
  box-shadow: 0 2px 0 #440000, 0 4px 12px rgba(204,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.25);
}

.divider { display: flex; align-items: center; gap: 10px; margin: 20px 0 16px; }
.divider-line { flex: 1; height: 1px; background: #d8cdb8; }
.divider-text {
  font-family: 'Press Start 2P', monospace; font-size: 0.26rem;
  color: #b0a090; letter-spacing: 0.1em; white-space: nowrap;
}

.signup-link-wrap { text-align: center; }
.signup-link {
  display: inline-flex; align-items: center; gap: 8px;
  background: linear-gradient(180deg, #f0ecf8, #e8e0f0);
  border: 2px solid #c0b8d8; border-radius: 10px;
  padding: 10px 20px; text-decoration: none; color: var(--blue);
  font-family: 'Press Start 2P', monospace; font-size: 0.3rem; letter-spacing: 0.08em;
  box-shadow: 0 3px 0 #a0a0c0, 0 6px 16px rgba(59,76,202,0.12);
  transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
}
.signup-link:hover {
  transform: translateY(-2px); border-color: var(--blue);
  box-shadow: 0 5px 0 #2a3aaa, 0 10px 24px rgba(59,76,202,0.22);
  color: var(--blue-light);
}

.bottom-bar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 14px 28px;
  background: rgba(8,6,20,0.92);
  border-top: 2px solid #1e2060;
  font-size: .68rem; color: #4a4a7a; letter-spacing: .08em;
  position: relative; z-index: 1;
}
.bottom-bar-left { display: flex; align-items: center; gap: 10px; }
.pixel-dots { display: flex; gap: 5px; }
.pixel-dot { width: 7px; height: 7px; border-radius: 2px; }
.pixel-dot:nth-child(1){background:#1e2060;}
.pixel-dot:nth-child(2){background:var(--blue);}
.pixel-dot:nth-child(3){background:var(--blue-light);opacity:.7;}
.version-tag {
  font-family: 'Press Start 2P', monospace; font-size: .3rem;
  color: var(--yellow); letter-spacing: .08em; opacity: .7;
}

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

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

@media (max-width: 480px) {
  .login-card { padding: 28px 20px 24px; }
  .topbar { padding: 0 16px; }
  .brand-logo { font-size: .88rem; }
  .login-title { font-size: 1.2rem; }
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
    <div class="brand-badge">LOGIN</div>
  </div>
  <div class="topbar-right">
    <div class="power-led"></div>
  </div>
</header>

<main class="main">
  <div class="login-card">
    <div class="login-ribbon"></div>
    <div class="login-stripe"></div>
    <div class="login-accent"></div>

    <svg class="login-pb-bg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
      <circle cx="50" cy="50" r="48" fill="none" stroke="#cc0000" stroke-width="6"/>
      <path d="M2,50 A48,48 0 0,1 98,50" fill="#cc0000"/>
      <rect x="2" y="46" width="96" height="8" fill="#cc0000"/>
      <circle cx="50" cy="50" r="13" fill="none" stroke="#cc0000" stroke-width="6"/>
      <circle cx="50" cy="50" r="7" fill="#cc0000"/>
    </svg>

    <div class="login-header">
      <div class="login-icon">🎮</div>
      <div class="login-eyebrow">TRAINER ACCESS</div>
      <div class="login-title">Welcome <span>Back!</span></div>
      <div class="login-sub">Log in to continue your journey</div>
    </div>

    <?php if (!empty($message)): ?>
    <div class="error-box">
      <div class="error-icon">⚠️</div>
      <div class="error-text"><?php echo htmlspecialchars($message); ?></div>
    </div>
    <?php endif; ?>

    <form method="POST" class="login-form">
      <div class="field-group">
        <div class="field-label">
          <div class="field-label-pip"></div>
          TRAINER NAME
        </div>
        <div class="field-input-wrap">
          <span class="field-input-icon">🧢</span>
          <input class="field-input" type="text" name="username" placeholder="Enter your username" required autocomplete="username">
        </div>
      </div>

      <div class="field-group">
        <div class="field-label">
          <div class="field-label-pip"></div>
          PASSWORD
        </div>
        <div class="field-input-wrap">
          <span class="field-input-icon">🔒</span>
          <input class="field-input" type="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
        </div>
      </div>

      <button class="login-btn" name="login" type="submit" onclick="showToast('Logging in...')">
        ▶ START GAME
      </button>
    </form>

    <div class="divider">
      <div class="divider-line"></div>
      <div class="divider-text">DON'T HAVE AN ACCOUNT?</div>
      <div class="divider-line"></div>
    </div>

    <div class="signup-link-wrap">
      <a class="signup-link" href="signup.php" onclick="showToast('Sign Up')">✚ SIGN UP</a>
    </div>
  </div>
</main>

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