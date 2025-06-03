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
        <div class="chat-messages" id="chat-messages">
          <?php foreach ($messages as $msg): ?>
            <div class="message <?php echo $msg['sender']; ?>">
              <strong><?php echo $msg['sender'] === 'user' ? htmlspecialchars($user_name) : 'Terap.IA'; ?>:</strong>
              <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
              <small><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></small>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="chat-input">
          <form id="chat-form" class="d-flex gap-2">
            <input type="hidden" name="conversation_id" value="<?php echo $conversation_id; ?>">
            <input type="text" name="message" autocomplete="off"class="form-control" placeholder="Digite sua mensagem..." required>
            <button type="submit" class="btn btn-primary" id="send-button">
              <i class="fas fa-paper-plane"></i> Enviar
            </button>
          </form>
        </div>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('chat-form');
      const messagesDiv = document.getElementById('chat-messages');
      const sendButton = document.getElementById('send-button');

      // Função para rolar para a última mensagem
      function scrollToBottom() {
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
      }

      // Rolar para a última mensagem ao carregar
      scrollToBottom();

      form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const messageInput = form.querySelector('input[name="message"]');
        const message = messageInput.value;
        
        // Desabilitar o botão e input durante o envio
        sendButton.disabled = true;
        messageInput.disabled = true;
        
        try {
          // Adicionar a mensagem do usuário imediatamente
          const userMessageDiv = document.createElement('div');
          userMessageDiv.className = 'message user';
          userMessageDiv.innerHTML = `
            <strong><?php echo htmlspecialchars($user_name); ?>:</strong>
            <p>${message}</p>
            <small>${new Date().toLocaleString()}</small>
          `;
          messagesDiv.appendChild(userMessageDiv);
          scrollToBottom();
          
          // Limpar o input
          messageInput.value = '';
          
          // Mostrar indicador de digitação
          const typingDiv = document.createElement('div');
          typingDiv.className = 'message bot typing';
          typingDiv.innerHTML = '<strong>Terap.IA:</strong><p>Digitando...</p>';
          messagesDiv.appendChild(typingDiv);
          scrollToBottom();

          // Enviar mensagem para o servidor
          const response = await fetch('processa_mensagem.php', {
            method: 'POST',
            body: formData
          });

          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }

          const data = await response.json();
          
          // Remover indicador de digitação
          typingDiv.remove();
          
          if (data.error) {
            throw new Error(data.error);
          }

          // Adicionar resposta do bot
          const botMessageDiv = document.createElement('div');
          botMessageDiv.className = 'message bot';
          botMessageDiv.innerHTML = `
            <strong>Terap.IA:</strong>
            <p>${data.botMessage}</p>
            <small>${new Date().toLocaleString()}</small>
          `;
          messagesDiv.appendChild(botMessageDiv);
          scrollToBottom();
          
        } catch (error) {
          console.error('Erro:', error);
          alert('Erro ao enviar mensagem: ' + error.message);
        } finally {
          // Reabilitar o botão e input
          sendButton.disabled = false;
          messageInput.disabled = false;
          messageInput.focus();
        }
      });
    });
  </script>

  <style>
    .chat-layout {
      display: grid;
      grid-template-columns: 300px 1fr;
      gap: 1rem;
      height: calc(100vh - 100px);
      margin: 0 1rem;
    }

    .chat-sidebar {
      background: #f8f9fa;
      padding: 1rem;
      border-radius: 8px;
      overflow-y: auto;
    }

    .chat-main {
      display: flex;
      flex-direction: column;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .chat-messages {
      flex-grow: 1;
      overflow-y: auto;
      padding: 1rem;
    }

    .chat-input {
      padding: 1rem;
      border-top: 1px solid #dee2e6;
    }

    .message {
      margin-bottom: 1rem;
      padding: 0.75rem;
      border-radius: 8px;
      max-width: 80%;
    }

    .message.user {
      background-color: #e3f2fd;
      margin-left: auto;
    }

    .message.bot {
      background-color: #f5f5f5;
      margin-right: auto;
    }

    .message p {
      margin: 0.5rem 0;
    }

    .message small {
      display: block;
      font-size: 0.75rem;
      color: #6c757d;
    }

    .typing {
      opacity: 0.7;
    }

    @media (max-width: 768px) {
      .chat-layout {
        grid-template-columns: 1fr;
      }
      
      .chat-sidebar {
        display: none;
      }
    }
  </style>
</body>
</html>