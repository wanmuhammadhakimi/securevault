<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="A local-first file vault built around three enforced mechanisms: AES-256 encryption, fingerprint-gated access control, and real-time intrusion detection.">
<link rel="icon" href="data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A//www.w3.org/2000/svg%27%20viewBox%3D%270%200%2034%2034%27%3E%3Ccircle%20cx%3D%2717%27%20cy%3D%2717%27%20r%3D%2716%27%20fill%3D%27%23080a0d%27/%3E%3Ccircle%20cx%3D%2717%27%20cy%3D%2717%27%20r%3D%2715%27%20fill%3D%27none%27%20stroke%3D%27%2317b8d6%27%20stroke-width%3D%271.4%27/%3E%3Cg%20fill%3D%27none%27%20stroke%3D%27%235ef0ff%27%20stroke-linecap%3D%27round%27%3E%3Cpath%20d%3D%27M9.04%2026.36%20A15.13%2018.91%20-4.0%201%201%2025.04%2025.27%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M9.87%2024.36%20A13.24%2016.54%20-2.7%201%201%2023.18%2023.82%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M10.71%2022.36%20A11.35%2014.18%20-1.4%201%201%2021.55%2022.22%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M11.55%2020.29%20A9.45%2011.82%20-0.1%201%201%2020.05%2020.44%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M12.42%2018.25%20A7.56%209.45%201.2%201%201%2018.82%2018.51%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M13.29%2016.15%20A5.67%207.09%202.5%201%201%2017.73%2016.47%27%20stroke-width%3D%270.9%27/%3E%3Cpath%20d%3D%27M14.16%2014.07%20A3.78%204.73%203.8%201%201%2016.89%2014.33%27%20stroke-width%3D%270.9%27/%3E%3Cpath%20d%3D%27M15.18%2013.02%20A3.64%202.55%202%201%201%2015.36%208.00%27%20stroke-width%3D%270.75%27%20opacity%3D%270.8%27/%3E%3Cpath%20d%3D%27M14.89%2014.91%20A2.18%201.45%20-3%201%201%2014.02%2012.15%27%20stroke-width%3D%270.75%27%20opacity%3D%270.8%27/%3E%3C/g%3E%3C/svg%3E">
<title>SecureVault — Cipher-Grade File Security</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="cyber-bg"></div>
<?php include 'partials/network-bg.php'; ?>
<div class="noise"></div>

<?php $active = 'home'; include 'partials/nav.php'; ?>

