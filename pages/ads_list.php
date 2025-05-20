<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['type'] !== 'paciente') {
  header('Location: ./pages/login.php');
  exit;
}
require_once '../includes/config.php';

$filter = $_GET['filter'] ?? '';
$sql = "SELECT ads.*, users.nome AS psicologo_nome,
        CASE 
            WHEN (views + (COUNT(DISTINCT al.id) * 5)) > 100 THEN 'Alta'
            WHEN (views + (COUNT(DISTINCT al.id) * 5)) > 50 THEN 'Média'
            ELSE 'Baixa'
        END as popularidade,
        views,
        COUNT(DISTINCT al.id) as likes_count,
        MAX(CASE WHEN al.user_id = :current_user THEN 1 ELSE 0 END) as user_liked
        FROM ads 
        JOIN users ON ads.user_id = users.id 
        LEFT JOIN ad_likes al ON ads.id = al.ad_id
        WHERE users.type = 'psicologo'
        GROUP BY ads.id, ads.title, ads.content, ads.created_at, ads.user_id, ads.views, users.nome
        ORDER BY likes_count DESC";

$params = [];
if ($filter) {
  $sql .= " AND ads.title LIKE :filter";
  $params[':filter'] = "%$filter%";
}

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
</head>

<body>
  <div class="container">
    <!-- Toast Container -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
      <div id="toast-container"></div>
    </div>
    <!-- End Toast Container -->

    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
      <h2 class="mb-3"><i class="fas fa-bullhorn text-primary"></i> Anúncios de Psicólogos</h2>
      <div class="d-flex gap-2">
        <a href="appointments_list.php" class="btn btn-success"><i class="fas fa-calendar-alt"></i> Meus
          Agendamentos</a>
        <a href="edit_profile.php" class="btn btn-secondary"><i class="fas fa-user-edit"></i> Editar Perfil</a>
        <a href="chat.php" class="btn btn-primary"><i class="fas fa-comments"></i> Chat</a>
        <a href="../logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Deslogar</a>
      </div>
    </div>
    <form method="GET" class="mb-3">
      <div class="input-group">
        <span class="input-group-text"><i class="fas fa-filter"></i></span>
        <input type="text" class="form-control" id="filter" name="filter" placeholder="Filtrar por título"
          value="<?php echo htmlspecialchars($filter); ?>">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
      </div>
    </form>
    <h3 class="mt-4"><i class="fas fa-list"></i> Resultados</h3>
    <?php if (count($ads) > 0): ?>
      <?php foreach ($ads as $ad): ?>
        <div class="card mb-3 shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h5 class="card-title"><i class="fas fa-ad"></i> <?php echo htmlspecialchars($ad['title']); ?></h5>
              <div class="d-flex align-items-center">
                <button class="btn btn-sm me-2 <?php echo $ad['user_liked'] ? 'btn-danger' : 'btn-outline-danger'; ?>" 
                        onclick="toggleLike(<?php echo $ad['id']; ?>, this)" 
                        data-likes="<?php echo $ad['likes_count']; ?>">
                  <i class="fas fa-heart"></i> 
                  <span class="likes-count"><?php echo number_format($ad['likes_count']); ?></span>
                </button>
                <span class="badge <?php echo $ad['popularidade'] === 'Alta' ? 'bg-success' : ($ad['popularidade'] === 'Média' ? 'bg-warning' : 'bg-secondary'); ?> me-2">
                  <i class="fas fa-chart-line"></i> <?php echo $ad['popularidade']; ?>
                </span>
                <span class="badge bg-info">
                  <i class="fas fa-eye"></i> <?php echo number_format($ad['views']); ?> visualizações
                </span>
              </div>
            </div>
            <p class="card-text"><?php echo htmlspecialchars($ad['content']); ?></p>
            <small class="text-muted"><i class="fas fa-user"></i> Postado por:
              <?php echo htmlspecialchars($ad['psicologo_nome']); ?> em <?php echo $ad['created_at']; ?></small>
            <button class="btn btn-primary mt-2" onclick="showAvailability(<?php echo $ad['user_id']; ?>, <?php echo $ad['id']; ?>)">
              <i class="fas fa-calendar-plus"></i> Ver Disponibilidade
            </button>
          </div>
        </div>
      <?php endforeach; ?>
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
      <p class="text-muted"><i class="fas fa-info-circle"></i> Nenhum anúncio encontrado.</p>
    <?php endif; ?>
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
          data.forEach(slot => {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.innerHTML = `
                      <strong>${capitalize(slot.day_of_week)}</strong>: 
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