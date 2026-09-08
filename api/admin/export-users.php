<?php
require_once '../../db.php';

function getAuthHeader() {
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) return $_SERVER['HTTP_AUTHORIZATION'];
    if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) return $headers['Authorization'];
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['message' => 'Method not allowed']);
    exit;
}

$authHeader = getAuthHeader();
$userId = null;
if (preg_match('/Bearer (?:dummy-token-)?(\d+)/', $authHeader, $matches)) {
    $userId = intval($matches[1]);
}

if (!$userId) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

$userStmt = $mysqli->prepare("SELECT id, role FROM users WHERE id = ? LIMIT 1");
if (!$userStmt) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['message' => 'Database error']);
    exit;
}

$userStmt->bind_param('i', $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

if (!$user || ($user['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['message' => 'Forbidden']);
    exit;
}

$sql = "SELECT id, name, email, role, status, bio, joined_at FROM users ORDER BY id ASC";
$result = $mysqli->query($sql);

if (!$result) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['message' => 'Unable to export users']);
    exit;
}

$filename = 'revaluehub_users_' . date('Y-m-d') . '.csv';
$csvHeaders = ['ID', 'Name', 'Email', 'Role', 'Status', 'Bio', 'Joined Date'];

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$stdout = fopen('php://output', 'w');
if (!$stdout) {
    http_response_code(500);
    echo 'Export failed.';
    exit;
}

fputs($stdout, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($stdout, $csvHeaders, ',', '"');

while ($row = $result->fetch_assoc()) {
    $bio = $row['bio'] ?? '';
    $joinedAt = $row['joined_at'] ?? '';
    fputcsv($stdout, [
        (int) $row['id'],
        $row['name'] ?? '',
        $row['email'] ?? '',
        $row['role'] ?? '',
        $row['status'] ?? '',
        $bio,
        $joinedAt,
    ], ',', '"');
}

fclose($stdout);
exit;