<section class="hero">
  <div>
    <div class="eyebrow">CBS/09/24B — German-Malaysian Institute</div>
    <h1 id="decryptHeadline" class="decrypt"></h1>
    <p>Every file locked behind AES-256 encryption, fingerprint-gated before it can be opened or deleted, and watched by a
       signature-based detection layer that never sleeps. Nothing leaves your own infrastructure.</p>
    <div class="hero-actions">
      <a href="app.php" class="btn btn-brass btn-lg">Request access</a>
      <a href="app.php" class="btn btn-outline btn-lg">Sign in</a>
    </div>
    <div class="hero-meta">
      <div><b>AES-256</b>Cipher standard</div>
      <div><b>LOCAL</b>Zero cloud exposure</div>
      <div><b>RBAC</b>Role-scoped vault</div>
    </div>
  </div>

  <div class="dial-wrap">
    <div class="dial-sweep"></div>
    <svg class="dial-svg" viewBox="0 0 400 400">
      <defs>
        <radialGradient id="dialGlow" cx="50%" cy="50%" r="55%">
          <stop offset="0%" stop-color="#17b8d6" stop-opacity="0.18"/>
          <stop offset="100%" stop-color="#17b8d6" stop-opacity="0"/>
        </radialGradient>
      </defs>

      <circle cx="200" cy="200" r="195" fill="url(#dialGlow)"/>

      <!-- static outer + inner rings -->
      <circle cx="200" cy="200" r="180" fill="none" stroke="#3d5a80" stroke-width="1" opacity="0.5"/>
      <circle cx="200" cy="200" r="108" fill="none" stroke="#3d5a80" stroke-width="1" opacity="0.6"/>

      <!-- rotating segmented HUD rings -->
      <circle class="dial-ring-outer" cx="200" cy="200" r="168" fill="none" stroke="#17b8d6"
        stroke-width="3" opacity="0.85" stroke-dasharray="2,10,22,6,10,14,4,26,8,10"/>
      <circle class="dial-ring-mid" cx="200" cy="200" r="145" fill="none" stroke="#5ef0ff"
        stroke-width="1.6" opacity="0.6" stroke-dasharray="14,6,4,6,20,8"/>

      <!-- clock-style tick marks -->
      <g stroke="#7b93b5" stroke-width="1.4" opacity="0.55">
          <line x1="322.0" y1="200.0" x2="334.0" y2="200.0"/>
          <line x1="320.1" y1="221.2" x2="327.0" y2="222.4"/>
          <line x1="314.6" y1="241.7" x2="321.2" y2="244.1"/>
          <line x1="305.7" y1="261.0" x2="316.0" y2="267.0"/>
          <line x1="293.5" y1="278.4" x2="298.8" y2="282.9"/>
          <line x1="278.4" y1="293.5" x2="282.9" y2="298.8"/>
          <line x1="261.0" y1="305.7" x2="267.0" y2="316.0"/>
          <line x1="241.7" y1="314.6" x2="244.1" y2="321.2"/>
          <line x1="221.2" y1="320.1" x2="222.4" y2="327.0"/>
          <line x1="200.0" y1="322.0" x2="200.0" y2="334.0"/>
          <line x1="178.8" y1="320.1" x2="177.6" y2="327.0"/>
          <line x1="158.3" y1="314.6" x2="155.9" y2="321.2"/>
          <line x1="139.0" y1="305.7" x2="133.0" y2="316.0"/>
          <line x1="121.6" y1="293.5" x2="117.1" y2="298.8"/>
          <line x1="106.5" y1="278.4" x2="101.2" y2="282.9"/>
          <line x1="94.3" y1="261.0" x2="84.0" y2="267.0"/>
          <line x1="85.4" y1="241.7" x2="78.8" y2="244.1"/>
          <line x1="79.9" y1="221.2" x2="73.0" y2="222.4"/>
          <line x1="78.0" y1="200.0" x2="66.0" y2="200.0"/>
          <line x1="79.9" y1="178.8" x2="73.0" y2="177.6"/>
          <line x1="85.4" y1="158.3" x2="78.8" y2="155.9"/>
          <line x1="94.3" y1="139.0" x2="84.0" y2="133.0"/>
          <line x1="106.5" y1="121.6" x2="101.2" y2="117.1"/>
          <line x1="121.6" y1="106.5" x2="117.1" y2="101.2"/>
          <line x1="139.0" y1="94.3" x2="133.0" y2="84.0"/>
          <line x1="158.3" y1="85.4" x2="155.9" y2="78.8"/>
          <line x1="178.8" y1="79.9" x2="177.6" y2="73.0"/>
          <line x1="200.0" y1="78.0" x2="200.0" y2="66.0"/>
          <line x1="221.2" y1="79.9" x2="222.4" y2="73.0"/>
          <line x1="241.7" y1="85.4" x2="244.1" y2="78.8"/>
          <line x1="261.0" y1="94.3" x2="267.0" y2="84.0"/>
          <line x1="278.4" y1="106.5" x2="282.9" y2="101.2"/>
          <line x1="293.5" y1="121.6" x2="298.8" y2="117.1"/>
          <line x1="305.7" y1="139.0" x2="316.0" y2="133.0"/>
          <line x1="314.6" y1="158.3" x2="321.2" y2="155.9"/>
          <line x1="320.1" y1="178.8" x2="327.0" y2="177.6"/>
      </g>

      <!-- center biometric dial -->
      <circle cx="200" cy="200" r="95" fill="#10141b" stroke="#17b8d6" stroke-width="1.4" opacity="0.95"/>
      <circle cx="200" cy="200" r="88" fill="none" stroke="#17b8d6" stroke-width="1" opacity="0.35"/>

      <!-- fingerprint glyph -->
      <g class="fingerprint-group">
          <path d="M181.1 245.5 A41.6 52.0 -4.0 1 1 225.1 242.5" fill="none" stroke="#5ef0ff" stroke-width="2.2" stroke-linecap="round"/>
          <path d="M183.4 240.0 A36.4 45.5 -2.7 1 1 220.0 238.5" fill="none" stroke="#5ef0ff" stroke-width="2.2" stroke-linecap="round"/>
          <path d="M185.7 234.5 A31.2 39.0 -1.4 1 1 215.5 234.1" fill="none" stroke="#5ef0ff" stroke-width="2.2" stroke-linecap="round"/>
          <path d="M188.0 228.8 A26.0 32.5 -0.1 1 1 211.4 229.2" fill="none" stroke="#5ef0ff" stroke-width="2.2" stroke-linecap="round"/>
          <path d="M190.4 223.2 A20.8 26.0 1.2 1 1 208.0 223.9" fill="none" stroke="#5ef0ff" stroke-width="2.2" stroke-linecap="round"/>
          <path d="M192.8 217.4 A15.6 19.5 2.5 1 1 205.0 218.3" fill="none" stroke="#5ef0ff" stroke-width="2.2" stroke-linecap="round"/>
          <path d="M195.2 211.7 A10.4 13.0 3.8 1 1 202.7 212.4" fill="none" stroke="#5ef0ff" stroke-width="2.2" stroke-linecap="round"/>
          <path d="M198.0 208.8 A10.0 7.0 2 1 1 198.5 195.0" fill="none" stroke="#5ef0ff" stroke-width="1.6" stroke-linecap="round" opacity="0.75"/>
          <path d="M197.2 214.0 A6.0 4.0 -3 1 1 194.8 206.4" fill="none" stroke="#5ef0ff" stroke-width="1.6" stroke-linecap="round" opacity="0.75"/>
      </g>

      <text x="200" y="375" text-anchor="middle" class="dial-center-label">VERIFIED ACCESS</text>
    </svg>

    <div class="dial-bracket tl"></div>
    <div class="dial-bracket tr"></div>
    <div class="dial-bracket bl"></div>
    <div class="dial-bracket br"></div>
    <div class="dial-scanline"></div>
  </div>
