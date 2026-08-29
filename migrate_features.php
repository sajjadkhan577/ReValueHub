<?php
// migrate_features.php - Run ONCE in your browser to add the new tables/columns
// this update needs, without touching your existing data.
// Visit: http://localhost/ReValueHub/migrate_features.php
require_once 'db.php';
header('Content-Type: text/html; charset=utf-8');
echo "<h1>ReValueHub Feature Migration</h1><ul>";

// 1. volunteer_applications table (fixes "Network error loading applications" in admin panel)
$check = $mysqli->query("SHOW TABLES LIKE 'volunteer_applications'");
if ($check && $check->num_rows > 0) {
    echo "<li>✅ 'volunteer_applications' table already exists.</li>";
} else {
    $sql = "CREATE TABLE volunteer_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        full_name VARCHAR(150) NOT NULL,
        email VARCHAR(150) NOT NULL,
        skills TEXT,
        availability VARCHAR(100) DEFAULT 'Flexible',
        motivation TEXT,
        status ENUM('pending','approved','rejected') DEFAULT 'pending',
        reviewed_by INT DEFAULT NULL,
        reviewed_at DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql)) {
        echo "<li>✅ Created 'volunteer_applications' table. This fixes the admin 'Network error loading applications' bug.</li>";
    } else {
        echo "<li style='color:red;'>❌ Could not create 'volunteer_applications': " . $mysqli->error . "</li>";
    }
}

// 2. donations table (completed hand-offs, removed from the live feed)
$check = $mysqli->query("SHOW TABLES LIKE 'donations'");
if ($check && $check->num_rows > 0) {
    echo "<li>✅ 'donations' table already exists.</li>";
} else {
    $sql = "CREATE TABLE donations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_id INT NOT NULL,
        request_id INT DEFAULT NULL,
        donor_id INT NOT NULL,
        recipient_id INT NOT NULL,
        item_title VARCHAR(200) NOT NULL,
        completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
        FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE SET NULL,
        FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql)) {
        echo "<li>✅ Created 'donations' table.</li>";
    } else {
        echo "<li style='color:red;'>❌ Could not create 'donations': " . $mysqli->error . "</li>";
    }
}

// 3. items.status needs to allow 'donated' so completed items disappear from Browse
$col = $mysqli->query("SHOW COLUMNS FROM items LIKE 'status'")->fetch_assoc();
if ($col && strpos($col['Type'], "'donated'") !== false) {
    echo "<li>✅ items.status already supports 'donated'.</li>";
} else {
    if ($mysqli->query("ALTER TABLE items MODIFY status ENUM('pending','approved','rejected','donated') DEFAULT 'pending'")) {
        echo "<li>✅ Updated items.status to support 'donated'.</li>";
    } else {
        echo "<li style='color:red;'>❌ Could not update items.status: " . $mysqli->error . "</li>";
    }
}

echo "</ul><p style='margin-top:20px;color:#b00'>⚠️ Delete this file (migrate_features.php) after running it once.</p>";
echo "<p><a href='admin-dashboard.html'>Go to Admin Dashboard</a> | <a href='browse.html'>Go to Browse</a></p>";
$mysqli->close();
