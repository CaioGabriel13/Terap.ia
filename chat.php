<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}
require_once 'includes/config.php';

$user_id = $_SESSION['usuario']['id'];

// Verifica se já existe uma conversa para o usuário
$sql = "SELECT * FROM conversations WHERE user_id = :user_id ORDER BY id DESC LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$conversation = $stmt->fetch();

if (!$conversation) {
    // Cria uma nova conversa para o usuário
    $sql = "INSERT INTO conversations (user_id) VALUES (:user_id)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    $conversation_id = $pdo->lastInsertId();
    $conversation = ['id' => $conversation_id];
} else {
    $conversation_id = $conversation['id'];
}

// Recupera as mensagens da conversa
$sql = "SELECT * FROM messages WHERE conversation_id = :conversation_id ORDER BY created_at ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':conversation_id' => $conversation_id]);
$messages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Chatbot</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mt-3">
      <h2><i class="fas fa-comments"></i> Chatbot</h2>
      <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Deslogar</a>
    </div>
    <div class="chat-box mt-3">
      <?php foreach ($messages as $msg): ?>
        <div class="message <?php echo $msg['sender']; ?>">
          <strong><?php echo ucfirst($msg['sender']); ?>:</strong> <?php echo htmlspecialchars($msg['message']); ?>
          <small><?php echo $msg['created_at']; ?></small>
        </div>
      <?php endforeach; ?>
    </div>
    <form action="processa_mensagem.php" method="POST" class="mt-3">
      <input type="hidden" name="conversation_id" value="<?php echo $conversation_id; ?>">
      <div class="mb-3">
        <label for="message" class="form-label">Sua Mensagem:</label>
        <input type="text" class="form-control" id="message" name="message" placeholder="Digite sua mensagem" required>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Enviar</button>
    </form>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
