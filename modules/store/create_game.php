<?php
// create_game.php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$name = trim($_POST['name'] ?? '');
$image = trim($_POST['image'] ?? '');
$link = trim($_POST['link'] ?? '');
$category = $_POST['category'] ?? 'TG';
$is_popular = isset($_POST['is_popular']) ? 1 : 0;

if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Game name is required']);
    exit();
}

if (empty($image)) {
    echo json_encode(['success' => false, 'error' => 'Image URL is required']);
    exit();
}

if (empty($link)) {
    echo json_encode(['success' => false, 'error' => 'Game link is required']);
    exit();
}

try {
    $stmt = $pdo->prepare("INSERT INTO games (name, image, link, category, is_popular, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([$name, $image, $link, $category, $is_popular]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Game created successfully', 
        'id' => $pdo->lastInsertId(),
        'data' => ['name' => $name, 'category' => $category]
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>