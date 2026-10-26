<?php
// Include this with $active set to one of: 'home' | 'objectives' | 'mechanism' | 'faq'
$active = $active ?? '';
function navClass($id, $active){ return $id === $active ? 'active' : ''; }
?>
<nav class="nav">
  <div class="nav-inner">
    <a href="index.php" class="brand">
      <div class="brand-mark">
        <svg viewBox="0 0 34 34">
          <circle cx="17" cy="17" r="16" fill="#080a0d"/>
          <circle cx="17" cy="17" r="15" fill="none" stroke="#17b8d6" stroke-width="1.4"/>
          <g fill="none" stroke="#5ef0ff" stroke-linecap="round">
            <path d="M9.04 26.36 A15.13 18.91 -4.0 1 1 25.04 25.27" stroke-width="1.0"/>
            <path d="M9.87 24.36 A13.24 16.54 -2.7 1 1 23.18 23.82" stroke-width="1.0"/>
            <path d="M10.71 22.36 A11.35 14.18 -1.4 1 1 21.55 22.22" stroke-width="1.0"/>
            <path d="M11.55 20.29 A9.45 11.82 -0.1 1 1 20.05 20.44" stroke-width="1.0"/>
            <path d="M12.42 18.25 A7.56 9.45 1.2 1 1 18.82 18.51" stroke-width="1.0"/>
            <path d="M13.29 16.15 A5.67 7.09 2.5 1 1 17.73 16.47" stroke-width="0.9"/>
            <path d="M14.16 14.07 A3.78 4.73 3.8 1 1 16.89 14.33" stroke-width="0.9"/>
            <path d="M15.18 13.02 A3.64 2.55 2 1 1 15.36 8.00" stroke-width="0.75" opacity="0.8"/>
            <path d="M14.89 14.91 A2.18 1.45 -3 1 1 14.02 12.15" stroke-width="0.75" opacity="0.8"/>
          </g>
        </svg>
      </div>
      SecureVault
    </a>
    <div class="nav-links">
      <a href="index.php" class="<?= navClass('home', $active) ?>">Home</a>
      <a href="objectives.php" class="<?= navClass('objectives', $active) ?>">Objectives</a>
      <a href="mechanism.php" class="<?= navClass('mechanism', $active) ?>">Mechanism</a>
      <a href="faq.php" class="<?= navClass('faq', $active) ?>">FAQ</a>
    </div>
    <div class="nav-cta">
      <a href="app.php" class="btn btn-brass">Sign in</a>
    </div>
  </div>
</nav>