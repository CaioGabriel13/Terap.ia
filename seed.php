<?php
require_once 'includes/config.php';

// Seed para usuários
$users = [
    ['nome' => 'Paciente', 'email' => 'paciente@email.com', 'senha' => password_hash('123', PASSWORD_DEFAULT), 'type' => 'paciente'],
    ['nome' => 'Psicólogo', 'email' => 'psicologo@email.com', 'senha' => password_hash('123', PASSWORD_DEFAULT), 'type' => 'psicologo']
];

foreach ($users as $user) {
    $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $user['email']]);
    $exists = $stmt->fetchColumn();

    if (!$exists) {
        $sql = "INSERT INTO users (id, nome, email, senha, type) 
                SELECT COALESCE(MAX(id), 0) + 1, :nome, :email, :senha, :type FROM users";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($user);
        echo "Usuário criado com o próximo ID disponível.\n";
    }
}

// Verifica se o usuário com email = 'paciente@email.com' existe
$sql = "SELECT id FROM users WHERE email = :email";
$stmt = $pdo->prepare($sql);
$stmt->execute([':email' => 'paciente@email.com']);
$userId = $stmt->fetchColumn();

if ($userId) {
    // Seed para conversas e mensagens
    $sql = "SELECT COUNT(*) FROM conversations WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    $conversationExists = $stmt->fetchColumn();

    if (!$conversationExists) {
        $sql = "INSERT INTO conversations (user_id) VALUES (:user_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
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
    }
} else {
    echo "Usuário com email = 'paciente@email.com' não encontrado. Certifique-se de que os usuários foram inseridos corretamente.\n";
}

// Add default availability for psychologists
$sql = "SELECT id FROM users WHERE type = 'psicologo'";
$stmt = $pdo->query($sql);
$psychologists = $stmt->fetchAll();

foreach ($psychologists as $psychologist) {
    $sql = "SELECT COUNT(*) FROM availability WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $psychologist['id']]);
    $availabilityExists = $stmt->fetchColumn();

    if (!$availabilityExists) {
        $defaultAvailability = [
            ['day_of_week' => 'monday', 'hour_start' => '08:00:00', 'hour_end' => '18:00:00', 'price' => 100.00],
            ['day_of_week' => 'tuesday', 'hour_start' => '08:00:00', 'hour_end' => '18:00:00', 'price' => 100.00],
            ['day_of_week' => 'wednesday', 'hour_start' => '08:00:00', 'hour_end' => '18:00:00', 'price' => 100.00],
            ['day_of_week' => 'thursday', 'hour_start' => '08:00:00', 'hour_end' => '18:00:00', 'price' => 100.00],
            ['day_of_week' => 'friday', 'hour_start' => '08:00:00', 'hour_end' => '18:00:00', 'price' => 100.00],
        ];

        foreach ($defaultAvailability as $slot) {
            $sql = "INSERT INTO availability (user_id, day_of_week, hour_start, hour_end, price) 
                    VALUES (:user_id, :day_of_week, :hour_start, :hour_end, :price)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_merge([':user_id' => $psychologist['id']], $slot));
        }
    }
}
?>
