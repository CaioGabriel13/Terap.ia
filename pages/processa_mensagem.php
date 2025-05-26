<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/config.php';
require_once '../includes/config_chatgpt.php';

// Configurar cabeçalho para JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conversation_id = isset($_POST['conversation_id']) ? (int) $_POST['conversation_id'] : null;
    $message = trim($_POST['message']);
    $sender = 'user';

    try {
        // Validate conversation ownership
        $sql = "SELECT * FROM conversations WHERE id = :conversation_id AND user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':conversation_id' => $conversation_id,
            ':user_id' => $_SESSION['usuario']['id']
        ]);
        $conversation = $stmt->fetch();
        
        if (!$conversation) {
            throw new Exception('Conversa inválida');
        }

        // Inicia uma transação para garantir que ambas as mensagens sejam salvas
        $pdo->beginTransaction();

        // Insere a mensagem do usuário
        $sql = "INSERT INTO messages (conversation_id, sender, message) VALUES (:conversation_id, :sender, :message)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':conversation_id' => $conversation_id,
            ':sender' => 'user',
            ':message' => $message
        ]);

        // Obtém resposta do ChatGPT
        $botMessage = getChatGPTResponse($message, $conversation_id);
        
        // Insere a resposta do ChatGPT
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':conversation_id' => $conversation_id,
            ':sender' => 'bot',
            ':message' => $botMessage
        ]);

        // Confirma as alterações no banco de dados
        $pdo->commit();
        
        // Retorna sucesso e a mensagem do bot
        echo json_encode([
            'success' => true,
            'botMessage' => $botMessage
        ]);

    } catch (Exception $e) {
        // Se houver algum erro, desfaz as alterações
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Erro ao processar mensagem: " . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erro ao processar a mensagem: ' . $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido'
    ]);
}
?>