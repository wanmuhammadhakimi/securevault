<?php
// Open self-registration is disabled. Accounts are created by an administrator
// (admin_create_user.php), optionally from a request submitted through
// request_access.php. Kept as a stub so old links/clients get a clear answer.
header('Content-Type: application/json');
http_response_code(410);
echo json_encode([
    'success' => false,
    'message' => 'Self-registration is disabled. Use "Request access" and an administrator will create your account.',
]);
