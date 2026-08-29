<?php
// migrate_add_role.php - Run this in browser to add the 'role' column to existing users table
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Migration: Add role column to users table</h1>";

try {
    // Check if column already exists
    $check = $mysqli->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($check && $check->num_rows > 0) {
        echo "<p style='color:green;'>✅ 'role' column already exists. No migration needed.</p>";
    } else {
        $result = $mysqli->query("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user' AFTER password");
        if ($result) {
            echo "<p style='color:green;'>✅ Successfully added 'role' column to users table!</p>";
        } else {
            echo "<p style='color:red;'>❌ Failed to add column: " . $mysqli->error . "</p>";
        }
    }
    
    // Update existing users to have 'user' role if null
    $mysqli->query("UPDATE users SET role = 'user' WHERE role IS NULL OR role = ''");
    echo "<p style='color:green;'>✅ Updated existing users with default 'user' role.</p>";
    
    echo "<br><a href='login.html'>Go to Login</a> | <a href='register.html'>Go to Register</a>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

