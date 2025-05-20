<?php
session_start();
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $ad_id = $data['ad_id'] ?? null;
    $user_id = $_SESSION['usuario']['id'] ?? null;

    if (!$ad_id || !$user_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Parâmetros inválidos']);
        exit;
    }

    try {
        // Verifica se o usuário já curtiu este anúncio
        $check_sql = "SELECT id FROM ad_likes WHERE ad_id = :ad_id AND user_id = :user_id";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([':ad_id' => $ad_id, ':user_id' => $user_id]);

        if ($check_stmt->rowCount() > 0) {
            // Se já curtiu, remove o like
            $sql = "DELETE FROM ad_likes WHERE ad_id = :ad_id AND user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':ad_id' => $ad_id, ':user_id' => $user_id]);
            $action = 'unliked';
        } else {
            // Se não curtiu, adiciona o like
            $sql = "INSERT INTO ad_likes (ad_id, user_id) VALUES (:ad_id, :user_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':ad_id' => $ad_id, ':user_id' => $user_id]);
            $action = 'liked';
        }

        // Retorna o novo número de likes e status
        $count_sql = "SELECT COUNT(*) as likes FROM ad_likes WHERE ad_id = :ad_id";
        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute([':ad_id' => $ad_id]);
        $likes = $count_stmt->fetch(PDO::FETCH_ASSOC)['likes'];

        echo json_encode([
            'success' => true,
            'action' => $action,
            'likes' => $likes
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao processar like']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
}
