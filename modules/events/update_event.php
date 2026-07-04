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

$event_id = intval($_POST['event_id'] ?? 0);
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$event_type = $_POST['event_type'] ?? '';
$event_date = $_POST['event_date'] ?? '';
$location = $_POST['location'] ?? '';
$max_participants = intval($_POST['max_participants'] ?? 50);
$participants = intval($_POST['participants'] ?? 0);
$prize = $_POST['prize'] ?? '';
$rules = $_POST['rules'] ?? '';

if ($event_id === 0 || empty($title) || empty($description) || empty($event_type) || empty($event_date)) {
    header('Location: events.php?error=Invalid data provided');
    exit();
}

if ($participants > $max_participants) {
    $participants = $max_participants;
}

try {
    $stmt = $pdo->prepare("
        UPDATE events 
        SET title = ?, description = ?, event_type = ?, event_date = ?, location = ?, 
            max_participants = ?, participants = ?, prize = ?, rules = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $title,
        $description,
        $event_type,
        $event_date,
        $location,
        $max_participants,
        $participants,
        $prize,
        $rules,
        $event_id
    ]);
    
    header('Location: events.php?success=Event updated successfully');
    exit();
    
} catch (Exception $e) {
    header('Location: events.php?error=Failed to update event: ' . urlencode($e->getMessage()));
    exit();
}
?>