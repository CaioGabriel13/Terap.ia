<?php
require_once 'includes/config.php';

// Seed para usuários
$users = [
    ['nome' => 'Paciente', 'email' => 'paciente@email.com', 'senha' => password_hash('123', PASSWORD_DEFAULT), 'type' => 'paciente'],
    ['nome' => 'Psicólogo', 'email' => 'psicologo@email.com', 'senha' => password_hash('123', PASSWORD_DEFAULT), 'type' => 'psicologo']
];

foreach ($users as $user) {
    $sql = "INSERT INTO users (nome, email, senha, type) VALUES (:nome, :email, :senha, :type)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($user);
}

// Seed para conversas e mensagens
$sql = "INSERT INTO conversations (user_id) VALUES (1)";
$pdo->exec($sql);
$conversation_id = $pdo->lastInsertId();

$messages = [
    ['conversation_id' => $conversation_id, 'sender' => 'user', 'message' => 'Olá, preciso de ajuda.'],
    ['conversation_id' => $conversation_id, 'sender' => 'bot', 'message' => 'Claro, como posso ajudar você?']
];

foreach ($messages as $message) {
    $sql = "INSERT INTO messages (conversation_id, sender, message) VALUES (:conversation_id, :sender, :message)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($message);
}
?>
