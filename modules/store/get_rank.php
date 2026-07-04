<?php
// get_rank.php
require_once 'config.php';

header('Content-Type: application/json');

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
    $stmt = $pdo->prepare("SELECT * FROM ranks WHERE id = ?");
    $stmt->execute([$rank_id]);
    $rank = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rank) {
        echo json_encode(['success' => false, 'error' => 'Rank not found']);
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT * FROM rank_features WHERE rank_id = ? ORDER BY id ASC");
    $stmt->execute([$rank_id]);
    $rank['features'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $rank]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>