<?php
// fix_login.php - Run this ONCE in your browser to fix "Invalid login" issues.
// Visit: http://localhost/ReValueHub/fix_login.php
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>ReValueHub Login Fix</h1>";

// 1. Make sure the 'role' column exists
$check = $mysqli->query("SHOW COLUMNS FROM users LIKE 'role'");
if ($check && $check->num_rows > 0) {
    echo "<p>✅ 'role' column already exists.</p>";
} else {
    if ($mysqli->query("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user' AFTER password")) {
        echo "<p>✅ Added missing 'role' column.</p>";
    } else {
        echo "<p style='color:red;'>❌ Could not add 'role' column: " . $mysqli->error . "</p>";
    }
}
$mysqli->query("UPDATE users SET role = 'user' WHERE role IS NULL OR role = ''");

// 2. Make sure a working admin account exists
$adminEmail = 'admin@revalue.com';
$adminPassword = 'admin123';

$stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($existingId);
    $stmt->fetch();
    $stmt->close();
    $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
    $upd = $mysqli->prepare("UPDATE users SET password = ?, role = 'admin' WHERE id = ?");
    $upd->bind_param("si", $hash, $existingId);
    $upd->execute();
    $upd->close();
    echo "<p>✅ Existing account <b>$adminEmail</b> reset to role 'admin' with password <b>$adminPassword</b>.</p>";
} else {
    $stmt->close();
    $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
    $name = 'Admin';
    $ins = $mysqli->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'admin', 'Active')");
    $ins->bind_param("sss", $name, $adminEmail, $hash);
    if ($ins->execute()) {
        echo "<p>✅ Created admin account: <b>$adminEmail</b> / <b>$adminPassword</b>.</p>";
    } else {
        echo "<p style='color:red;'>❌ Could not create admin account: " . $mysqli->error . "</p>";
    }
    $ins->close();
}

// 3. Show how many regular users exist so you know what to log in with
$result = $mysqli->query("SELECT name, email, role, status FROM users ORDER BY id");
echo "<h2>Current users in database</h2><table border='1' cellpadding='6'><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>{$row['name']}</td><td>{$row['email']}</td><td>{$row['role']}</td><td>{$row['status']}</td></tr>";
}
echo "</table>";

echo "<p style='margin-top:20px;color:#b00'>⚠️ Delete this file (fix_login.php) after you're done — it resets a known password.</p>";
echo "<p><a href='admin-login.html'>Go to Admin Login</a> | <a href='login.html'>Go to User Login</a></p>";

$mysqli->close();
