<?php
// update_game.php
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

$game_id = intval($_POST['game_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$image = trim($_POST['image'] ?? '');
$link = trim($_POST['link'] ?? '');
$category = $_POST['category'] ?? 'TG';
$is_popular = isset($_POST['is_popular']) ? 1 : 0;

if ($game_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid game ID']);
    exit();
}

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
    $stmt = $pdo->prepare("UPDATE games SET name = ?, image = ?, link = ?, category = ?, is_popular = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$name, $image, $link, $category, $is_popular, $game_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Game updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'No changes made or game not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>