<?php
require_once '../../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['message' => 'Method not allowed']));
}

$data = json_decode(file_get_contents('php://input'), true);

$name = $mysqli->real_escape_string($data['name'] ?? '');
$email = $mysqli->real_escape_string($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$name || !$email || !$password) {
    http_response_code(400);
    die(json_encode(['message' => 'All fields are required']));
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $mysqli->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hashedPassword);
    
    if ($stmt->execute()) {
        $userId = $mysqli->insert_id;
        
        // Start a simple session or return a token (using a dummy token for this project structure)
        echo json_encode([
            'message' => 'Registration successful',
            'token' => 'dummy-token-' . $userId,
            'user' => [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'avatar' => null,
                'bio' => null,
                'role' => 'user'
            ]
        ]);
    } else {
        throw new Exception($mysqli->error);
    }
} catch (Exception $e) {
    http_response_code(400);
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo json_encode(['message' => 'Email already exists']);
    } else {
        echo json_encode(['message' => 'Registration failed: ' . $e->getMessage()]);
    }
}
?>
