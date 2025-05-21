<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conversation_id = isset($_POST['conversation_id']) ? (int) $_POST['conversation_id'] : null;
    $message = trim($_POST['message']);
    $sender = 'user';

    // Validate conversation ownership
    $sql = "SELECT * FROM conversations WHERE id = :conversation_id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':conversation_id' => $conversation_id,
        ':user_id' => $_SESSION['usuario']['id']
    ]);
    $conversation = $stmt->fetch();
    if (!$conversation) {
        header('Location: chat.php?error=Conversa inválida.');
        exit;
    }

    // Insert user message
    $sql = "INSERT INTO messages (conversation_id, sender, message) VALUES (:conversation_id, :sender, :message)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':conversation_id' => $conversation_id,
        ':sender' => $sender,
        ':message' => $message
    ]);

    // Simple bot reply
    $botMessage = "Esta é uma resposta do bot.";
    $sql = "INSERT INTO messages (conversation_id, sender, message) VALUES (:conversation_id, :sender, :message)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':conversation_id' => $conversation_id,
        ':sender' => 'bot',
        ':message' => $botMessage
    ]);

    header("Location: chat.php?conversation_id=$conversation_id");
    exit;
} else {
    header('Location: chat.php');
    exit;
}
?>