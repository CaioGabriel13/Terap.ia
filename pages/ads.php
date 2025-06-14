<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['type'] !== 'psicologo') {
  header('Location: ./pages/login.php');
  exit;
}
require_once '../includes/config.php';

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
    $category = trim($_POST['category']);
    $tags = trim($_POST['tags']);
    $image_url = null;
    // Processa o upload da imagem se houver
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      $upload_dir = '../uploads/ads/';
      if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
      }
      $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
      $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
      if (in_array($file_extension, $allowed_extensions) && $_FILES['image']['size'] <= 2 * 1024 * 1024) {
        $image_url = $upload_dir . uniqid() . '.' . $file_extension;
        move_uploaded_file($_FILES['image']['tmp_name'], $image_url);
        $image_url = str_replace('../', '', $image_url);
      }
    }
    if ($image_url) {
      $sql = "UPDATE ads SET title = :title, content = :content, category = :category, tags = :tags, image_url = :image_url WHERE id = :ad_id AND user_id = :user_id";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([':title' => $title, ':content' => $content, ':category' => $category, ':tags' => $tags, ':image_url' => $image_url, ':ad_id' => $ad_id, ':user_id' => $user_id]);
    } else {
      $sql = "UPDATE ads SET title = :title, content = :content, category = :category, tags = :tags WHERE id = :ad_id AND user_id = :user_id";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([':title' => $title, ':content' => $content, ':category' => $category, ':tags' => $tags, ':ad_id' => $ad_id, ':user_id' => $user_id]);
    }
  } else {
    // Criar novo anúncio
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $category = trim($_POST['category']);
    $tags = trim($_POST['tags']);
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
      $upload_dir = '../uploads/ads/';
      if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
      }
      $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
      $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
      if (in_array($file_extension, $allowed_extensions) && $_FILES['image']['size'] <= 2 * 1024 * 1024) {
        $image_url = $upload_dir . uniqid() . '.' . $file_extension;
        move_uploaded_file($_FILES['image']['tmp_name'], $image_url);
        $image_url = str_replace('../', '', $image_url);
      }
    }
    $sql = "INSERT INTO ads (user_id, title, content, category, tags, image_url) 
            VALUES (:user_id, :title, :content, :category, :tags, :image_url)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      ':user_id' => $user_id,
      ':title' => $title,
      ':content' => $content,
      ':category' => $category,
      ':tags' => $tags,
      ':image_url' => $image_url
    ]);
  }
}

// Buscar anúncios com likes e views
$sql = "SELECT ads.*, 
  (SELECT COUNT(*) FROM ad_likes WHERE ad_id = ads.id) as likes_count, 
  views 
  FROM ads WHERE user_id = :user_id ORDER BY created_at DESC";
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
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- Added Font Awesome -->
</head>

