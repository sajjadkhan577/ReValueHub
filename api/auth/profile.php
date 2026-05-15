<?php
require_once '../../db.php';
header('Content-Type: application/json');

function getAuthHeader() {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) return $_SERVER['HTTP_AUTHORIZATION'];
    if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'PUT') {
    // PHP doesn't populate $_POST for PUT requests with multipart/form-data easily.
    // However, since we're using FormData, we might be sending it as POST with a method override or just POST.
    // Let's check if it's actually a POST request from the JS side.
    // In js/app.js: apiRequest('auth/profile', { method: 'PUT', body: payload })
    
    // For simplicity with multipart/form-data (avatars), it's easier to use POST.
    // I'll check if the method is POST or PUT.
}

// Actually, let's use POST for profile updates to handle files easily.
// I'll update js/app.js to use POST for profile updates.

// For now, let's assume it's POST and handle it.
$name = $mysqli->real_escape_string($_POST['name'] ?? '');
$email = $mysqli->real_escape_string($_POST['email'] ?? '');
$bio = $mysqli->real_escape_string($_POST['bio'] ?? '');

$avatarUrl = '';
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    
    $fileExt = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $fileName = 'avatar_' . $userId . '_' . time() . '.' . $fileExt;
    $uploadFile = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadFile)) {
        $avatarUrl = 'uploads/' . $fileName;
    }
}

$sql = "UPDATE users SET name = '$name', email = '$email', bio = '$bio'";
if ($avatarUrl) {
    $sql .= ", avatar = '$avatarUrl'";
}
$sql .= " WHERE id = $userId";

if ($mysqli->query($sql)) {
    $result = $mysqli->query("SELECT id, name, email, avatar, bio FROM users WHERE id = $userId");
    $user = $result->fetch_assoc();
    echo json_encode([
        'message' => 'Profile updated successfully',
        'user' => $user
    ]);
} else {
    http_response_code(500);
    echo json_encode(['message' => 'Database error: ' . $mysqli->error]);
}
?>
