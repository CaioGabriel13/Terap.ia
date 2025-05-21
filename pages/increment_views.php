<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['ad_id']) || !is_numeric($data['ad_id'])) {
    echo json_encode(['error' => 'ID do anúncio inválido.']);
    exit;
}
$adId = (int) $data['ad_id'];

try {
    $stmt = $pdo->prepare('UPDATE ads SET views = views + 1 WHERE id = :ad_id');
    $stmt->execute([':ad_id' => $adId]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao incrementar visualizações.']);
}
