<?php
// delete_rank.php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'Rank ID required']);
    exit();
}

$rank_id = intval($_GET['id']);

if ($rank_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid rank ID']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("DELETE FROM rank_features WHERE rank_id = ?");
    $stmt->execute([$rank_id]);
    
    $stmt = $pdo->prepare("DELETE FROM ranks WHERE id = ?");
    $stmt->execute([$rank_id]);
    
    $pdo->commit();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Rank deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Rank not found']);
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>