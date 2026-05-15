<?php
require_once '../db.php';
header('Content-Type: application/json');

// Get Authorization Header
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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Admin or specific user requests
    $id = intval($_GET['id'] ?? 0);
    $user = intval($_GET['user'] ?? 0); // Filter by requester

    if ($userId && $user === $userId) {
        // User's own requests
        $sql = "SELECT r.*, i.title as item_title, i.image_url FROM requests r JOIN items i ON r.item_id = i.id WHERE r.requester_id = $userId";
    } elseif ($userId) {
        // Admin view (if role allows, but for now show all for admin-dashboard logic)
        $sql = "SELECT r.*, i.title as item_title, u.name as requester_name 
                FROM requests r 
                JOIN items i ON r.item_id = i.id 
                JOIN users u ON r.requester_id = u.id 
                ORDER BY r.created_at DESC";
    } else {
        http_response_code(401);
        die(json_encode(['message' => 'Unauthorized']));
    }

    $result = $mysqli->query($sql);
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    echo json_encode($requests);
} 
elseif ($method === 'POST') {
    if (!$userId) {
        http_response_code(401);
        die(json_encode(['message' => 'Unauthorized']));
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $itemId = intval($input['item_id'] ?? 0);

    if (!$itemId) {
        http_response_code(400);
        die(json_encode(['message' => 'Item ID is required']));
    }

    // Check if item exists and get donor
    $itemResult = $mysqli->query("SELECT donor_id FROM items WHERE id = $itemId");
    if (!$itemResult || !$item = $itemResult->fetch_assoc()) {
        http_response_code(404);
        die(json_encode(['message' => 'Item not found']));
    }

    if ($item['donor_id'] == $userId) {
        http_response_code(400);
        die(json_encode(['message' => 'You cannot request your own item']));
    }

    // Check if already requested
    $check = $mysqli->query("SELECT id FROM requests WHERE item_id = $itemId AND requester_id = $userId");
    if ($check && $check->num_rows > 0) {
        http_response_code(400);
        die(json_encode(['message' => 'You have already requested this item']));
    }

    $sql = "INSERT INTO requests (item_id, requester_id, status) VALUES ($itemId, $userId, 'open')";

    if ($mysqli->query($sql)) {
        $requestId = $mysqli->insert_id;
        
        // Notify Donor
        $donorId = $item['donor_id'];
        $requesterResult = $mysqli->query("SELECT name FROM users WHERE id = $userId");
        $requesterName = $requesterResult->fetch_assoc()['name'];
        $itemTitle = $mysqli->query("SELECT title FROM items WHERE id = $itemId")->fetch_assoc()['title'];
        
        $msg = "$requesterName has requested your item: $itemTitle";
        $mysqli->query("INSERT INTO notifications (user_id, message) VALUES ($donorId, '$msg')");

        echo json_encode(['message' => 'Request sent successfully', 'id' => $requestId]);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Database error: ' . $mysqli->error]);
    }
}
?>
