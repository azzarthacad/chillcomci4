<?php
// delete_game.php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Game ID required']);
    exit();
}

$game_id = intval($_GET['id']);

if ($game_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid game ID']);
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Game deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Game not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>