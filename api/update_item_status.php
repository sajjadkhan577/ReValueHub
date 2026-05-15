<?php
require_once '../db.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
file_put_contents('debug_api.log', date('Y-m-d H:i:s') . ' - Input: ' . file_get_contents('php://input') . "\n", FILE_APPEND);
$id = intval($input['id'] ?? 0);
$status = $input['status'] ?? '';
$action = $input['action'] ?? ''; // 'edit', 'delete', or empty for status update

if (!$id) {
    http_response_code(400);
    die(json_encode(['message' => 'ID is required']));
}

try {
    if ($action === 'delete') {
        $stmt = $mysqli->prepare("DELETE FROM items WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            echo json_encode(['message' => 'Item deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Item not found or already deleted']);
        }
    } 
    elseif ($action === 'edit') {
        $desc = $input['description'] ?? '';
        $loc = $input['location'] ?? '';
        $stmt = $mysqli->prepare("UPDATE items SET description = ?, location = ? WHERE id = ?");
        $stmt->bind_param("ssi", $desc, $loc, $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            echo json_encode(['message' => 'Item updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Item not found or no changes made']);
        }
    }
    else {
        // Status update (approve/reject)
        $stmt = $mysqli->prepare("UPDATE items SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            echo json_encode(['message' => 'Item status updated to ' . $status]);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Item not found or status already set']);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
}
?>
