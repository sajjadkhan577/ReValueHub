<?php
require_once '../db.php';
header('Content-Type: application/json');

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
$userId = null;
if (preg_match('/Bearer (?:dummy-token-)?(\d+)/', $authHeader, $matches)) {
    $userId = intval($matches[1]);
}

if (!$userId) {
    http_response_code(401);
    die(json_encode(['message' => 'Unauthorized']));
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $result = $mysqli->query("SELECT * FROM notifications WHERE user_id = $userId ORDER BY created_at DESC LIMIT 20");
    $notifs = [];
    while ($row = $result->fetch_assoc()) {
        $notifs[] = $row;
    }
    echo json_encode($notifs);
} 
elseif ($method === 'POST') {
    // Mark as read
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    $mysqli->query("UPDATE notifications SET is_read = 1 WHERE id = $id AND user_id = $userId");
    echo json_encode(['message' => 'Notification updated']);
}
?>
