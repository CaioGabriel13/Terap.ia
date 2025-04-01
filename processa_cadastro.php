<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome  = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    // Hash da senha para segurança
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // Inserção no banco
    $sql = "INSERT INTO users (nome, email, senha) VALUES (:nome, :email, :senha)";
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([
            ':nome'  => $nome,
            ':email' => $email,
            ':senha' => $senhaHash
        ]);
        header('Location: login.php');
        exit;
    } catch (Exception $e) {
        die("Erro ao cadastrar: " . $e->getMessage());
    }
} else {
    header('Location: cadastro.php');
    exit;
}
?>
