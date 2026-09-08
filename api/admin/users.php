<?php
require_once '../../db.php';
header('Content-Type: application/json; charset=utf-8');

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
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

$userStmt = $mysqli->prepare("SELECT id, role FROM users WHERE id = ? LIMIT 1");
if (!$userStmt) {
    http_response_code(500);
    echo json_encode(['message' => 'Database error']);
    exit;
}

$userStmt->bind_param('i', $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

if (!$user || ($user['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Forbidden']);
    exit;
}

$sql = "SELECT id, name, email, role, status, avatar, bio, joined_at as created_at FROM users ORDER BY joined_at DESC";
$result = $mysqli->query($sql);

$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

echo json_encode($users);
?>
