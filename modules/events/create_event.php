<?php
session_start();

// Check admin privileges
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['role'] ?? 'member') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$host = 'localhost';
$dbname = 'chillcom';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$event_type = $_POST['event_type'] ?? '';
$event_date = $_POST['event_date'] ?? '';
$location = $_POST['location'] ?? '';
$max_participants = intval($_POST['max_participants'] ?? 50);
$prize = $_POST['prize'] ?? '';
$rules = $_POST['rules'] ?? '';

if (empty($title) || empty($description) || empty($event_type) || empty($event_date) || empty($max_participants)) {
    header('Location: events.php?error=Please fill all required fields');
    exit();
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO events (title, description, event_type, event_date, location, max_participants, prize, rules, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $title,
        $description,
        $event_type,
        $event_date,
        $location,
        $max_participants,
        $prize,
        $rules
    ]);
    
    header('Location: events.php?success=Event created successfully');
    exit();
    
} catch (Exception $e) {
    header('Location: events.php?error=Failed to create event: ' . urlencode($e->getMessage()));
    exit();
}
?>