<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="The five mechanisms enforcing security inside SecureVault — encryption, password login, fingerprint step-up, lockouts, malware scanning, and physical alerts.">
<link rel="icon" href="data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A//www.w3.org/2000/svg%27%20viewBox%3D%270%200%2034%2034%27%3E%3Ccircle%20cx%3D%2717%27%20cy%3D%2717%27%20r%3D%2716%27%20fill%3D%27%23080a0d%27/%3E%3Ccircle%20cx%3D%2717%27%20cy%3D%2717%27%20r%3D%2715%27%20fill%3D%27none%27%20stroke%3D%27%2317b8d6%27%20stroke-width%3D%271.4%27/%3E%3Cg%20fill%3D%27none%27%20stroke%3D%27%235ef0ff%27%20stroke-linecap%3D%27round%27%3E%3Cpath%20d%3D%27M9.04%2026.36%20A15.13%2018.91%20-4.0%201%201%2025.04%2025.27%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M9.87%2024.36%20A13.24%2016.54%20-2.7%201%201%2023.18%2023.82%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M10.71%2022.36%20A11.35%2014.18%20-1.4%201%201%2021.55%2022.22%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M11.55%2020.29%20A9.45%2011.82%20-0.1%201%201%2020.05%2020.44%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M12.42%2018.25%20A7.56%209.45%201.2%201%201%2018.82%2018.51%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M13.29%2016.15%20A5.67%207.09%202.5%201%201%2017.73%2016.47%27%20stroke-width%3D%270.9%27/%3E%3Cpath%20d%3D%27M14.16%2014.07%20A3.78%204.73%203.8%201%201%2016.89%2014.33%27%20stroke-width%3D%270.9%27/%3E%3Cpath%20d%3D%27M15.18%2013.02%20A3.64%202.55%202%201%201%2015.36%208.00%27%20stroke-width%3D%270.75%27%20opacity%3D%270.8%27/%3E%3Cpath%20d%3D%27M14.89%2014.91%20A2.18%201.45%20-3%201%201%2014.02%2012.15%27%20stroke-width%3D%270.75%27%20opacity%3D%270.8%27/%3E%3C/g%3E%3C/svg%3E">
<title>Mechanism — SecureVault</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="cyber-bg"></div>
<?php include 'partials/network-bg.php'; ?>
<div class="noise"></div>

<?php $active = 'mechanism'; include 'partials/nav.php'; ?>

<div class="page-header reveal">
  <div class="eyebrow">Under The Hood</div>
  <h1>Five Enforced Mechanisms</h1>
  <p>Not a feature list — each one is a check that runs in the live request path, with a log entry to show it happened.</p>
</div>

<div class="mech">
  <div class="mech-inner reveal-stagger" style="padding-top:20px">
    <div class="mech-item"><b>AES-256-CBC</b><span>Per-file encryption, unique IV</span></div>
    <div class="mech-item"><b>Fingerprint step-up</b><span>Required before every download or delete</span></div>
    <div class="mech-item"><b>Admin-cleared lockouts</b><span>No auto-expiring bans</span></div>
    <div class="mech-item"><b>ClamAV scanning</b><span>Every upload checked against 3.6M+ signatures</span></div>
    <div class="mech-item"><b>Physical alert</b><span>Buzzer fires on high-severity events</span></div>
  </div>
</div>

<section class="section">
  <div class="mech-detail-grid reveal-stagger">
    <div class="mech-detail-card obj-card">
      <div class="obj-num">01 — Cipher</div>
      <h3>AES-256-CBC Encryption</h3>
      <p>Every file is encrypted the moment it's uploaded, with its own randomly generated IV so two identical files never produce the same ciphertext. Decryption happens only in memory, only for the verified owner, and the plaintext never touches disk.</p>
    </div>
    <div class="mech-detail-card obj-card">
      <div class="obj-num">02 — Identity</div>
      <h3>Password Login + Fingerprint Step-Up</h3>
      <p>Signing in only ever needs a bcrypt-hashed password — nothing physical is required to reach the dashboard. The AS608 fingerprint sensor comes in later, at the moment you try to download or delete a file: a single-use, 30-second verification grant that expires the instant it's consumed. Something you know gets you into the account; something you are is what actually lets a file leave the vault.</p>
    </div>
    <div class="mech-detail-card obj-card">
      <div class="obj-num">03 — Lockout</div>
      <h3>Admin-Cleared Lockouts</h3>
      <p>Five failed attempts locks the account and raises an intrusion alert. There's deliberately no auto-expiring timer — a locked account stays locked until an administrator reviews and clears it, closing the gap a timed lockout leaves open.</p>
    </div>
    <div class="mech-detail-card obj-card">
      <div class="obj-num">04 — Malware Scan</div>
      <h3>ClamAV Signature Scanning</h3>
      <p>Every upload — vault files and profile pictures alike — is scanned by ClamAV before it's ever encrypted or stored. A detected threat is rejected outright and logged with the exact signature name; if the scanner itself is unreachable, the upload is blocked rather than silently skipped.</p>
    </div>
    <div class="mech-detail-card obj-card">
      <div class="obj-num">05 — Physical Alert</div>
      <h3>Hardware Buzzer</h3>
      <p>High-severity intrusion events — repeated brute-force attempts, a fingerprint mismatch on a download or delete attempt, a confirmed malware upload — don't just sit in a log waiting to be checked. A physical buzzer fires in real time, so a threat gets noticed even away from the dashboard.</p>
    </div>
  </div>
</section>

<?php include 'partials/footer.php'; ?>

</body>
</html>