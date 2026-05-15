<?php
require_once '../db.php';
header('Content-Type: application/json');

try {
    $sql = "SELECT i.*, u.name as donor_name 
            FROM items i 
            JOIN users u ON i.donor_id = u.id 
            WHERE i.status = 'pending' 
            ORDER BY i.created_at DESC 
            LIMIT 10";
    
    $result = $mysqli->query($sql);
    $items = [];
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    echo json_encode($items);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
