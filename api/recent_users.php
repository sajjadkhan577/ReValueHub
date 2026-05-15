<?php
require_once '../db.php';
header('Content-Type: application/json');

try {
    $result = $mysqli->query("SELECT id, name, status, joined_at FROM users ORDER BY joined_at DESC LIMIT 5");
    $users = [];
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    echo json_encode($users);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
