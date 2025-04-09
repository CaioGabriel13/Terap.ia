<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['type'] !== 'psicologo') {
    header('Location: login.php');
    exit;
}
require_once 'includes/config.php';

$user_id = $_SESSION['usuario']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_ad'])) {
        // Remover anúncio
        $ad_id = $_POST['ad_id'];
        $sql = "DELETE FROM ads WHERE id = :ad_id AND user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':ad_id' => $ad_id, ':user_id' => $user_id]);
    } elseif (isset($_POST['edit_ad'])) {
        // Editar anúncio
        $ad_id = $_POST['ad_id'];
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $sql = "UPDATE ads SET title = :title, content = :content WHERE id = :ad_id AND user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':title' => $title, ':content' => $content, ':ad_id' => $ad_id, ':user_id' => $user_id]);
    } else {
        // Criar novo anúncio
        $title = trim($_POST['title']);
        $content = trim($_POST['content']);
        $sql = "INSERT INTO ads (user_id, title, content) VALUES (:user_id, :title, :content)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $user_id, ':title' => $title, ':content' => $content]);
    }
}

$sql = "SELECT * FROM ads WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id]);
$ads = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Meus Anúncios</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Added Font Awesome -->
</head>
<body>
  <div class="container my-5">
    <div class="card shadow-lg p-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary"><i class="fas fa-bullhorn"></i> Meus Anúncios</h2>
        <div>
          <a href="appointments_list.php" class="btn btn-success"><i class="fas fa-calendar-alt"></i> Meus Agendamentos</a> <!-- Added -->
          <a href="edit_profile.php" class="btn btn-secondary"><i class="fas fa-user-edit"></i> Editar Perfil</a> <!-- Icon added -->
          <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Deslogar</a> <!-- Icon added -->
        </div>
      </div>
      <form method="POST" class="mb-3">
        <div class="mb-3">
          <label for="title" class="form-label">Título:</label>
          <input type="text" class="form-control" id="title" name="title" required>
        </div>
        <div class="mb-3">
          <label for="content" class="form-label">Conteúdo:</label>
          <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Postar Anúncio</button> <!-- Icon added -->
      </form>
      <h3><i class="fas fa-list"></i> Anúncios Postados</h3>
      <?php foreach ($ads as $ad): ?>
        <div class="card mb-3">
          <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($ad['title']); ?></h5>
            <p class="card-text"><?php echo htmlspecialchars($ad['content']); ?></p>
            <small class="text-muted">Postado em: <?php echo $ad['created_at']; ?></small>
            <form method="POST" class="mt-2">
              <input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>">
              <div class="mb-2">
                <label for="title_<?php echo $ad['id']; ?>" class="form-label">Editar Título:</label>
                <input type="text" class="form-control" id="title_<?php echo $ad['id']; ?>" name="title" value="<?php echo htmlspecialchars($ad['title']); ?>" required>
              </div>
              <div class="mb-2">
                <label for="content_<?php echo $ad['id']; ?>" class="form-label">Editar Conteúdo:</label>
                <textarea class="form-control" id="content_<?php echo $ad['id']; ?>" name="content" rows="2" required><?php echo htmlspecialchars($ad['content']); ?></textarea>
              </div>
              <button type="submit" name="edit_ad" class="btn btn-warning"><i class="fas fa-edit"></i> Editar</button> <!-- Icon added -->
              <button type="submit" name="delete_ad" class="btn btn-danger"><i class="fas fa-trash"></i> Remover</button> <!-- Icon added -->
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>
