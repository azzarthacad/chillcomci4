<?php
// get_game.php
require_once 'config.php';

header('Content-Type: application/json');

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
    $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$game) {
        echo json_encode(['success' => false, 'error' => 'Game not found']);
        exit();
    }
    
    echo json_encode(['success' => true, 'data' => $game]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>