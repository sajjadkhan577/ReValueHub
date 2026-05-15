<?php
require_once '../db.php';
header('Content-Type: application/json');

try {
    $userCount = $mysqli->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
    $itemCount = $mysqli->query("SELECT COUNT(*) as total FROM items WHERE status = 'approved'")->fetch_assoc()['total'];
    $pendingCount = $mysqli->query("SELECT COUNT(*) as total FROM items WHERE status = 'pending'")->fetch_assoc()['total'];
    $requestCount = $mysqli->query("SELECT COUNT(*) as total FROM requests")->fetch_assoc()['total'];

    echo json_encode([
        'users' => number_format($userCount),
        'items' => number_format($itemCount),
        'items_pending' => number_format($pendingCount),
        'requests' => number_format($requestCount),
        'impact' => number_format($itemCount * 12) . 'kg' 
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
