<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header('Location: ./pages/login.php');
  exit;
}
require_once '../includes/config.php';

$user_id = $_SESSION['usuario']['id'];

// Obtém o nome do usuário logado
$user_name = $_SESSION['usuario']['nome'];

// Verifica se uma conversa específica foi selecionada
$conversation_id = isset($_GET['conversation_id']) ? (int) $_GET['conversation_id'] : null;

if ($conversation_id) {
  // Verifica se a conversa pertence ao usuário
  $sql = "SELECT * FROM conversations WHERE id = :conversation_id AND user_id = :user_id";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':conversation_id' => $conversation_id, ':user_id' => $user_id]);
  $conversation = $stmt->fetch();

  if (!$conversation) {
    die("Conversa não encontrada.");
  }
} else {
  // Seleciona a conversa mais recente ou cria uma nova
  $sql = "SELECT * FROM conversations WHERE user_id = :user_id ORDER BY id DESC LIMIT 1";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':user_id' => $user_id]);
  $conversation = $stmt->fetch();

  if (!$conversation) {
    $sql = "INSERT INTO conversations (user_id) VALUES (:user_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $conversation_id = $pdo->lastInsertId();
    $conversation = ['id' => $conversation_id];
  } else {
    $conversation_id = $conversation['id'];
  }
}

// Recupera todas as conversas do usuário
$sql = "SELECT * FROM conversations WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$conversations = $stmt->fetchAll();

// Recupera as mensagens da conversa selecionada
$sql = "SELECT * FROM messages WHERE conversation_id = :conversation_id ORDER BY created_at ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':conversation_id' => $conversation_id]);
$messages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Terap.IA</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>
  <div class="container my-5">
    <div class="card shadow-lg p-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary"><i class="fas fa-comments"></i> Terap.IA</h2>
        <div>
          <a href="edit_profile.php" class="btn btn-secondary"><i class="fas fa-user-edit"></i> Editar Perfil</a>
          <a href="ads_list.php" class="btn btn-info"><i class="fas fa-bullhorn"></i> Anúncios</a>
          <a href="appointments_list.php" class="btn btn-success"><i class="fas fa-calendar-alt"></i> Meus
            Agendamentos</a>
          <a href="../logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Deslogar</a>
        </div>
      </div>
      <div class="row">
        <div class="col-md-4">
          <h4>Suas Conversas</h4>
          <ul class="list-group">
            <?php foreach ($conversations as $conv): ?>
              <li class="list-group-item <?php echo $conv['id'] == $conversation_id ? 'active' : ''; ?>">
                <a href="chat.php?conversation_id=<?php echo $conv['id']; ?>" class="text-decoration-none text-dark">
                  Conversa #<?php echo $conv['id']; ?> - <?php echo $conv['created_at']; ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="col-md-8">
          <div class="chat-box">
            <?php foreach ($messages as $msg): ?>
              <div class="message <?php echo $msg['sender']; ?>">
                <strong>
                  <?php
                  echo $msg['sender'] === 'user' ? htmlspecialchars($user_name) : ucfirst($msg['sender']);
                  ?>:
                </strong>
                <?php echo htmlspecialchars($msg['message']); ?>
                <small><?php echo $msg['created_at']; ?></small>
              </div>
            <?php endforeach; ?>
          </div>
          <form action="processa_mensagem.php" method="POST" class="mt-3">
            <input type="hidden" name="conversation_id" value="<?php echo $conversation_id; ?>">
            <div class="mb-3">
              <label for="message" class="form-label">Sua Mensagem:</label>
              <input type="text" class="form-control" id="message" name="message" placeholder="Digite sua mensagem"
                required>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Enviar</button>
          </form>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>