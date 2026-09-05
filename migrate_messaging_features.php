<?php
require_once 'db.php';

header('Content-Type: text/plain; charset=utf-8');

$columns = [
    'message_type' => "VARCHAR(20) NOT NULL DEFAULT 'text'",
    'attachment_url' => 'VARCHAR(255) DEFAULT NULL',
    'attachment_name' => 'VARCHAR(255) DEFAULT NULL',
    'attachment_mime' => 'VARCHAR(100) DEFAULT NULL',
    'delivered_at' => 'DATETIME DEFAULT NULL',
    'read_at' => 'DATETIME DEFAULT NULL',
];

foreach ($columns as $column => $definition) {
    $check = $mysqli->query("SHOW COLUMNS FROM messages LIKE '$column'");
    if (!$check || $check->num_rows === 0) {
        if (!$mysqli->query("ALTER TABLE messages ADD `$column` $definition")) {
            http_response_code(500);
            exit("Failed adding $column: {$mysqli->error}\n");
        }
    }
}

$mysqli->query("UPDATE messages SET delivered_at = created_at WHERE delivered_at IS NULL");
$mysqli->query("UPDATE messages SET read_at = created_at WHERE is_read = 1 AND read_at IS NULL");
$mysqli->query("CREATE TABLE IF NOT EXISTS user_presence (
    user_id INT PRIMARY KEY,
    last_seen DATETIME NOT NULL,
    typing_to INT DEFAULT NULL,
    typing_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB");

echo "Messaging columns ready.\n";
?>
