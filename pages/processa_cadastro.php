<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    // Validações básicas
    if (empty($nome) || empty($email) || empty($senha)) {
        header('Location: cadastro.php?error=Todos os campos são obrigatórios.');
        exit;
    }

    if (strlen($senha) < 6) {
        header('Location: cadastro.php?error=A senha deve ter pelo menos 6 caracteres.');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: cadastro.php?error=E-mail inválido.');
        exit;
    }

    // Hash da senha para segurança
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // Verifica se o e-mail já está cadastrado
    $sqlCheck = "SELECT id FROM users WHERE email = :email";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([':email' => $email]);

    if ($stmtCheck->rowCount() > 0) {
        header('Location: cadastro.php?error=E-mail já cadastrado.');
        exit;
    }

    // Inserção no banco
    $sql = "INSERT INTO users (nome, email, senha) VALUES (:nome, :email, :senha)";
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':senha' => $senhaHash
        ]);

        $userId = $pdo->lastInsertId();

        // Set default weekly availability for psychologists
        if ($_POST['type'] === 'psicologo') {
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
                $stmt->execute(array_merge([':user_id' => $userId], $slot));
            }
        }

        header('Location: login.php');
        exit;
    } catch (Exception $e) {
        header('Location: cadastro.php?error=Erro ao cadastrar. Tente novamente mais tarde.');
        exit;
    }
} else {
    header('Location: cadastro.php');
    exit;
}
?>