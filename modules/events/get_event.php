<?php
session_start();

$host = 'localhost';
$dbname = 'chillcom';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed']));
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die(json_encode(['error' => 'Not authenticated']));
}

$user_id = $_SESSION['user_id'] ?? null;

if (!isset($_GET['id'])) {
    die(json_encode(['error' => 'Event ID not provided']));
}

$event_id = intval($_GET['id']);

$query = "
    SELECT e.*, 
           CASE 
               WHEN er.id IS NOT NULL THEN 1 
               ELSE 0 
           END as is_registered,
           er.registration_date as user_registration_date
    FROM events e
    LEFT JOIN event_registrations er ON e.id = er.event_id AND er.user_id = ?
    WHERE e.id = ?
";

$stmt = $pdo->prepare($query);
$stmt->execute([$user_id, $event_id]);
$event = $stmt->fetch();

if (!$event) {
    die(json_encode(['error' => 'Event not found']));
}

header('Content-Type: application/json');
echo json_encode($event);
?>