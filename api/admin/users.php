<?php
require_once '../../db.php';
header('Content-Type: application/json');

$sql = "SELECT id, name, email, status, joined_at as created_at FROM users ORDER BY joined_at DESC";
$result = $mysqli->query($sql);

$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['role'] = (strpos($row['email'], 'admin') !== false) ? 'admin' : 'user';
        $users[] = $row;
    }
}

echo json_encode($users);
?>
