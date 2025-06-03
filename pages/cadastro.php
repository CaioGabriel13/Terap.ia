<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Cadastro de Usuário</title>
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
      <h2 class="text-center mb-4"><i class="fas fa-user-plus"></i> Cadastro de Usuário</h2>
      <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger" role="alert">
          <?= htmlspecialchars($_GET['error']) ?>
        </div>
      <?php endif; ?>
      <form action="processa_cadastro.php" method="POST" onsubmit="return validateForm()">
        <div class="mb-3">
          <label for="nome" class="form-label">Nome:</label>
          <input type="text" class="form-control" id="nome" name="nome" placeholder="Digite seu nome completo" required>
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">E-mail:</label>
          <input type="email" class="form-control" id="email" name="email" placeholder="Digite seu e-mail" required>
        </div>
        <div class="mb-3">
          <label for="senha" class="form-label">Senha:</label>
          <input type="password" class="form-control" id="senha" name="senha" placeholder="Crie uma senha" required>
        </div>
        <button type="submit" class="btn btn-primary w-100 mt-2"><i class="fas fa-user-plus"></i> Cadastrar</button>
      </form>
      <p class="mt-3 text-center">
        Já tem uma conta? <a href="login.php">Faça o login</a>
      </p>
    </div>
  </div>
  <script>
    function validateForm() {
      const nome = document.getElementById('nome').value.trim();
      const email = document.getElementById('email').value.trim();
      const senha = document.getElementById('senha').value.trim();
      if (!nome || !email || !senha) {
        alert('Todos os campos são obrigatórios.');
        return false;
      }
      if (senha.length < 6) {
        alert('A senha deve ter pelo menos 6 caracteres.');
        return false;
      }
      return true;
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>