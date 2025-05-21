<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header('Location: ./pages/login.php');
  exit;
}
require_once '../includes/config.php';

$user_id = $_SESSION['usuario']['id'];
$user_name = $_SESSION['usuario']['nome'];

// Handle conversation deletion
if (isset($_GET['delete_conversation_id'])) {
  $delete_id = (int) $_GET['delete_conversation_id'];
  $sql = "DELETE FROM conversations WHERE id = :id AND user_id = :user_id";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':id' => $delete_id, ':user_id' => $user_id]);
  // Also delete messages (if not ON DELETE CASCADE)
  $sql = "DELETE FROM messages WHERE conversation_id = :conversation_id";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':conversation_id' => $delete_id]);
  header('Location: chat.php');
  exit;
}

// Create new conversation if requested
if (isset($_GET['new_conversation'])) {
  $sql = "INSERT INTO conversations (user_id) VALUES (:user_id)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':user_id' => $user_id]);
  $conversation_id = $pdo->lastInsertId();
  header('Location: chat.php?conversation_id=' . $conversation_id);
  exit;
}

$conversation_id = isset($_GET['conversation_id']) ? (int) $_GET['conversation_id'] : null;

// Fetch all conversations for sidebar
$sql = "SELECT * FROM conversations WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$conversations = $stmt->fetchAll();

// If no conversation selected, pick the latest
if (!$conversation_id && count($conversations) > 0) {
  $conversation_id = $conversations[0]['id'];
}

// Fetch selected conversation
$conversation = null;
if ($conversation_id) {
  $sql = "SELECT * FROM conversations WHERE id = :conversation_id AND user_id = :user_id";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':conversation_id' => $conversation_id, ':user_id' => $user_id]);
  $conversation = $stmt->fetch();
}

// Fetch messages for selected conversation
$messages = [];
if ($conversation) {
  $sql = "SELECT * FROM messages WHERE conversation_id = :conversation_id ORDER BY created_at ASC";
  $stmt = $pdo->prepare($sql);
  $stmt->execute([':conversation_id' => $conversation_id]);
  $messages = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Terap.IA</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>
  <div class="container-fluid px-0" style="max-width: 1200px; margin: 0 auto;">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3 px-3">
      <h2 class="text-primary mb-0"><i class="fas fa-comments"></i> Terap.IA</h2>
      <div class="d-flex gap-2 flex-wrap">
        <a href="edit_profile.php" class="btn btn-secondary"><i class="fas fa-user-edit"></i> Editar Perfil</a>
        <a href="ads_list.php" class="btn btn-info"><i class="fas fa-bullhorn"></i> Anúncios</a>
        <a href="appointments_list.php" class="btn btn-success"><i class="fas fa-calendar-alt"></i> Meus
          Agendamentos</a>
        <a href="../logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Deslogar</a>
      </div>
    </div>
    <div class="chat-layout">
      <aside class="chat-sidebar">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h4 class="mb-0"><i class="fas fa-inbox"></i> Suas Conversas</h4>
          <a href="chat.php?new_conversation=1" class="btn btn-sm btn-primary" title="Nova Conversa"><i
              class="fas fa-plus"></i></a>
        </div>
        <ul class="list-group list-group-flush">
          <?php foreach ($conversations as $conv): ?>
            <li
              class="d-flex align-items-center justify-content-between list-group-item<?php echo $conv['id'] == $conversation_id ? ' active' : ''; ?>">
              <a href="chat.php?conversation_id=<?php echo $conv['id']; ?>"
                class="flex-grow-1 text-decoration-none <?php echo $conv['id'] == $conversation_id ? 'text-white' : ''; ?>">
                <div class="fw-bold">Conversa #<?php echo $conv['id']; ?></div>
                <small><?php echo $conv['created_at']; ?></small>
              </a>
              <a href="chat.php?delete_conversation_id=<?php echo $conv['id']; ?>" class="btn btn-sm btn-danger ms-2"
                title="Deletar" onclick="return confirm('Tem certeza que deseja deletar esta conversa?');"><i
                  class="fas fa-trash"></i></a>
            </li>
          <?php endforeach; ?>
        </ul>
      </aside>
      <main class="chat-main">
        <div class="chat-main-header">
          <div class="fw-bold text-primary"><i class="fas fa-user"></i> <?php echo htmlspecialchars($user_name); ?>
          </div>
        </div>
        <div class="chat-box mb-2">
          <?php if ($conversation): ?>
            <?php foreach ($messages as $msg): ?>
              <div class="message <?php echo $msg['sender']; ?>">
                <strong>
                  <?php echo $msg['sender'] === 'user' ? htmlspecialchars($user_name) : ucfirst($msg['sender']); ?>:
                </strong>
                <?php echo htmlspecialchars($msg['message']); ?>
                <small><?php echo $msg['created_at']; ?></small>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="alert alert-info">Selecione ou crie uma conversa para começar.</div>
          <?php endif; ?>
        </div>
        <?php if ($conversation): ?>
          <form action="processa_mensagem.php" method="POST" class="mt-2">
            <input type="hidden" name="conversation_id" value="<?php echo $conversation_id; ?>">
            <div class="input-group">
              <input type="text" class="form-control" id="message" name="message" placeholder="Digite sua mensagem"
                required autocomplete="off">
              <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Enviar</button>
            </div>
          </form>
        <?php endif; ?>
      </main>
    </div>
  </div>
</body>

</html>