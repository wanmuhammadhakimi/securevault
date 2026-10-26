<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Answers to what happens on a failed login, an infected upload, a fingerprint mismatch, and where SecureVault actually stores files.">
<link rel="icon" href="data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A//www.w3.org/2000/svg%27%20viewBox%3D%270%200%2034%2034%27%3E%3Ccircle%20cx%3D%2717%27%20cy%3D%2717%27%20r%3D%2716%27%20fill%3D%27%23080a0d%27/%3E%3Ccircle%20cx%3D%2717%27%20cy%3D%2717%27%20r%3D%2715%27%20fill%3D%27none%27%20stroke%3D%27%2317b8d6%27%20stroke-width%3D%271.4%27/%3E%3Cg%20fill%3D%27none%27%20stroke%3D%27%235ef0ff%27%20stroke-linecap%3D%27round%27%3E%3Cpath%20d%3D%27M9.04%2026.36%20A15.13%2018.91%20-4.0%201%201%2025.04%2025.27%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M9.87%2024.36%20A13.24%2016.54%20-2.7%201%201%2023.18%2023.82%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M10.71%2022.36%20A11.35%2014.18%20-1.4%201%201%2021.55%2022.22%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M11.55%2020.29%20A9.45%2011.82%20-0.1%201%201%2020.05%2020.44%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M12.42%2018.25%20A7.56%209.45%201.2%201%201%2018.82%2018.51%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M13.29%2016.15%20A5.67%207.09%202.5%201%201%2017.73%2016.47%27%20stroke-width%3D%270.9%27/%3E%3Cpath%20d%3D%27M14.16%2014.07%20A3.78%204.73%203.8%201%201%2016.89%2014.33%27%20stroke-width%3D%270.9%27/%3E%3Cpath%20d%3D%27M15.18%2013.02%20A3.64%202.55%202%201%201%2015.36%208.00%27%20stroke-width%3D%270.75%27%20opacity%3D%270.8%27/%3E%3Cpath%20d%3D%27M14.89%2014.91%20A2.18%201.45%20-3%201%201%2014.02%2012.15%27%20stroke-width%3D%270.75%27%20opacity%3D%270.8%27/%3E%3C/g%3E%3C/svg%3E">
<title>FAQ — SecureVault</title>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="cyber-bg"></div>
<?php include 'partials/network-bg.php'; ?>
<div class="noise"></div>

<?php $active = 'faq'; include 'partials/nav.php'; ?>

<div class="page-header reveal">
  <div class="eyebrow">Questions</div>
  <h1>Before You Ask</h1>
  <p>The things people check before trusting a vault with real files.</p>
</div>

<section class="section" style="padding-top:20px">
  <div class="faq-list reveal">
    <details class="faq-item" open>
      <summary class="faq-q">What happens if I upload an infected file?<span class="faq-plus">+</span></summary>
      <p class="faq-a">It never reaches storage. Every upload is scanned by ClamAV before encryption — a detected threat is rejected outright, logged to the intrusion log with the signature name, and the file is never written to disk.</p>
    </details>
    <details class="faq-item">
      <summary class="faq-q">Do I need my fingerprint to log in?<span class="faq-plus">+</span></summary>
      <p class="faq-a">No. Logging in only needs your password. Your enrolled fingerprint is checked separately — only at the moment you try to download or delete a file. That verification is a single-use grant that expires after 30 seconds, so even a hijacked session can't walk away with a file without a live scan on the sensor.</p>
    </details>
    <details class="faq-item">
      <summary class="faq-q">What happens after too many failed login attempts?<span class="faq-plus">+</span></summary>
      <p class="faq-a">The account locks after 5 failed attempts and an intrusion alert is raised. There's no auto-expiring timer — an administrator has to explicitly review and unlock it.</p>
    </details>
    <details class="faq-item">
      <summary class="faq-q">What happens if the fingerprint doesn't match?<span class="faq-plus">+</span></summary>
      <p class="faq-a">The download or delete is blocked outright, and the mismatch is logged as an intrusion alert — the same as a failed login attempt, just tied to that specific file action instead.</p>
    </details>
    <details class="faq-item">
      <summary class="faq-q">Where are the files actually stored?<span class="faq-plus">+</span></summary>
      <p class="faq-a">On the same infrastructure that runs the application, fully encrypted — no third-party cloud storage in the path. Each file has its own unique IV, so no two encrypted outputs look alike, even for identical source files.</p>
    </details>
    <details class="faq-item">
      <summary class="faq-q">Can a regular user see what admins see?<span class="faq-plus">+</span></summary>
      <p class="faq-a">No. Role-based access control scopes the dashboard itself — regular users only manage their own files, and only admins see the full intrusion log, access ledger, and account management tools.</p>
    </details>
  </div>
</section>

<section class="cta reveal">
  <div class="eyebrow" style="justify-content:center">Still have questions?</div>
  <h2>See it for yourself</h2>
  <p>Register, enroll your fingerprint, and your files are encrypted from the first upload.</p>
  <a href="app.php" class="btn btn-brass btn-lg">Request access →</a>
</section>

<?php include 'partials/footer.php'; ?>

</body>
</html>