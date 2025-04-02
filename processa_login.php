<?php
session_start();
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    // Busca o usuário pelo e-mail
    $sql = "SELECT * FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario'] = $usuario;
        if (empty($usuario['type'])) {
            header('Location: select_type.php');
            exit;
        }
        if ($usuario['type'] === 'paciente') {
            header('Location: chat.php');
        } else {
            header('Location: ads.php');
        }
        exit;
    } else {
        echo "E-mail ou senha incorretos.";
    }
} else {
    header('Location: login.php');
    exit;
}
?>