<body>
  <div class="container my-5">
    <div class="card shadow-lg p-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary"><i class="fas fa-bullhorn"></i> Meus Anúncios</h2>
        <div>
          <a href="appointments_list.php" class="btn btn-success"><i class="fas fa-calendar-alt"></i> Meus
            Agendamentos</a> <!-- Added -->
          <a href="edit_profile.php" class="btn btn-secondary"><i class="fas fa-user-edit"></i> Editar Perfil</a>
          <!-- Icon added -->
          <a href="../logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Deslogar</a>
          <!-- Icon added -->
        </div>
      </div>
      <form method="POST" class="mb-3" enctype="multipart/form-data">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="title" class="form-label">Título:</label>
            <input type="text" class="form-control" id="title" name="title" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="category" class="form-label">Categoria:</label>
            <select class="form-select" id="category" name="category" required>
              <option value="">Selecione uma categoria</option>
              <option value="Terapia Individual">Terapia Individual</option>
              <option value="Terapia de Casal">Terapia de Casal</option>
              <option value="Terapia Familiar">Terapia Familiar</option>
              <option value="Psicanalise">Psicanálise</option>
              <option value="Terapia Cognitivo-Comportamental">Terapia Cognitivo-Comportamental</option>
              <option value="Aconselhamento">Aconselhamento</option>
              <option value="Outros">Outros</option>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <label for="content" class="form-label">Conteúdo:</label>
          <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
        </div>
        <div class="mb-3">
          <label for="tags" class="form-label">Tags (separadas por vírgula):</label>
          <input type="text" class="form-control" id="tags" name="tags"
            placeholder="Ex: ansiedade, depressão, autoestima">
        </div>
        <div class="mb-3">
          <label for="image" class="form-label">Imagem (opcional):</label>
          <input type="file" class="form-control" id="image" name="image" accept="image/*">
          <small class="text-muted">Tamanho máximo: 2MB. Formatos aceitos: JPG, PNG, GIF</small>
        </div>
        <button type="submit" class="btn btn-primary w-100 mb-2"><i class="fas fa-plus"></i> Postar Anúncio</button>
      </form>
      <h3><i class="fas fa-list"></i> Anúncios Postados</h3>
      <?php foreach ($ads as $ad): ?>
        <div class="card mb-3">
          <div class="card-body">
            <?php if ($ad['image_url']): ?>
              <img src="<?php echo htmlspecialchars('../' . $ad['image_url']); ?>" class="card-img-top mb-3"
                alt="Imagem do anúncio" style="max-height: 200px; object-fit: cover;">
            <?php endif; ?>
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h5 class="card-title"><?php echo htmlspecialchars($ad['title']); ?></h5>
              <span class="badge bg-primary"><?php echo htmlspecialchars($ad['category']); ?></span>
            </div>
            <p class="card-text"><?php echo htmlspecialchars($ad['content']); ?></p>
            <?php if ($ad['tags']): ?>
              <div class="mb-2">
                <?php foreach (explode(',', $ad['tags']) as $tag): ?>
                  <span class="badge bg-secondary me-1"><?php echo htmlspecialchars(trim($tag)); ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <div class="mb-2">
              <span class="badge bg-info"><i class="fas fa-eye"></i> <?php echo $ad['views']; ?> Visualizações</span>
              <span class="badge bg-success"><i class="fas fa-heart"></i> <?php echo $ad['likes_count']; ?> Likes</span>
            </div>
            <small class="text-muted">Postado em: <?php echo $ad['created_at']; ?></small>
            <form method="POST" class="mt-2" enctype="multipart/form-data">
              <input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>">
              <div class="row">
                <div class="col-md-6 mb-2">
                  <label for="title_<?php echo $ad['id']; ?>" class="form-label">Editar Título:</label>
                  <input type="text" class="form-control" id="title_<?php echo $ad['id']; ?>" name="title"
                    value="<?php echo htmlspecialchars($ad['title']); ?>" required>
                </div>
                <div class="col-md-6 mb-2">
                  <label for="category_<?php echo $ad['id']; ?>" class="form-label">Categoria:</label>
                  <select class="form-select" id="category_<?php echo $ad['id']; ?>" name="category" required>
                    <option value="">Selecione uma categoria</option>
                    <?php
                    $categories = [
                      'Terapia Individual',
                      'Terapia de Casal',
                      'Terapia Familiar',
                      'Psicanalise',
                      'Terapia Cognitivo-Comportamental',
                      'Aconselhamento',
                      'Outros'
                    ];
                    foreach ($categories as $cat):
                      $selected = $cat === $ad['category'] ? 'selected' : '';
                      ?>
                      <option value="<?php echo $cat; ?>" <?php echo $selected; ?>><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="mb-2">
                <label for="content_<?php echo $ad['id']; ?>" class="form-label">Editar Conteúdo:</label>
                <textarea class="form-control" id="content_<?php echo $ad['id']; ?>" name="content" rows="2"
                  required><?php echo htmlspecialchars($ad['content']); ?></textarea>
              </div>
              <div class="mb-2">
                <label for="tags_<?php echo $ad['id']; ?>" class="form-label">Tags (separadas por vírgula):</label>
                <input type="text" class="form-control" id="tags_<?php echo $ad['id']; ?>" name="tags"
                  value="<?php echo htmlspecialchars($ad['tags']); ?>" placeholder="Ex: ansiedade, depressão, autoestima">
              </div>
              <div class="mb-2">
                <label for="image_<?php echo $ad['id']; ?>" class="form-label">Nova Imagem (opcional):</label>
                <input type="file" class="form-control" id="image_<?php echo $ad['id']; ?>" name="image" accept="image/*">
                <small class="text-muted">Tamanho máximo: 2MB. Formatos aceitos: JPG, PNG, GIF</small>
              </div>
              <button type="submit" name="edit_ad" class="btn btn-warning mb-2"><i class="fas fa-edit"></i>
                Editar</button>
              <button type="submit" name="delete_ad" class="btn btn-danger mb-2"><i class="fas fa-trash"></i>
                Remover</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>

</html>