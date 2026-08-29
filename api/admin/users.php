<?php
require_once '../../db.php';
header('Content-Type: application/json');

$sql = "SELECT id, name, email, role, status, joined_at as created_at FROM users ORDER BY joined_at DESC";
$result = $mysqli->query($sql);

$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

echo json_encode($users);
?>