</section>

<div class="strip">
  <div class="strip-inner">
    <div class="marquee" id="marquee"></div>
  </div>
</div>

<section class="cta reveal">
  <div class="eyebrow" style="justify-content:center">Ready when you are</div>
  <h2>Open your vault</h2>
  <p>Register, enroll your fingerprint, and your files are encrypted from the first upload — with that fingerprint standing guard the moment you try to download or delete one.</p>
  <a href="app.php" class="btn btn-brass btn-lg">Request access →</a>
</section>

<?php include 'partials/footer.php'; ?>

<script>
// ── Decryption scramble effect on headline ──
const finalText = "FILES LOCKED. ";
const finalAccent = "IDENTITY VERIFIED.";
const chars = "!<>-_\\/[]{}—=+*^?#________";
const el = document.getElementById('decryptHeadline');

function scrambleReveal(plain, accentPlain, container){
  const full = plain + accentPlain;
  let frame = 0;
  const totalFrames = 32;

  function render(){
    let out = '';
    for(let i=0;i<full.length;i++){
      const revealFrame = (i / full.length) * totalFrames;
      if(frame >= revealFrame + 8){
        out += full[i];
      } else if(frame >= revealFrame){
        out += chars[Math.floor(Math.random()*chars.length)];
      } else {
        out += ' ';
      }
    }
    const splitPoint = plain.length;
    container.innerHTML = out.slice(0,splitPoint) + '<span class="accent">' + out.slice(splitPoint) + '</span>';
    frame++;
    if(frame < totalFrames + 10){
      requestAnimationFrame(render);
    } else {
      container.innerHTML = plain + '<span class="accent">' + accentPlain + '</span>';
    }
  }
  render();
}
scrambleReveal(finalText, finalAccent, el);

// ── Marquee ledger ──
const events = [
  'LOGIN_SUCCESS · password verified',
  'FILE_UPLOAD · encrypted with AES-256',
  'BIOMETRIC_SUCCESS · fingerprint verified before download',
  'ACCOUNT_LOCKED · 5 failed attempts, buzzer triggered',
  'ROLE_CHANGED · access re-scoped by admin',
  'FILE_DOWNLOAD · ownership + fingerprint verified before decrypt',
  'ACCESS_DENIED · fingerprint mismatch on delete attempt',
];
const mq = document.getElementById('marquee');
const line = events.map(e=>`<span><span class="dot"></span>${e}</span>`).join('');
mq.innerHTML = line + line; // duplicate for seamless loop
</script>

</body>
</html>