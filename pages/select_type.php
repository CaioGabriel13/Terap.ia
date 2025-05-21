<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header('Location: login.php');
  exit;
}
require_once '../includes/config.php';

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
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>
  <nav class="navbar navbar-expand-lg bg-white border-bottom shadow-sm mb-0">
    <div class="container-fluid">
      <a class="navbar-brand text-primary fw-bold" href="../index.html">Terap.IA</a>
    </div>
  </nav>
  <div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card p-4 w-100" style="max-width: 400px;">
      <h2 class="text-center text-primary mb-4"><i class="fas fa-user-tag"></i> Escolher Tipo</h2>
      <form method="POST">
        <div class="mb-3">
          <label for="type" class="form-label">Tipo de Usuário:</label>
          <select class="form-select" id="type" name="type" required>
            <option value="paciente">Paciente</option>
            <option value="psicologo">Psicólogo</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save"></i> Salvar</button>
      </form>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>