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
    $itemFilter = intval($_GET['item'] ?? 0); // Filter by item (used by the item owner)

    if ($itemFilter) {
        // Item owner viewing who requested their item
        $ownerCheck = $mysqli->query("SELECT donor_id FROM items WHERE id = $itemFilter");
        $ownerRow = $ownerCheck ? $ownerCheck->fetch_assoc() : null;
        if (!$ownerRow || !$userId || intval($ownerRow['donor_id']) !== $userId) {
            http_response_code(403);
            die(json_encode(['message' => 'You can only view requests for your own items']));
        }
        $sql = "SELECT r.*, u.name as requester_name, u.avatar as requester_avatar
                FROM requests r
                JOIN users u ON r.requester_id = u.id
                WHERE r.item_id = $itemFilter
                ORDER BY r.created_at DESC";
    } elseif ($userId && $user === $userId) {
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
elseif ($method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true);
    $requestId = intval($input['id'] ?? 0);
    $status = $input['status'] ?? '';
    $allowedStatuses = ['open', 'closed', 'cancelled'];

    if (!$userId || !$requestId || !in_array($status, $allowedStatuses, true)) {
        http_response_code(400);
        die(json_encode(['message' => 'Valid request ID and status are required']));
    }

    $adminResult = $mysqli->query("SELECT role FROM users WHERE id = $userId LIMIT 1");
    $admin = $adminResult ? $adminResult->fetch_assoc() : null;
    if (!$admin || $admin['role'] !== 'admin') {
        http_response_code(403);
        die(json_encode(['message' => 'Administrator access required']));
    }

    $statusE = $mysqli->real_escape_string($status);
    if ($mysqli->query("UPDATE requests SET status = '$statusE' WHERE id = $requestId")) {
        echo json_encode(['message' => 'Request status updated', 'status' => $status]);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Database error: ' . $mysqli->error]);
    }
}
?>
