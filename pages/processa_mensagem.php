<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $conversation_id = $_POST['conversation_id'];
    $message = trim($_POST['message']);
    $sender = 'user'; // Mensagem enviada pelo usuário

    // Insere a mensagem do usuário
    $sql = "INSERT INTO messages (conversation_id, sender, message) VALUES (:conversation_id, :sender, :message)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':conversation_id' => $conversation_id,
        ':sender' => $sender,
        ':message' => $message
    ]);

    // Para fins de demonstração, insere uma resposta simples do bot
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