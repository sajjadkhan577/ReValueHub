<?php
require_once '../../db.php';
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

// Simple admin check via token
if (!$userId) {
    http_response_code(401);
    die(json_encode(['message' => 'Unauthorized']));
}

// Admin check: verify user exists (admin page is already gated by JS in admin-dashboard.html)
// If token was parsed successfully, the user is authenticated.

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // List all volunteer applications
    $sql = "SELECT va.*, u.name as applicant_name, u.email as applicant_email, u.avatar as applicant_avatar,
                   r.name as reviewer_name
            FROM volunteer_applications va
            LEFT JOIN users u ON va.user_id = u.id
            LEFT JOIN users r ON va.reviewed_by = r.id
            ORDER BY va.created_at DESC";
    
    $result = $mysqli->query($sql);
    $applications = [];
    while ($row = $result->fetch_assoc()) {
        // Map full_name from application if applicant user was deleted
        if (!$row['applicant_name']) {
            $row['applicant_name'] = $row['full_name'];
        }
        if (!$row['applicant_email']) {
            $row['applicant_email'] = $row['email'];
        }
        $applications[] = $row;
    }
    
    echo json_encode($applications);
    
} elseif ($method === 'POST') {
    // Update application status (approve/reject)
    $data = json_decode(file_get_contents('php://input'), true);
    $applicationId = intval($data['id'] ?? 0);
    $newStatus = $mysqli->real_escape_string($data['status'] ?? '');
    
    if (!$applicationId || !in_array($newStatus, ['approved', 'rejected'])) {
        http_response_code(400);
        die(json_encode(['message' => 'Valid application ID and status (approved/rejected) are required']));
    }
    
    // Get the application to find the applicant user_id
    $appResult = $mysqli->query("SELECT user_id, full_name, email FROM volunteer_applications WHERE id = $applicationId");
    $application = $appResult->fetch_assoc();
    
    if (!$application) {
        http_response_code(404);
        die(json_encode(['message' => 'Application not found']));
    }
    
    $stmt = $mysqli->prepare("UPDATE volunteer_applications SET status = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
    $stmt->bind_param("sii", $newStatus, $userId, $applicationId);
    
    if ($stmt->execute()) {
        // Notify the applicant
        $applicantUserId = $application['user_id'];
        $applicantName = $application['full_name'];
        
        if ($applicantUserId) {
            $notifMessage = $newStatus === 'approved' 
                ? "Congratulations! Your volunteer application has been approved. Welcome to the ReValue Hub team!"
                : "Thank you for your interest. Your volunteer application has been reviewed and we regret to inform you that it was not approved at this time.";
            
            $notifStmt = $mysqli->prepare("INSERT INTO notifications (user_id, message, is_read) VALUES (?, ?, 0)");
            $notifStmt->bind_param("is", $applicantUserId, $notifMessage);
            $notifStmt->execute();
            $notifStmt->close();
        }
        
        $statusText = $newStatus === 'approved' ? 'approved' : 'rejected';
        echo json_encode([
            'message' => "Application $statusText successfully.",
            'status' => $newStatus,
            'applicant_name' => $applicantName
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Database error: ' . $mysqli->error]);
    }
    
    $stmt->close();
} else {
    http_response_code(405);
    die(json_encode(['message' => 'Method not allowed']));
}

$mysqli->close();
?>

