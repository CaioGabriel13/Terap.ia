<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/config.php';

$user = $_SESSION['usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = !empty($_POST['senha']) ? password_hash(trim($_POST['senha']), PASSWORD_DEFAULT) : $user['senha'];
    $type = $_POST['type'];

    $sql = "UPDATE users SET nome = :nome, email = :email, senha = :senha, type = :type WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':senha' => $senha,
        ':type' => $type,
        ':id' => $user['id']
    ]);

    $_SESSION['usuario'] = array_merge($user, ['nome' => $nome, 'email' => $email, 'type' => $type]);
    header('Location: chat.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar Perfil</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mt-3">
      <h2><i class="fas fa-user-edit"></i> Editar Perfil</h2>
      <a href="<?php echo $_SESSION['usuario']['type'] === 'paciente' ? 'chat.php' : 'ads.php'; ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Voltar
      </a>
    </div>
    <form method="POST">
      <div class="mb-3">
        <label for="nome" class="form-label">Nome:</label>
        <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($user['nome']); ?>" required>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">E-mail:</label>
        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
      </div>
      <div class="mb-3">
        <label for="senha" class="form-label">Nova Senha (opcional):</label>
        <input type="password" class="form-control" id="senha" name="senha">
      </div>
      <div class="mb-3">
        <label for="type" class="form-label">Tipo de Usuário:</label>
        <select class="form-control" id="type" name="type">
          <option value="paciente" <?php echo $user['type'] === 'paciente' ? 'selected' : ''; ?>>Paciente</option>
          <option value="psicologo" <?php echo $user['type'] === 'psicologo' ? 'selected' : ''; ?>>Psicólogo</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Alterações</button>
    </form>
  </div>
</body>
</html>
