<?php
require_once '../../db.php';
header('Content-Type: application/json');

// Admin check (simple)
function isAdmin() {
    // In a real app, verify the token and check user role in DB
    return true; 
}

if (!isAdmin()) {
    http_response_code(403);
    die(json_encode(['message' => 'Forbidden']));
}

$userCount = $mysqli->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$itemCount = $mysqli->query("SELECT COUNT(*) as count FROM items")->fetch_assoc()['count'];
$requestCount = $mysqli->query("SELECT COUNT(*) as count FROM requests")->fetch_assoc()['count'];

echo json_encode([
    'users' => intval($userCount),
    'items' => intval($itemCount),
    'requests' => intval($requestCount)
]);
?>
