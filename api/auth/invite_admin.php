<?php
header('Content-Type: application/json');
require_once '../../db.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// 1. Verify Admin Token
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
$isAdmin = false;

if (preg_match('/Bearer (?:dummy-token-)?(\d+)/', $authHeader, $matches)) {
    $userId = intval($matches[1]);
    $stmt = $mysqli->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($adminUser = $result->fetch_assoc()) {
        if (strpos($adminUser['email'], 'admin') !== false) {
            $isAdmin = true;
        }
    }
    $stmt->close();
}

if (!$isAdmin) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden: Only administrators can invite new admins.']);
    exit;
}

// 2. Read Request Data
$data = json_decode(file_get_contents('php://input'), true);
$email = isset($data['email']) ? trim($data['email']) : '';

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email address is required.']);
    exit;
}

if (strpos($email, 'admin') === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'The email address must contain "admin" to be recognized as an administrator.']);
    exit;
}

// 3. Check if user already exists
$stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'An account with this email already exists.']);
    exit;
}
$stmt->close();

// 4. Create new admin user
// We create a random temporary password
$tempPassword = bin2hex(random_bytes(4)); // 8 characters
$hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
$name = explode('@', $email)[0]; // Default name to part of email

$stmt = $mysqli->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $hashedPassword);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => "Admin invited successfully. Temporary Password: $tempPassword (Please share securely)"
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error during invite.']);
}

$stmt->close();
$mysqli->close();
