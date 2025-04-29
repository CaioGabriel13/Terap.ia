<?php
session_start();
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);

    if (empty($email)) {
        header('Location: ./pages/login.php?email_error=O campo de e-mail é obrigatório.');
        exit;
    }

    if (empty($senha)) {
        header('Location: ./pages/login.php?password_error=O campo de senha é obrigatório.');
        exit;
    }

    // Busca o usuário pelo e-mail
    $sql = "SELECT * FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        header('Location: ./pages/login.php?error=Usuário com este e-mail não existe.');
        exit;
    }

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
        header('Location: ./pages/login.php?error=Credenciais inválidas.');
        exit;
    }
} else {
    header('Location: ./pages/login.php');
    exit;
}
?>