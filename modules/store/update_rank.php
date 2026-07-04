<?php
// update_rank.php
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

$rank_id = intval($_POST['rank_id'] ?? 0);
$name = trim($_POST['rank_name'] ?? '');
$price = floatval($_POST['rank_price'] ?? 0);
$color = $_POST['rank_color'] ?? '#7289da';
$badge_type = $_POST['badge_type'] ?? 'custom';

if ($rank_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid rank ID']);
    exit();
}

if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Rank name is required']);
    exit();
}

if ($price <= 0) {
    echo json_encode(['success' => false, 'error' => 'Valid rank price is required']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("UPDATE ranks SET name = ?, price = ?, color = ?, badge_type = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$name, $price, $color, $badge_type, $rank_id]);
    
    $stmt = $pdo->prepare("DELETE FROM rank_features WHERE rank_id = ?");
    $stmt->execute([$rank_id]);
    
    if (isset($_POST['features']) && is_array($_POST['features'])) {
        $stmt = $pdo->prepare("INSERT INTO rank_features (rank_id, feature_text, is_premium) VALUES (?, ?, ?)");
        foreach ($_POST['features'] as $feature) {
            $feature_text = trim($feature['text'] ?? '');
            if (!empty($feature_text)) {
                $is_premium = isset($feature['premium']) ? 1 : 0;
                $stmt->execute([$rank_id, $feature_text, $is_premium]);
            }
        }
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Rank updated successfully']);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>