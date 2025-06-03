<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header('Location: ./pages/login.php');
  exit;
}

require_once '../includes/config.php';

// Fetch user data from the database if session data is incomplete
if (!isset($_SESSION['usuario']['nome']) || !isset($_SESSION['usuario']['email']) || !isset($_SESSION['usuario']['type'])) {
  $userId = $_SESSION['usuario']['id'] ?? null;
  if ($userId) {
    $sql = "SELECT nome, email, type FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $userId]);
    $userData = $stmt->fetch();
    if ($userData) {
      $_SESSION['usuario'] = array_merge($_SESSION['usuario'], $userData);
    } else {
      // If user data is not found, redirect to login
      session_destroy();
      header('Location: ./pages/login.php');
      exit;
    }
  } else {
    // If user ID is missing, redirect to login
    session_destroy();
    header('Location: ./pages/login.php');
    exit;
  }
}

$user = $_SESSION['usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['form_type']) && $_POST['form_type'] === 'delete_account') {
    $sql = "DELETE FROM users WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $user['id']]);
    session_destroy();
    header('Location: cadastro.php');
    exit;
  } elseif (isset($_POST['form_type']) && $_POST['form_type'] === 'update_profile') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = !empty($_POST['senha']) ? password_hash(trim($_POST['senha']), PASSWORD_DEFAULT) : $user['senha'];
    $type = $_POST['type'];

    // Validate required fields
    if (empty($nome) || empty($email) || empty($type)) {
      header('Location: edit_profile.php?error=Todos os campos são obrigatórios.');
      exit;
    }

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
  } elseif (isset($_POST['form_type']) && $_POST['form_type'] === 'update_availability') {
    if ($_SESSION['usuario']['type'] === 'psicologo' && isset($_POST['availability'])) {
      $availability = $_POST['availability'];
      $pdo->prepare("DELETE FROM availability WHERE user_id = :user_id")->execute([':user_id' => $user['id']]);
      foreach ($availability as $day => $slot) {
        if (!empty($slot['hour_start']) && !empty($slot['hour_end']) && !empty($slot['price'])) {
          $sql = "INSERT INTO availability (user_id, day_of_week, hour_start, hour_end, price, unavailable) 
                            VALUES (:user_id, :day_of_week, :hour_start, :hour_end, :price, :unavailable)";
          $stmt = $pdo->prepare($sql);
          $stmt->execute([
            ':user_id' => $user['id'],
            ':day_of_week' => $day,
            ':hour_start' => $slot['hour_start'],
            ':hour_end' => $slot['hour_end'],
            ':price' => $slot['price'],
            ':unavailable' => isset($slot['unavailable']) ? 1 : 0
          ]);
        }
      }
    }
    header('Location: edit_profile.php');
    exit;
  }
}

