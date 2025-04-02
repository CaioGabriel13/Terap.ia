<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['type'] !== 'paciente') {
    header('Location: login.php');
    exit;
}
require_once 'includes/config.php';

$filter = $_GET['filter'] ?? '';
$sql = "SELECT ads.*, users.nome AS psicologo_nome FROM ads 
        JOIN users ON ads.user_id = users.id 
        WHERE users.type = 'psicologo'";

$params = [];
if ($filter) {
    $sql .= " AND ads.title LIKE :filter";
    $params[':filter'] = "%$filter%";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ads = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
      <h2 class="mb-3"><i class="fas fa-bullhorn text-primary"></i> Anúncios de Psicólogos</h2>
      <div class="d-flex gap-2">
        <a href="edit_profile.php" class="btn btn-secondary"><i class="fas fa-user-edit"></i> Editar Perfil</a>
        <a href="chat.php" class="btn btn-primary"><i class="fas fa-comments"></i> Chat</a>
        <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Deslogar</a>
      </div>
    </div>
    <form method="GET" class="mb-3">
      <div class="input-group">
        <span class="input-group-text"><i class="fas fa-filter"></i></span>
        <input type="text" class="form-control" id="filter" name="filter" placeholder="Filtrar por título" value="<?php echo htmlspecialchars($filter); ?>">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
      </div>
    </form>
    <h3 class="mt-4"><i class="fas fa-list"></i> Resultados</h3>
    <?php if (count($ads) > 0): ?>
      <?php foreach ($ads as $ad): ?>
        <div class="card mb-3 shadow-sm">
          <div class="card-body">
            <h5 class="card-title"><i class="fas fa-ad"></i> <?php echo htmlspecialchars($ad['title']); ?></h5>
            <p class="card-text"><?php echo htmlspecialchars($ad['content']); ?></p>
            <small class="text-muted"><i class="fas fa-user"></i> Postado por: <?php echo htmlspecialchars($ad['psicologo_nome']); ?> em <?php echo $ad['created_at']; ?></small>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p class="text-muted"><i class="fas fa-info-circle"></i> Nenhum anúncio encontrado.</p>
    <?php endif; ?>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
