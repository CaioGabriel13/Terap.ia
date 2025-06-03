<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['type'] !== 'paciente') {
  header('Location: ./pages/login.php');
  exit;
}
require_once '../includes/config.php';

$filter = $_GET['filter'] ?? '';
$category = $_GET['category'] ?? '';
$tags = $_GET['tags'] ?? '';
$sql = "SELECT ads.*, users.nome AS psicologo_nome,
        CASE 
            WHEN ((views * 1) + (COUNT(DISTINCT al.id) * 10)) > 15 THEN 'Alta'
            WHEN ((views * 1) + (COUNT(DISTINCT al.id) * 10)) > 10 THEN 'Média'
            ELSE 'Baixa'
        END as popularidade,
        CASE
            WHEN COUNT(DISTINCT al.id) >= 6 THEN 'Altamente Recomendado'
            WHEN COUNT(DISTINCT al.id) >= 5 THEN 'Recomendado'
            WHEN COUNT(DISTINCT al.id) >= 1 THEN 'Bem Avaliado'
            ELSE 'Novo'
        END as recomendacao,
        views,
        COUNT(DISTINCT al.id) as likes_count,
        MAX(CASE WHEN al.user_id = :current_user THEN 1 ELSE 0 END) as user_liked,
        ((views * 1) + (COUNT(DISTINCT al.id) * 10)) as score
        FROM ads 
        JOIN users ON ads.user_id = users.id 
        LEFT JOIN ad_likes al ON ads.id = al.ad_id
        WHERE users.type = 'psicologo'";

$params = [];
if ($filter) {
  $sql .= " AND ads.title LIKE :filter";
  $params[':filter'] = "%$filter%";
}
if ($category) {
  $sql .= " AND ads.category = :category";
  $params[':category'] = $category;
}
if ($tags) {
  $sql .= " AND ads.tags LIKE :tags";
  $params[':tags'] = "%$tags%";
}

$sql .= " GROUP BY ads.id, ads.title, ads.content, ads.category, ads.tags, ads.created_at, ads.user_id, ads.views, users.nome
        ORDER BY score DESC, created_at DESC";

$stmt = $pdo->prepare($sql);
$params[':current_user'] = $_SESSION['usuario']['id'];
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
  <link rel="stylesheet" href="../assets/css/styles.css">
  <title>Anúncios de Psicólogos</title>
  <style>
    .metrics {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 15px;
    }

    .popularity-badge,
    .recommendation-badge {
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.9em;
    }

    .popularity-badge.alta {
      background: #4CAF50;
      color: white;
    }

    .popularity-badge.média {
      background: #FFC107;
      color: #000;
    }

    .popularity-badge.baixa {
      background: #9E9E9E;
      color: white;
    }

    .recommendation-badge {
      background: #E1F5FE;
      color: #0288D1;
    }

    .recommendation-badge.altamente-recomendado {
      background: #E8F5E9;
      color: #2E7D32;
    }

    .stats {
      display: flex;
      gap: 15px;
      align-items: center;
    }

    .stats span {
      display: flex;
      align-items: center;
      gap: 5px;
      color: #666;
    }

    .stats .liked {
      color: #F44336;
    }
  </style>
</head>

