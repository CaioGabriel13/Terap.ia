<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $user_id = $_SESSION['usuario']['id'];

    $sql = "UPDATE users SET type = :type WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':type' => $type, ':id' => $user_id]);

    $_SESSION['usuario']['type'] = $type;
    header('Location: chat.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Escolher Tipo de Usuário</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <div class="container">
    <h2><i class="fas fa-user-tag"></i> Escolher Tipo de Usuário</h2>
    <form method="POST">
      <div class="mb-3">
        <label for="type" class="form-label">Tipo de Usuário:</label>
        <select class="form-control" id="type" name="type" required>
          <option value="paciente">Paciente</option>
          <option value="psicologo">Psicólogo</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar</button>
    </form>
  </div>
</body>
</html>
