<?php
session_start();
if (!isset($_SESSION['usuario'])) {
  header('Location: ./pages/login.php');
  exit;
}

require_once '../includes/config.php';

$user = $_SESSION['usuario'];

// Fetch appointments for the logged-in user
if ($user['type'] === 'paciente') {
  $sql = "SELECT DISTINCT a.*, u.nome AS psychologist_name, av.price 
            FROM appointments a
            JOIN users u ON a.psychologist_id = u.id
            LEFT JOIN availability av ON av.user_id = a.psychologist_id
            WHERE a.patient_id = :user_id";
} else {
  $sql = "SELECT a.*, u.nome AS patient_name 
            FROM appointments a
            JOIN users u ON a.patient_id = u.id
            WHERE a.psychologist_id = :user_id";
}

$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user['id']]);
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Meus Agendamentos</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
  <div class="container my-5">
    <div class="card shadow-lg p-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary"><i class="fas fa-calendar-alt"></i> Meus Agendamentos</h2>
        <a href="<?php echo $user['type'] === 'paciente' ? 'ads_list.php' : 'ads.php'; ?>" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Voltar
        </a>
      </div>
      <ul id="appointments-list" class="list-group">
        <?php if (count($appointments) > 0): ?>
          <?php foreach ($appointments as $appointment): ?>
            <li class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center">
              <div>
                <span class="fw-bold">Data:</span> <?php echo $appointment['appointment_date']; ?>,
                <?php if ($user['type'] === 'paciente'): ?>
                  <span class="fw-bold">Psicólogo:</span> <?php echo htmlspecialchars($appointment['psychologist_name']); ?>,
                  <span class="fw-bold">Preço:</span> R$ <?php echo number_format($appointment['price'], 2, ',', '.'); ?>
                <?php else: ?>
                  <span class="fw-bold">Paciente:</span> <?php echo htmlspecialchars($appointment['patient_name']); ?>
                <?php endif; ?>
              </div>
              <form method="POST" action="appointments.php" class="d-inline-block mt-2 mt-md-0 ms-md-2">
                <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                <button type="submit" name="delete_appointment" class="btn btn-danger btn-sm">
                  <i class="fas fa-trash"></i> Deletar
                </button>
              </form>
            </li>
          <?php endforeach; ?>
        <?php else: ?>
          <li class="list-group-item text-muted">Nenhum agendamento encontrado.</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>