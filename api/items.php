<?php
require_once '../db.php';
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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = intval($_GET['id'] ?? 0);
    $category = $_GET['category'] ?? '';
    $search = $_GET['search'] ?? '';
    $limit = intval($_GET['limit'] ?? 50);
    $offset = max(0, intval($_GET['offset'] ?? 0));

    if ($id) {
        $sql = "SELECT i.*, u.name as donor_name, u.avatar as donor_avatar, u.id as donor_id FROM items i JOIN users u ON i.donor_id = u.id WHERE i.id = $id";
        $result = $mysqli->query($sql);
        if ($result && $item = $result->fetch_assoc()) {
            echo json_encode($item);
        } else {
            http_response_code(404);
            echo json_encode(['message' => 'Item not found']);
        }
        exit;
    }

    $status = $_GET['status'] ?? 'approved';
    $featured = isset($_GET['featured']) && $_GET['featured'] === '1';
    $sql = "SELECT i.*, u.name as donor_name, u.avatar as donor_avatar, u.id as donor_id FROM items i JOIN users u ON i.donor_id = u.id WHERE 1=1";
    if ($status !== 'all') {
        $sql .= " AND i.status = '" . $mysqli->real_escape_string($status) . "'";
    }
    if ($category) $sql .= " AND i.category = '" . $mysqli->real_escape_string($category) . "'";
    if ($search) $sql .= " AND (i.title LIKE '%" . $mysqli->real_escape_string($search) . "%' OR i.description LIKE '%" . $mysqli->real_escape_string($search) . "%')";
    if (isset($_GET['count']) && $_GET['count'] === '1') {
        $countResult = $mysqli->query(str_replace(
            "SELECT i.*, u.name as donor_name, u.avatar as donor_avatar, u.id as donor_id",
            "SELECT COUNT(*) AS total",
            $sql
        ));
        $countRow = $countResult ? $countResult->fetch_assoc() : ['total' => 0];
        echo json_encode(['count' => intval($countRow['total'])]);
        exit;
    }
    if ($featured) {
        // Deterministic shuffle that changes every 3 hours, so the homepage
        // spotlight rotates through different listings automatically over the day.
        $seed = intdiv(time(), 10800);
        $sql .= " ORDER BY RAND($seed) LIMIT $limit";
    } else {
        $sql .= " ORDER BY i.created_at DESC LIMIT $limit OFFSET $offset";
    }

    $result = $mysqli->query($sql);
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode($items);
} 
elseif ($method === 'POST') {
    if (!$userId) {
        http_response_code(401);
        die(json_encode(['message' => 'Unauthorized']));
    }

    $title = $mysqli->real_escape_string($_POST['title'] ?? '');
    $category = $mysqli->real_escape_string($_POST['category'] ?? '');
    $description = $mysqli->real_escape_string($_POST['description'] ?? '');
    $location = $mysqli->real_escape_string($_POST['location'] ?? '');
    $condition = $mysqli->real_escape_string($_POST['condition'] ?? '');
    $mfgDate = $_POST['mfgDate'] ?? null;
    $expDate = $_POST['expDate'] ?? null;

    if (!$title || !$category) {
        http_response_code(400);
        die(json_encode(['message' => 'Title and category are required']));
    }

    $imageUrl = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $fileExt;
        $uploadFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $imageUrl = 'uploads/' . $fileName;
        }
    }

    $mfgVal = $mfgDate ? "'$mfgDate'" : "NULL";
    $expVal = $expDate ? "'$expDate'" : "NULL";

    $sql = "INSERT INTO items (title, category, description, location, `condition`, image_url, donor_id, status) 
            VALUES ('$title', '$category', '$description', '$location', '$condition', '$imageUrl', $userId, 'pending')";

    if ($mysqli->query($sql)) {
        echo json_encode(['message' => 'Item listed successfully', 'id' => $mysqli->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Database error: ' . $mysqli->error]);
    }
}
elseif ($method === 'PUT') {
    if (!$userId) {
        http_response_code(401);
        die(json_encode(['message' => 'Unauthorized']));
    }

    $itemId = intval($_POST['id'] ?? 0);
    if (!$itemId) {
        http_response_code(400);
        die(json_encode(['message' => 'Item ID is required']));
    }

    // Verify user owns the item
    $checkSql = "SELECT donor_id FROM items WHERE id = $itemId";
    $checkResult = $mysqli->query($checkSql);
    if ($checkResult && $item = $checkResult->fetch_assoc()) {
        if ($item['donor_id'] != $userId) {
            http_response_code(403);
            die(json_encode(['message' => 'You can only edit your own items']));
        }
    } else {
        http_response_code(404);
        die(json_encode(['message' => 'Item not found']));
    }

    $title = $mysqli->real_escape_string($_POST['title'] ?? '');
    $category = $mysqli->real_escape_string($_POST['category'] ?? '');
    $description = $mysqli->real_escape_string($_POST['description'] ?? '');
    $location = $mysqli->real_escape_string($_POST['location'] ?? '');
    $condition = $mysqli->real_escape_string($_POST['condition'] ?? '');
    $mfgDate = $_POST['mfgDate'] ?? null;
    $expDate = $_POST['expDate'] ?? null;

    if (!$title || !$category) {
        http_response_code(400);
        die(json_encode(['message' => 'Title and category are required']));
    }

    $updateSql = "UPDATE items SET title = '$title', category = '$category', description = '$description', location = '$location', `condition` = '$condition'";
    
    if ($mfgDate) $updateSql .= ", mfgDate = '$mfgDate'";
    if ($expDate) $updateSql .= ", expDate = '$expDate'";
    
    // Handle image upload if provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $fileExt;
        $uploadFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
            $imageUrl = 'uploads/' . $fileName;
            $updateSql .= ", image_url = '$imageUrl'";
        }
    }
    
    $updateSql .= " WHERE id = $itemId AND donor_id = $userId";

    if ($mysqli->query($updateSql)) {
        echo json_encode(['message' => 'Item updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Database error: ' . $mysqli->error]);
    }
}
?>
