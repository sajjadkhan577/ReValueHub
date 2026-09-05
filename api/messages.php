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

if (!$userId) {
    http_response_code(401);
    die(json_encode(['message' => 'Unauthorized']));
}

$method = $_SERVER['REQUEST_METHOD'];

$mysqli->query("INSERT INTO user_presence (user_id, last_seen) VALUES ($userId, NOW()) ON DUPLICATE KEY UPDATE last_seen = NOW()");

function isMessageParticipant($mysqli, $userId, $otherId) {
    $result = $mysqli->query("SELECT id FROM messages WHERE (sender_id = $userId AND receiver_id = $otherId) OR (sender_id = $otherId AND receiver_id = $userId) LIMIT 1");
    return $result && $result->num_rows > 0;
}

if ($method === 'GET') {
    if (isset($_GET['search'])) {
        $search = $mysqli->real_escape_string(trim($_GET['search']));
        $result = $mysqli->query("SELECT id, name, avatar FROM users WHERE id != $userId AND role = 'user' AND status = 'Active' AND (name LIKE '%$search%' OR email LIKE '%$search%') ORDER BY name LIMIT 20");
        $users = [];
        while ($result && ($row = $result->fetch_assoc())) $users[] = $row;
        echo json_encode($users);
        exit;
    }
    // Check if we want a specific chat thread or the inbox list
    if (isset($_GET['userId'])) {
        $otherId = intval($_GET['userId']);

        $otherUser = $mysqli->query("SELECT id FROM users WHERE id = $otherId AND status = 'Active' LIMIT 1");
        if ($otherId <= 0 || $otherId === $userId || !$otherUser || !$otherUser->num_rows) {
            http_response_code(403);
            die(json_encode(['message' => 'Conversation access denied']));
        }
        
        // 1. Mark received messages from this user as read
        $mysqli->query("UPDATE messages SET is_read = 1, read_at = NOW() WHERE sender_id = $otherId AND receiver_id = $userId AND is_read = 0");
        
        // 2. Fetch the message thread
        $sql = "SELECT m.*, 
                       s.name as sender_name, s.avatar as sender_avatar, 
                       r.name as receiver_name, r.avatar as receiver_avatar 
                FROM messages m 
                JOIN users s ON m.sender_id = s.id 
                JOIN users r ON m.receiver_id = r.id 
                WHERE (m.sender_id = $userId AND m.receiver_id = $otherId) 
                   OR (m.sender_id = $otherId AND m.receiver_id = $userId) 
                ORDER BY m.created_at ASC";
                
        $result = $mysqli->query($sql);
        $thread = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $thread[] = [
                    'id' => intval($row['id']),
                    'sender_id' => intval($row['sender_id']),
                    'receiver_id' => intval($row['receiver_id']),
                    'item_id' => $row['item_id'] ? intval($row['item_id']) : null,
                    'message' => $row['message'],
                    'is_read' => intval($row['is_read']) === 1,
                    'delivered_at' => $row['delivered_at'] ?? null,
                    'read_at' => $row['read_at'] ?? null,
                    'message_type' => $row['message_type'] ?? 'text',
                    'attachment_url' => $row['attachment_url'] ?? null,
                    'attachment_name' => $row['attachment_name'] ?? null,
                    'created_at' => $row['created_at'],
                    'sender_name' => $row['sender_name'],
                    'sender_avatar' => $row['sender_avatar']
                ];
            }
        }
        $presenceResult = $mysqli->query("SELECT last_seen, typing_to, typing_at FROM user_presence WHERE user_id = $otherId LIMIT 1");
        $presence = $presenceResult ? $presenceResult->fetch_assoc() : null;
        echo json_encode([
            'messages' => $thread,
            'presence' => $presence ? [
                'online' => strtotime($presence['last_seen']) >= time() - 20,
                'typing' => intval($presence['typing_to']) === $userId && strtotime($presence['typing_at']) >= time() - 5,
                'last_seen' => $presence['last_seen']
            ] : ['online' => false, 'typing' => false]
        ]);
        exit;
    } else {
        // Fetch Inbox/Conversations list
        $sql = "SELECT DISTINCT 
                    CASE 
                        WHEN sender_id = $userId THEN receiver_id 
                        ELSE sender_id 
                    END as partner_id
                FROM messages 
                WHERE sender_id = $userId OR receiver_id = $userId";
                
        $result = $mysqli->query($sql);
        $conversations = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $partnerId = intval($row['partner_id']);
                
                // Get partner profile details
                $partnerRes = $mysqli->query("SELECT id, name, avatar FROM users WHERE id = $partnerId");
                if ($partnerRes && $partner = $partnerRes->fetch_assoc()) {
                    // Fetch latest message
                    $msgRes = $mysqli->query("SELECT message, created_at, sender_id, is_read
                                              FROM messages 
                                              WHERE (sender_id = $userId AND receiver_id = $partnerId) 
                                                 OR (sender_id = $partnerId AND receiver_id = $userId) 
                                              ORDER BY created_at DESC LIMIT 1");
                    $latestMsg = $msgRes ? $msgRes->fetch_assoc() : null;
                    
                    // Fetch unread count from this partner
                    $unreadRes = $mysqli->query("SELECT COUNT(*) as unread_count 
                                                 FROM messages 
                                                 WHERE sender_id = $partnerId AND receiver_id = $userId AND is_read = 0");
                    $unreadCount = $unreadRes ? intval($unreadRes->fetch_assoc()['unread_count']) : 0;
                    
                    $conversations[] = [
                        'partner' => [
                            'id' => $partnerId,
                            'name' => $partner['name'],
                            'avatar' => $partner['avatar']
                        ],
                        'latest_message' => $latestMsg ? $latestMsg['message'] : '',
                        'latest_sender_id' => $latestMsg ? intval($latestMsg['sender_id']) : 0,
                        'latest_is_read' => $latestMsg ? intval($latestMsg['is_read']) === 1 : false,
                        'created_at' => $latestMsg ? $latestMsg['created_at'] : '',
                        'unread_count' => $unreadCount
                    ];
                }
            }
        }
        
        // Sort conversations by latest message timestamp DESC
        usort($conversations, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        echo json_encode($conversations);
        exit;
    }
} 
elseif ($method === 'POST') {
    $input = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
    if (!$input) {
        // Fallback for regular POST data
        $input = $_POST;
    }
    
    $receiverId = intval($input['receiver_id'] ?? 0);

    if (($input['action'] ?? '') === 'typing') {
        $recipient = $mysqli->query("SELECT id FROM users WHERE id = $receiverId AND status = 'Active' LIMIT 1");
        if (!$receiverId || $receiverId === $userId || !$recipient || !$recipient->num_rows) {
            http_response_code(403);
            die(json_encode(['message' => 'Conversation access denied']));
        }
        $typingTo = intval($input['typing'] ?? 0) === 1 ? $receiverId : 'NULL';
        $mysqli->query("UPDATE user_presence SET typing_to = $typingTo, typing_at = NOW(), last_seen = NOW() WHERE user_id = $userId");
        echo json_encode(['message' => 'Presence updated']);
        exit;
    }

    $message = $mysqli->real_escape_string(trim($input['message'] ?? ''));
    $itemId = isset($input['item_id']) && $input['item_id'] ? intval($input['item_id']) : null;
    
    if (!$receiverId || $message === '') {
        http_response_code(400);
        die(json_encode(['message' => 'Receiver ID and non-empty message are required']));
    }
    
    if ($receiverId === $userId) {
        http_response_code(400);
        die(json_encode(['message' => 'You cannot send a message to yourself']));
    }

    $recipient = $mysqli->query("SELECT id FROM users WHERE id = $receiverId AND status = 'Active' LIMIT 1");
    if (!$recipient || !$recipient->num_rows) {
        http_response_code(403);
        die(json_encode(['message' => 'Recipient not found']));
    }

    $attachmentUrl = null;
    $attachmentName = null;
    $attachmentMime = null;
    $messageType = 'text';
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['attachment']['size'] > 10 * 1024 * 1024) {
            http_response_code(400);
            die(json_encode(['message' => 'Attachments must be 10MB or smaller']));
        }
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        $mime = mime_content_type($_FILES['attachment']['tmp_name']);
        if (!in_array($mime, $allowedMimes, true)) {
            http_response_code(400);
            die(json_encode(['message' => 'Only images and PDF files are supported']));
        }
        $directory = __DIR__ . '/../uploads/messages/';
        if (!is_dir($directory)) mkdir($directory, 0777, true);
        $extension = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        $fileName = bin2hex(random_bytes(12)) . '.' . $extension;
        if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $directory . $fileName)) {
            http_response_code(500);
            die(json_encode(['message' => 'Could not save attachment']));
        }
        $attachmentUrl = 'uploads/messages/' . $fileName;
        $attachmentName = basename($_FILES['attachment']['name']);
        $attachmentMime = $mime;
        $messageType = strpos($mime, 'image/') === 0 ? 'image' : 'file';
    }
    
    // Insert into messages table
    $itemVal = $itemId ? $itemId : "NULL";
        $attachmentUrlSql = $attachmentUrl ? "'" . $mysqli->real_escape_string($attachmentUrl) . "'" : 'NULL';
        $attachmentNameSql = $attachmentName ? "'" . $mysqli->real_escape_string($attachmentName) . "'" : 'NULL';
        $attachmentMimeSql = $attachmentMime ? "'" . $mysqli->real_escape_string($attachmentMime) . "'" : 'NULL';
        $typeSql = $mysqli->real_escape_string($messageType);
        $sql = "INSERT INTO messages (sender_id, receiver_id, item_id, message, message_type, attachment_url, attachment_name, attachment_mime, delivered_at)
            VALUES ($userId, $receiverId, $itemVal, '$message', '$typeSql', $attachmentUrlSql, $attachmentNameSql, $attachmentMimeSql, NOW())";
            
    if ($mysqli->query($sql)) {
        $msgId = $mysqli->insert_id;
        
        // Get sender name for the notification
        $senderRes = $mysqli->query("SELECT name FROM users WHERE id = $userId");
        $senderName = 'Someone';
        if ($senderRes && $row = $senderRes->fetch_assoc()) {
            $senderName = $row['name'];
        }
        
        // Create truncated message preview
        $preview = mb_strimwidth(strip_tags($input['message']), 0, 45, '...');
        $notificationMsg = "New message from $senderName: \"$preview\"";
        
        // Insert notification for the receiver
        $escapedNotificationMsg = $mysqli->real_escape_string($notificationMsg);
        $mysqli->query("INSERT INTO notifications (user_id, message) VALUES ($receiverId, '$escapedNotificationMsg')");
        
        echo json_encode([
            'message' => 'Message sent successfully',
            'data' => [
                'id' => $msgId,
                'sender_id' => $userId,
                'receiver_id' => $receiverId,
                'item_id' => $itemId,
                'message' => $input['message'],
                'created_at' => date('Y-m-d H:i:s'),
                'is_read' => false
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['message' => 'Database error: ' . $mysqli->error]);
    }
}
?>