// Fetch current availability for display
$currentAvailability = [];
if ($_SESSION['usuario']['type'] === 'psicologo') {
  $sql = "SELECT day_of_week, hour_start, hour_end, price, unavailable FROM availability WHERE user_id = :user_id";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':user_id' => $user['id']]);
  $currentAvailability = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Editar Perfil</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
  <style>
    /* Remove animation, modernize card */
    body {
      background: linear-gradient(120deg, #f6f7fb 0%, #e9eefa 100%);
    }

    .profile-section-title {
      font-size: 1.3rem;
      color: #6366f1;
      font-weight: 600;
      margin-bottom: 18px;
    }

    .btn+.btn {
      margin-left: 8px;
    }

    .availability-label {
      font-weight: 500;
      color: #6366f1;
    }

    .availability-row {
      background: #f7f8fa;
      border-radius: 8px;
      padding: 8px 0;
      margin-bottom: 8px;
    }
  </style>
</head>

<body>
  <div class="container my-5">
    <div class="card p-4 shadow-lg">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary"><i class="fas fa-user-edit"></i> Editar Perfil</h2>
        <div>
          <a href="<?php echo $_SESSION['usuario']['type'] === 'paciente' ? 'chat.php' : 'ads.php'; ?>"
            class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
          </a>
        </div>
      </div>
      <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
      <?php endif; ?>
      <form method="POST" class="mb-4">
        <input type="hidden" name="form_type" value="update_profile">
        <div class="mb-3">
          <label for="nome" class="form-label">Nome:</label>
          <input type="text" class="form-control" id="nome" name="nome"
            value="<?php echo htmlspecialchars($user['nome']); ?>" required>
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">E-mail:</label>
          <input type="email" class="form-control" id="email" name="email"
            value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        <div class="mb-3">
          <label for="senha" class="form-label">Nova Senha (opcional):</label>
          <input type="password" class="form-control" id="senha" name="senha">
        </div>
        <div class="mb-3">
          <label for="type" class="form-label">Tipo de Usuário:</label>
          <select class="form-select" id="type" name="type" required>
            <option value="paciente" <?php echo $user['type'] === 'paciente' ? 'selected' : ''; ?>>Paciente</option>
            <option value="psicologo" <?php echo $user['type'] === 'psicologo' ? 'selected' : ''; ?>>Psicólogo</option>
          </select>
        </div>
        <div class="d-flex justify-content-center">
          <div class="d-grid gap-2 mb-2" style="width: 100%; max-width: 400px;">
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save"></i> Salvar Alterações</button>
          </div>
        </div>
        <div class="d-flex justify-content-center">
          <div class="d-grid gap-2 mb-2" style="width: 100%; max-width: 400px;">
            <button type="submit" name="form_type" value="delete_account" class="btn btn-danger w-100">
              <i class="fas fa-trash"></i> Deletar Conta
            </button>
          </div>
        </div>
      </form>
      <?php if ($_SESSION['usuario']['type'] === 'psicologo'): ?>
        <form method="POST" class="mt-4">
          <input type="hidden" name="form_type" value="update_availability">
          <h4 class="profile-section-title"><i class="fas fa-calendar-alt"></i> Disponibilidade</h4>
          <?php
          $daysOfWeek = ['monday' => 'Segunda-feira', 'tuesday' => 'Terça-feira', 'wednesday' => 'Quarta-feira', 'thursday' => 'Quinta-feira', 'friday' => 'Sexta-feira', 'saturday' => 'Sábado', 'sunday' => 'Domingo'];
          foreach ($daysOfWeek as $day => $label):
            $currentSlot = array_filter($currentAvailability, fn($slot) => $slot['day_of_week'] === $day);
            $currentSlot = $currentSlot ? array_values($currentSlot)[0] : null;
            ?>
            <div class="row align-items-center availability-row">
              <div class="col-md-3 availability-label"><?php echo $label; ?></div>
              <div class="col-md-3">
                <input type="time" class="form-control" name="availability[<?php echo $day; ?>][hour_start]"
                  placeholder="Início" value="<?php echo $currentSlot['hour_start'] ?? ''; ?>">
              </div>
              <div class="col-md-3">
                <input type="time" class="form-control" name="availability[<?php echo $day; ?>][hour_end]" placeholder="Fim"
                  value="<?php echo $currentSlot['hour_end'] ?? ''; ?>">
              </div>
              <div class="col-md-2">
                <input type="number" step="0.01" class="form-control" name="availability[<?php echo $day; ?>][price]"
                  placeholder="Preço" value="<?php echo $currentSlot['price'] ?? ''; ?>">
              </div>
              <div class="col-md-1 text-center">
                <input type="checkbox" class="form-check-input mt-2" name="availability[<?php echo $day; ?>][unavailable]"
                  <?php echo isset($currentSlot['unavailable']) && $currentSlot['unavailable'] ? 'checked' : ''; ?>>
              </div>
            </div>
          <?php endforeach; ?>
          <button type="submit" class="btn btn-primary w-100 mt-3"><i class="fas fa-save"></i> Salvar
            Disponibilidade</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</body>

</html>