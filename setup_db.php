<?php
// setup_db.php - Run this in your browser to setup the database
$host = "localhost";
$user = "root"; // Change this if your DB user is different
$pass = "";     // Change this if you have a password

try {
    $conn = new PDO("mysql:host=$host", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Read schema.sql
    $sql = file_get_contents("schema.sql");
    
    // Execute schema
    $conn->exec($sql);
    
    echo "<h1>✅ Database Setup Successful!</h1>";
    echo "<p>The 'revalue_hub' database and tables (users, items, requests) have been created.</p>";
    echo "<a href='admin-dashboard.html'>Go to Dashboard</a>";
} catch(PDOException $e) {
    echo "<h1>❌ Error Setting Up Database</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Make sure MariaDB/MySQL is running and your credentials are correct in this file.</p>";
}
?>
