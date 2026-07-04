<?php
// CONFIGURASI SIMPLE
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'chillcom');

// Test connection
try {
    $test = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    // echo "✅ Database connected";
} catch(PDOException $e) {
    die("❌ Database error: " . $e->getMessage());
}
?>