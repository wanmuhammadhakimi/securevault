<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="The three enforced mechanisms behind SecureVault, and what happens between login and a file leaving the vault.">
<link rel="icon" href="data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A//www.w3.org/2000/svg%27%20viewBox%3D%270%200%2034%2034%27%3E%3Ccircle%20cx%3D%2717%27%20cy%3D%2717%27%20r%3D%2716%27%20fill%3D%27%23080a0d%27/%3E%3Ccircle%20cx%3D%2717%27%20cy%3D%2717%27%20r%3D%2715%27%20fill%3D%27none%27%20stroke%3D%27%2317b8d6%27%20stroke-width%3D%271.4%27/%3E%3Cg%20fill%3D%27none%27%20stroke%3D%27%235ef0ff%27%20stroke-linecap%3D%27round%27%3E%3Cpath%20d%3D%27M9.04%2026.36%20A15.13%2018.91%20-4.0%201%201%2025.04%2025.27%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M9.87%2024.36%20A13.24%2016.54%20-2.7%201%201%2023.18%2023.82%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M10.71%2022.36%20A11.35%2014.18%20-1.4%201%201%2021.55%2022.22%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M11.55%2020.29%20A9.45%2011.82%20-0.1%201%201%2020.05%2020.44%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M12.42%2018.25%20A7.56%209.45%201.2%201%201%2018.82%2018.51%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M13.29%2016.15%20A5.67%207.09%202.5%201%201%2017.73%2016.47%27%20stroke-width%3D%270.9%27/%3E%3Cpath%20d%3D%27M14.16%2014.07%20A3.78%204.73%203.8%201%201%2016.89%2014.33%27%20stroke-width%3D%270.9%27/%3E%3Cpath%20d%3D%27M15.18%2013.02%20A3.64%202.55%202%201%201%2015.36%208.00%27%20stroke-width%3D%270.75%27%20opacity%3D%270.8%27/%3E%3Cpath%20d%3D%27M14.89%2014.91%20A2.18%201.45%20-3%201%201%2014.02%2012.15%27%20stroke-width%3D%270.75%27%20opacity%3D%270.8%27/%3E%3C/g%3E%3C/svg%3E">
<title>Objectives — SecureVault</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="cyber-bg"></div>
<?php include 'partials/network-bg.php'; ?>
<div class="noise"></div>

<?php $active = 'objectives'; include 'partials/nav.php'; ?>

<div class="page-header reveal">
  <div class="eyebrow">System Objectives</div>
  <h1>Three Mechanisms, One Vault</h1>
  <p>Each objective in the project scope maps to an enforced mechanism — not a claim, a working part of the architecture.</p>
</div>

<section class="section" style="padding-top:20px">
  <div class="obj-grid reveal-stagger">
    <div class="obj-card">
      <div class="obj-num">Mechanism 01</div>
      <h3>Cipher Layer</h3>
      <p>Every file is encrypted with AES-256 the instant it's uploaded, and only decrypted in-memory for the verified owner. A stolen disk yields ciphertext, nothing more.</p>
    </div>
    <div class="obj-card">
      <div class="obj-num">Mechanism 02</div>
      <h3>Access Ledger</h3>
      <p>Admin and user roles scope the vault per account. Every login, upload, download, delete, and role change writes a permanent line to the activity log — reviewable from the Access Control dashboard.</p>
    </div>
    <div class="obj-card">
      <div class="obj-num">Mechanism 03</div>
      <h3>Threat Watch</h3>
      <p>Signature-based rules watch for brute-force patterns, fingerprint mismatches, and malware in uploaded files. A crossed threshold locks the account, logs the alert, and sounds a physical buzzer.</p>
    </div>
  </div>
</section>

<div class="flow">
  <div class="flow-inner">
    <div class="section-head reveal" style="margin-bottom:0">
      <div class="eyebrow">Inside a session</div>
      <h2>What happens between login and a file leaving the vault</h2>
      <p>Four checkpoints run in sequence before a single file is decrypted.</p>
    </div>
    <div class="flow-steps reveal-stagger">
      <div class="flow-step">
        <div class="flow-dot">1</div>
        <h4>Password</h4>
        <p>Bcrypt-hashed credentials checked against the account. Five wrong guesses locks it — no timer, an admin has to clear it.</p>
      </div>
      <div class="flow-step">
        <div class="flow-dot">2</div>
        <h4>Role check</h4>
        <p>Admin or user — the dashboard and file list scope themselves to what that role is allowed to touch.</p>
      </div>
      <div class="flow-step">
        <div class="flow-dot">3</div>
        <h4>Fingerprint step-up</h4>
        <p>Triggered only when you try to download or delete a file — a single-use, 30-second grant read from the AS608 sensor. Logging in never requires it.</p>
      </div>
      <div class="flow-step">
        <div class="flow-dot">4</div>
        <h4>Decrypt on demand</h4>
        <p>Files sit encrypted at rest and are only opened in memory once the fingerprint grant clears, then re-sealed.</p>
      </div>
    </div>
  </div>
</div>

<?php include 'partials/footer.php'; ?>

</body>
</html>