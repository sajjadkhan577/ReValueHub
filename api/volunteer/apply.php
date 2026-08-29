<?php
// Error handling: catch all PHP errors and return as JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once dirname(__DIR__, 2) . '/db.php';
    header('Content-Type: application/json');

    // Get Authorization Header
    function getAuthHeader() {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) return $_SERVER['HTTP_AUTHORIZATION'];
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) return $headers['Authorization'];
        }
        return '';
    }

    $authHeader = getAuthHeader();
    $userId = null;

    if (preg_match('/Bearer (?:dummy-token-)?(\d+)/', $authHeader, $matches)) {
        $userId = intval($matches[1]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        die(json_encode(['message' => 'Method not allowed']));
    }

    if (!$userId) {
        http_response_code(401);
        die(json_encode(['message' => 'Unauthorized. Please log in to submit a volunteer application.']));
    }

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    
    if (!$data) {
        http_response_code(400);
        die(json_encode(['message' => 'Invalid JSON input']));
    }

    $fullName = $mysqli->real_escape_string(trim($data['full_name'] ?? ''));
    $email = $mysqli->real_escape_string(trim($data['email'] ?? ''));
    $skills = $mysqli->real_escape_string(trim($data['skills'] ?? ''));
    $availability = $mysqli->real_escape_string(trim($data['availability'] ?? 'Flexible'));
    $motivation = $mysqli->real_escape_string(trim($data['motivation'] ?? ''));

    // Validation
    if (!$fullName || !$email) {
        http_response_code(400);
        die(json_encode(['message' => 'Full name and email are required']));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        die(json_encode(['message' => 'Invalid email address']));
    }

    // Check if user already has a pending application
    $checkStmt = $mysqli->prepare("SELECT id, status FROM volunteer_applications WHERE user_id = ? AND status = 'pending'");
    if (!$checkStmt) {
        throw new Exception('Database prepare error (check): ' . $mysqli->error);
    }
    $checkStmt->bind_param("i", $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->fetch_assoc()) {
        http_response_code(409);
        die(json_encode(['message' => 'You already have a pending application. Please wait for review.']));
    }
    $checkStmt->close();

    // Insert the application
    $stmt = $mysqli->prepare("INSERT INTO volunteer_applications (user_id, full_name, email, skills, availability, motivation, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    if (!$stmt) {
        throw new Exception('Database prepare error (insert): ' . $mysqli->error);
    }
    $stmt->bind_param("isssss", $userId, $fullName, $email, $skills, $availability, $motivation);

    if ($stmt->execute()) {
        $applicationId = $mysqli->insert_id;
        
        // Create a notification for all admin users
        $adminNotifMsg = "New volunteer application received from " . $fullName;
        $adminStmt = $mysqli->prepare("INSERT INTO notifications (user_id, message, is_read) SELECT id, ?, 0 FROM users WHERE role = 'admin'");
        if ($adminStmt) {
            $adminStmt->bind_param("s", $adminNotifMsg);
            $adminStmt->execute();
            $adminStmt->close();
        }

        echo json_encode([
            'message' => 'Application submitted successfully! We will review it and get back to you soon.',
            'application' => [
                'id' => $applicationId,
                'status' => 'pending'
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Database error: ' . $mysqli->error]);
    }

    $stmt->close();
    $mysqli->close();
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Server error: ' . $e->getMessage()]);
}
?>

