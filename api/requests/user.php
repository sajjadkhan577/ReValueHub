<?php
require_once '../../db.php';
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

if (!$userId) {
    http_response_code(401);
    die(json_encode(['message' => 'Unauthorized']));
}

$sql = "SELECT r.*, i.title as item_title, i.id as item_id 
        FROM requests r 
        JOIN items i ON r.item_id = i.id 
        WHERE r.requester_id = $userId 
        ORDER BY r.created_at DESC";

$result = $mysqli->query($sql);

$requests = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = [
            'id' => $row['id'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'Item' => [
                'id' => $row['item_id'],
                'title' => $row['item_title']
            ]
        ];
    }
}

echo json_encode($requests);
?>
