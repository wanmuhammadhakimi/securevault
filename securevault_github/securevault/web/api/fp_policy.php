<?php
// SecureVault fingerprint enrolment policy (bank-style).
//
// After an ADMIN changes the fingerprint of an account that already had one
// (e.g. "I hurt my finger, please register a new one"), that account cannot
// download or delete files for this many minutes. If the change was social
// engineering, the real owner has time to notice and report it.
// Production: 720 (12 hours). For a live demo you can set it to 2.
if (!defined('FP_REENROLL_COOLDOWN_MINUTES')) define('FP_REENROLL_COOLDOWN_MINUTES', 720);

/** Returns the cooldown end time (Y-m-d H:i:s) if the account is cooling off, else null. */
function fpCooldownUntil(PDO $pdo, int $userId): ?string {
    $s = $pdo->prepare("SELECT fp_cooldown_until FROM users WHERE user_id = ? AND fp_cooldown_until > NOW()");
    $s->execute([$userId]);
    $v = $s->fetchColumn();
    return $v ?: null;
}

function fpCooldownMessage(string $until): string {
    return 'This account\'s fingerprint was changed recently. For security, downloads and deletes are paused until '
         . date('j M Y, g:i A', strtotime($until)) . '. If you did not request this change, contact your administrator now.';
}
