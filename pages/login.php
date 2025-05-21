<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Login</title>
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
    <div class="card p-4 w-100" style="max-width: 420px;">
      <h2 class="text-center mb-4"><i class="fas fa-sign-in-alt"></i> Login</h2>
      <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger" role="alert">
          <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
      <?php endif; ?>
      <form action="processa_login.php" method="POST">
        <div class="mb-3">
          <label for="email" class="form-label">E-mail:</label>
          <input type="email" class="form-control" id="email" name="email" placeholder="Digite seu e-mail" required>
          <?php if (isset($_GET['email_error'])): ?>
            <small class="text-danger"><?php echo htmlspecialchars($_GET['email_error']); ?></small>
          <?php endif; ?>
        </div>
        <div class="mb-3">
          <label for="senha" class="form-label">Senha:</label>
          <input type="password" class="form-control" id="senha" name="senha" placeholder="Digite sua senha" required>
          <?php if (isset($_GET['password_error'])): ?>
            <small class="text-danger"><?php echo htmlspecialchars($_GET['password_error']); ?></small>
          <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-sign-in-alt"></i> Entrar</button>
      </form>
      <p class="mt-3 text-center">
        Ainda não possui conta? <a href="cadastro.php">Cadastre-se</a>
      </p>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>