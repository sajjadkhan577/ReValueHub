<?php
// db.php – Expert connection script
$configs = [
    ['host' => 'localhost', 'user' => 'revalue_user', 'pass' => 'secure_password', 'db' => 'revalue_hub'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'db' => 'revalue_hub']
];

$mysqli = null;
foreach ($configs as $c) {
    try {
        $mysqli = @new mysqli($c['host'], $c['user'], $c['pass'], $c['db']);
        if (!$mysqli->connect_error) {
            break;
        }
    } catch (mysqli_sql_exception $e) {
        $mysqli = null;
        continue;
    }
}

if (!$mysqli || $mysqli->connect_error) {
    header('Content-Type: application/json');
    http_response_code(500);
    die(json_encode([
        'error' => 'Database connection failed.',
        'details' => $mysqli ? $mysqli->connect_error : 'No configuration worked'
    ]));
}

$mysqli->set_charset('utf8mb4');
?>
