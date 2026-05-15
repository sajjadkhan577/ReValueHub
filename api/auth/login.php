<?php
require_once '../../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['message' => 'Method not allowed']));
}

$data = json_decode(file_get_contents('php://input'), true);
$email = $mysqli->real_escape_string($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$email || !$password) {
    http_response_code(400);
    die(json_encode(['message' => 'Email and password required']));
}

$result = $mysqli->query("SELECT * FROM users WHERE email = '$email'");
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    echo json_encode([
        'token' => 'dummy-token-' . $user['id'],
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'avatar' => $user['avatar'] ?? null,
            'bio' => $user['bio'] ?? null,
            'role' => (strpos($user['email'], 'admin') !== false) ? 'admin' : 'user'
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode(['message' => 'Invalid email or password']);
}
?>
