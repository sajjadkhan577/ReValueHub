<?php
require_once '../../db.php';
header('Content-Type: application/json');

// Robust way to get the Authorization header
function getAuthHeader() {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) return $_SERVER['HTTP_AUTHORIZATION'];
    if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) return $headers['Authorization'];
    }
    return '';
}

$authHeader = getAuthHeader();

if (preg_match('/Bearer (?:dummy-token-)?(\d+)/', $authHeader, $matches)) {
    $userId = intval($matches[1]);
    $result = $mysqli->query("SELECT id, name, email, avatar, bio, COALESCE(role, 'user') as role FROM users WHERE id = $userId");
    if ($result && ($user = $result->fetch_assoc())) {
        echo json_encode([
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'avatar' => $user['avatar'] ?? null,
                'bio' => $user['bio'] ?? null,
                'role' => $user['role'] ?? 'user'
            ]
        ]);
        exit;
    }
}

http_response_code(401);
echo json_encode(['message' => 'Unauthorized']);
?>
