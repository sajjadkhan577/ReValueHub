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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['message' => 'Method not allowed']));
}

if (!$userId) {
    http_response_code(401);
    die(json_encode(['message' => 'Unauthorized']));
}

$input = json_decode(file_get_contents('php://input'), true);
$itemId = intval($input['item_id'] ?? 0);
$requestId = intval($input['request_id'] ?? 0);

if (!$itemId || !$requestId) {
    http_response_code(400);
    die(json_encode(['message' => 'item_id and request_id are required']));
}

// Verify the caller owns this item
$itemStmt = $mysqli->prepare("SELECT donor_id, title, status FROM items WHERE id = ?");
$itemStmt->bind_param("i", $itemId);
$itemStmt->execute();
$item = $itemStmt->get_result()->fetch_assoc();
$itemStmt->close();

if (!$item) {
    http_response_code(404);
    die(json_encode(['message' => 'Item not found']));
}
if (intval($item['donor_id']) !== $userId) {
    http_response_code(403);
    die(json_encode(['message' => 'You can only complete donations for your own items']));
}
if ($item['status'] === 'donated') {
    http_response_code(409);
    die(json_encode(['message' => 'This item has already been marked as donated']));
}

// Verify the request belongs to this item and is still open
$reqStmt = $mysqli->prepare("SELECT requester_id, status FROM requests WHERE id = ? AND item_id = ?");
$reqStmt->bind_param("ii", $requestId, $itemId);
$reqStmt->execute();
$request = $reqStmt->get_result()->fetch_assoc();
$reqStmt->close();

if (!$request) {
    http_response_code(404);
    die(json_encode(['message' => 'Request not found for this item']));
}

$recipientId = intval($request['requester_id']);

$mysqli->begin_transaction();
$ok = true;

// Mark item as donated so it disappears from the live feed (browse defaults to status=approved)
$stmt = $mysqli->prepare("UPDATE items SET status = 'donated' WHERE id = ?");
$stmt->bind_param("i", $itemId);
$ok = $ok && $stmt->execute();
$stmt->close();

// Close the accepted request, cancel any other open requests for the same item
if ($ok) {
    $stmt = $mysqli->prepare("UPDATE requests SET status = 'closed' WHERE id = ?");
    $stmt->bind_param("i", $requestId);
    $ok = $stmt->execute();
    $stmt->close();
}

if ($ok) {
    $stmt = $mysqli->prepare("UPDATE requests SET status = 'cancelled' WHERE item_id = ? AND id != ? AND status = 'open'");
    $stmt->bind_param("ii", $itemId, $requestId);
    $ok = $stmt->execute();
    $stmt->close();
}

// Record the completed donation
if ($ok) {
    $stmt = $mysqli->prepare("INSERT INTO donations (item_id, request_id, donor_id, recipient_id, item_title) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiis", $itemId, $requestId, $userId, $recipientId, $item['title']);
    $ok = $stmt->execute();
    $stmt->close();
}

if ($ok) {
    $mysqli->commit();

    // Notify the recipient (best-effort; do not fail the whole request if this fails)
    $msg = "Great news! The donation of \"" . $item['title'] . "\" has been marked as complete.";
    $notifStmt = $mysqli->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $notifStmt->bind_param("is", $recipientId, $msg);
    $notifStmt->execute();
    $notifStmt->close();

    echo json_encode(['message' => 'Donation marked as complete']);
} else {
    $mysqli->rollback();
    http_response_code(500);
    echo json_encode(['message' => 'Database error: ' . $mysqli->error]);
}