<body>
  <div class="container py-4">
    <!-- Toast Container -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
      <div id="toast-container"></div>
    </div>
    <!-- Header and Actions -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
      <h2 class="mb-3 mb-md-0 text-primary"><i class="fas fa-bullhorn me-2"></i>Anúncios de Psicólogos</h2>
      <div class="d-flex flex-wrap gap-2">
        <a href="appointments_list.php" class="btn btn-success"><i class="fas fa-calendar-alt me-1"></i>Meus
          Agendamentos</a>
        <a href="edit_profile.php" class="btn btn-secondary"><i class="fas fa-user-edit me-1"></i>Editar Perfil</a>
        <a href="chat.php" class="btn btn-primary"><i class="fas fa-comments me-1"></i>Chat</a>
        <a href="../logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt me-1"></i>Deslogar</a>
      </div>
    </div>
    <!-- Filters -->
    <form method="GET" class="mb-4">
      <div class="row g-2">
        <div class="col-md-4">
          <input type="text" class="form-control" name="filter" placeholder="Filtrar por título"
            value="<?php echo htmlspecialchars($filter); ?>">
        </div>
        <div class="col-md-4">
          <input type="text" class="form-control" name="category" placeholder="Filtrar por categoria"
            value="<?php echo htmlspecialchars($category); ?>">
        </div>
        <div class="col-md-3">
          <input type="text" class="form-control" name="tags" placeholder="Filtrar por tags"
            value="<?php echo htmlspecialchars($tags); ?>">
        </div>
        <div class="col-md-1 d-grid">
          <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
        </div>
      </div>
    </form>
    <!-- Ads List -->
    <h3 class="mb-3"><i class="fas fa-list me-2"></i>Resultados</h3>
    <div class="row gy-4">
      <?php if (count($ads) > 0): ?>
        <?php foreach ($ads as $ad): ?>
          <div class="col-12">
            <div class="card shadow-sm h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <h5 class="card-title text-primary"><i
                      class="fas fa-ad me-2"></i><?php echo htmlspecialchars($ad['title']); ?></h5>
                  <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm <?php echo $ad['user_liked'] ? 'btn-danger' : 'btn-outline-danger'; ?>"
                      onclick="toggleLike(<?php echo $ad['id']; ?>, this)">
                      <i class="fas fa-heart me-1"></i><span
                        class="likes-count"><?php echo number_format($ad['likes_count']); ?></span>
                    </button>
                    <span
                      class="badge <?php echo $ad['popularidade'] === 'Alta' ? 'bg-success' : ($ad['popularidade'] === 'Média' ? 'bg-warning' : 'bg-secondary'); ?>">
                      <i class="fas fa-chart-line me-1"></i><?php echo $ad['popularidade']; ?>
                    </span>
                    <span class="badge bg-info">
                      <i class="fas fa-eye me-1"></i><?php echo number_format($ad['views']); ?>
                    </span>
                  </div>
                </div>
                <div class="mb-2">
                  <span class="me-3"><strong>Categoria:</strong> <?php echo htmlspecialchars($ad['category']); ?></span>
                  <span><strong>Tags:</strong> <?php echo htmlspecialchars($ad['tags']); ?></span>
                </div>
                <p class="card-text mb-2"><?php echo htmlspecialchars($ad['content']); ?></p>
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                  <small class="text-muted"><i class="fas fa-user me-1"></i>Postado por:
                    <?php echo htmlspecialchars($ad['psicologo_nome']); ?> em <?php echo $ad['created_at']; ?></small>
                  <button class="btn btn-primary mt-2 mt-md-0"
                    onclick="showAvailability(<?php echo $ad['user_id']; ?>, <?php echo $ad['id']; ?>)"><i
                      class="fas fa-calendar-plus me-1"></i>Ver Disponibilidade</button>
                </div>
                <!-- Popularity and Recommendation Indicators -->
                <?php
                $popularityClass = strtolower($ad['popularidade']);
                $recommendationClass = str_replace(' ', '-', strtolower($ad['recomendacao']));
                ?>
                <div class="mt-3">
                  <span
                    class="badge rounded-pill text-bg-<?php echo $popularityClass === 'alta' ? 'success' : ($popularityClass === 'média' ? 'warning' : 'secondary'); ?>">
                    <i class="fas fa-chart-line me-1"></i><?php echo $ad['popularidade']; ?>
                  </span>
                  <span
                    class="badge rounded-pill text-bg-<?php echo $recommendationClass === 'altamente-recomendado' ? 'primary' : ($recommendationClass === 'recomendado' ? 'info' : 'light'); ?>">
                    <i class="fas fa-star me-1"></i><?php echo $ad['recomendacao']; ?>
                  </span>
                </div>
                <!-- Metrics Display -->
                <div class="metrics">
                  <div class="stat-item">
                    <span class="stat-label">Visualizações:</span>
                    <span class="stat-value"><?php echo number_format($ad['views']); ?></span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-label">Curtidas:</span>
                    <span class="stat-value liked"><?php echo number_format($ad['likes_count']); ?></span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-label">Popularidade:</span>
                    <span class="stat-value popularity-badge <?php echo strtolower($ad['popularidade']); ?>">
                      <?php echo $ad['popularidade']; ?>
                    </span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-label">Recomendação:</span>
                    <span
                      class="stat-value recommendation-badge <?php echo str_replace(' ', '-', strtolower($ad['recomendacao'])); ?>">
                      <?php echo $ad['recomendacao']; ?>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        <!-- Modal and JS remain unchanged -->
        <div id="availability-modal" class="modal fade" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Disponibilidade do Psicólogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <ul id="availability-list" class="list-group"></ul>
                <div id="time-slot-container" class="mt-3" style="display: none;">
                  <h6>Selecione um horário:</h6>
                  <select id="time-slot-select" class="form-select mb-3">
                    <option value="" selected>Não Selecionado</option>
                  </select>
                  <div id="date-input-container" class="mb-3" style="display: none;">
                    <label for="appointment-date" class="form-label">Selecione uma data:</label>
                    <input type="date" id="appointment-date" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                  </div>
                  <button id="confirm-appointment-btn" class="btn btn-primary" style="display: none;">Confirmar
                    Agendamento</button>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
              </div>
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="col-12">
          <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Nenhum anúncio encontrado.</div>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    let selectedPsychologistId = null;

    function scheduleAppointment(psychologistId, date, time) {
      if (!psychologistId || !date || !time) {
        showToast('Erro', 'Por favor, selecione uma data e horário.', 'danger');
        return;
      }

      const appointmentDate = `${date} ${time}:00`;

      fetch('appointments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ psychologist_id: psychologistId, appointment_date: appointmentDate })
      })
        .then(response => response.json())
        .then(data => {
          if (data.error) {
            showToast('Erro', data.error, 'danger');
          } else {
            showToast('Sucesso', data.success, 'success');
            setTimeout(() => location.reload(), 2000);
          }
        })
        .catch(error => {
          console.error('Erro ao agendar:', error);
          showToast('Erro', 'Erro ao agendar. Tente novamente mais tarde.', 'danger');
        });
    }

    function showToast(title, message, type) {
      const toastContainer = document.getElementById('toast-container');
      const toastId = `toast-${Date.now()}`;
      const toast = document.createElement('div');
      toast.className = `toast align-items-center text-bg-${type} border-0`;
      toast.id = toastId;
      toast.role = 'alert';
      toast.ariaLive = 'assertive';
      toast.ariaAtomic = 'true';
      toast.innerHTML = `
        <div class="d-flex">
          <div class="toast-body">
            <strong>${title}:</strong> ${message}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      `;
      toastContainer.appendChild(toast);

      const bootstrapToast = new bootstrap.Toast(toast);
      bootstrapToast.show();

      toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
      });
    }

    function incrementViews(adId) {
      fetch('increment_views.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ad_id: adId })
      });
    }

    function showAvailability(psychologistId, adId) {
      // Incrementa as visualizações quando o usuário clica para ver disponibilidade
      incrementViews(adId);
      selectedPsychologistId = psychologistId;

      fetch(`appointments.php?psychologist_id=${psychologistId}`)
        .then(response => response.json())
        .then(data => {
          const list = document.getElementById('availability-list');
          list.innerHTML = '';
          if (data.length === 0) {
            list.innerHTML = '<li class="list-group-item">Nenhuma disponibilidade encontrada.</li>';
            return;
          }
          // Dias da semana em português
          const daysOfWeek = {
            'monday': 'Segunda-feira',
            'tuesday': 'Terça-feira',
            'wednesday': 'Quarta-feira',
            'thursday': 'Quinta-feira',
            'friday': 'Sexta-feira',
            'saturday': 'Sábado',
            'sunday': 'Domingo'
          };
          data.forEach(slot => {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.innerHTML = `
                      <strong>${daysOfWeek[slot.day_of_week.toLowerCase()] || capitalize(slot.day_of_week)}</strong>: 
                      ${slot.hour_start} - ${slot.hour_end} 
                      (R$ ${parseFloat(slot.price).toFixed(2)})
                      <button class="btn btn-sm btn-primary float-end" onclick="selectDay('${slot.day_of_week}', '${slot.hour_start}', '${slot.hour_end}')">
                          Selecionar Dia
                      </button>
                  `;
            list.appendChild(li);
          });
          const modal = new bootstrap.Modal(document.getElementById('availability-modal'));
          modal.show();
        })
        .catch(error => {
          console.error('Erro ao buscar disponibilidade:', error);
          showToast('Erro', 'Erro ao buscar disponibilidade. Tente novamente mais tarde.', 'danger');
        });
    }

    function selectDay(dayOfWeek, hourStart, hourEnd) {
      const timeSlotContainer = document.getElementById('time-slot-container');
      const timeSlotSelect = document.getElementById('time-slot-select');
      const dateInputContainer = document.getElementById('date-input-container');
      const confirmButton = document.getElementById('confirm-appointment-btn');

      timeSlotContainer.style.display = 'block';
      timeSlotSelect.innerHTML = '<option value="" selected>Não Selecionado</option>';
      dateInputContainer.style.display = 'none';
      confirmButton.style.display = 'none';

      const startTime = new Date(`1970-01-01T${hourStart}`);
      const endTime = new Date(`1970-01-01T${hourEnd}`);
      while (startTime < endTime) {
        const option = document.createElement('option');
        option.value = startTime.toTimeString().slice(0, 5);
        option.textContent = startTime.toTimeString().slice(0, 5);
        timeSlotSelect.appendChild(option);
        startTime.setHours(startTime.getHours() + 1);
      }

      timeSlotSelect.onchange = () => {
        if (timeSlotSelect.value) {
          dateInputContainer.style.display = 'block';
          confirmButton.style.display = 'block';
        } else {
          dateInputContainer.style.display = 'none';
          confirmButton.style.display = 'none';
        }
      };

      confirmButton.onclick = () => {
        const selectedTime = timeSlotSelect.value;
        const selectedDate = document.getElementById('appointment-date').value;

        if (!selectedTime) {
          showToast('Erro', 'Por favor, selecione um horário.', 'danger');
          return;
        }

        if (!selectedDate) {
          showToast('Erro', 'Por favor, selecione uma data.', 'danger');
          return;
        }

        const currentDate = new Date().toISOString().split('T')[0];
        if (selectedDate < currentDate) {
          showToast('Erro', 'Você não pode agendar uma consulta para uma data passada.', 'danger');
          return;
        }

        scheduleAppointment(selectedPsychologistId, selectedDate, selectedTime);
      };
    }

    function capitalize(str) {
      return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function toggleLike(adId, button) {
      fetch('toggle_like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ad_id: adId })
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const likesCount = button.querySelector('.likes-count');
            likesCount.textContent = data.likes;

            if (data.action === 'liked') {
              button.classList.remove('btn-outline-danger');
              button.classList.add('btn-danger');
              showToast('Sucesso', 'Você curtiu este anúncio!', 'success');
            } else {
              button.classList.remove('btn-danger');
              button.classList.add('btn-outline-danger');
              showToast('Sucesso', 'Você removeu sua curtida!', 'info');
            }
          } else {
            showToast('Erro', 'Erro ao processar sua curtida', 'danger');
          }
        })
        .catch(error => {
          console.error('Erro ao processar like:', error);
          showToast('Erro', 'Erro ao processar sua curtida', 'danger');
        });
    }
  </script>
</body>

</html>