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

if ($method === 'GET') {
    // Check if we want a specific chat thread or the inbox list
    if (isset($_GET['userId'])) {
        $otherId = intval($_GET['userId']);
        
        // 1. Mark received messages from this user as read
        $mysqli->query("UPDATE messages SET is_read = 1 WHERE sender_id = $otherId AND receiver_id = $userId AND is_read = 0");
        
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
                    'created_at' => $row['created_at'],
                    'sender_name' => $row['sender_name'],
                    'sender_avatar' => $row['sender_avatar']
                ];
            }
        }
        echo json_encode($thread);
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
                    $msgRes = $mysqli->query("SELECT message, created_at, sender_id 
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
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        // Fallback for regular POST data
        $input = $_POST;
    }
    
    $receiverId = intval($input['receiver_id'] ?? 0);
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
    
    // Insert into messages table
    $itemVal = $itemId ? $itemId : "NULL";
    $sql = "INSERT INTO messages (sender_id, receiver_id, item_id, message) 
            VALUES ($userId, $receiverId, $itemVal, '$message')";
            
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
