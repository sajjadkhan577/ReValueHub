<?php
require_once 'db.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    die(json_encode(['message' => 'User ID is required']));
}

// Fetch user public info
$userResult = $mysqli->query("SELECT id, name, joined_at, avatar, bio FROM users WHERE id = $id");
if (!$userResult || !($user = $userResult->fetch_assoc())) {
    http_response_code(404);
    die(json_encode(['message' => 'User not found']));
}

// Fetch user's items
$itemsResult = $mysqli->query("SELECT * FROM items WHERE donor_id = $id AND status = 'approved' ORDER BY created_at DESC");
$items = [];
while ($row = $itemsResult->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode([
    'user' => $user,
    'items' => $items
]);
?>
