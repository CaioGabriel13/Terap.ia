<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access. Please log in.']);
    exit;
}

$user = $_SESSION['usuario'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_appointment'])) {
            $appointment_id = $_POST['appointment_id'] ?? null;

            if (!$appointment_id) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Missing appointment ID.']);
                exit;
            }

            // Check if the user is authorized to delete the appointment
            $sql = "SELECT * FROM appointments WHERE id = :appointment_id AND 
                    (psychologist_id = :user_id OR patient_id = :user_id)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':appointment_id' => $appointment_id, ':user_id' => $user['id']]);
            $appointment = $stmt->fetch();

            if (!$appointment) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Unauthorized or appointment not found.']);
                exit;
            }

            // Delete the appointment
            $sql = "DELETE FROM appointments WHERE id = :appointment_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':appointment_id' => $appointment_id]);

            header('Location: appointments_list.php');
            exit;
        }

        if ($user['type'] !== 'paciente') {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Only patients can schedule appointments.']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $psychologist_id = $data['psychologist_id'] ?? null;
        $appointment_date = $data['appointment_date'] ?? null;

        if (!$psychologist_id || !$appointment_date) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Missing required parameters.']);
            exit;
        }

        // Ensure the appointment is exactly one hour
        $appointment_end_time = date('Y-m-d H:i:s', strtotime($appointment_date . ' +1 hour'));

        // Validate the format of the appointment date
        if (!DateTime::createFromFormat('Y-m-d H:i:s', $appointment_date)) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid date format. Please use YYYY-MM-DD HH:MM:SS.']);
            exit;
        }

        // Validate that the appointment date is not in the past
        $current_time = date('Y-m-d H:i:s');
        if ($appointment_date < $current_time) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'You cannot schedule an appointment in the past.']);
            exit;
        }

        // Check if the psychologist is available
        $sql = "SELECT * FROM availability 
                WHERE user_id = :psychologist_id 
                AND day_of_week = LOWER(DAYNAME(:appointment_date))
                AND :appointment_date >= CONCAT(DATE(:appointment_date), ' ', hour_start) 
                AND :appointment_end_time <= CONCAT(DATE(:appointment_date), ' ', hour_end)
                AND unavailable = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':psychologist_id' => $psychologist_id,
            ':appointment_date' => $appointment_date,
            ':appointment_end_time' => $appointment_end_time
        ]);
        $availability = $stmt->fetch();

        if (!$availability) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'The psychologist is not available at this time.']);
            exit;
        }

        // Check if the slot is already booked
        $sql = "SELECT * FROM appointments 
                WHERE psychologist_id = :psychologist_id 
                AND appointment_date < :appointment_end_time
                AND DATE_ADD(appointment_date, INTERVAL 1 HOUR) > :appointment_date";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':psychologist_id' => $psychologist_id,
            ':appointment_date' => $appointment_date,
            ':appointment_end_time' => $appointment_end_time
        ]);
        $existingAppointment = $stmt->fetch();

        if ($existingAppointment) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'This time slot is already booked.']);
            exit;
        }

        // Create the appointment
        $sql = "INSERT INTO appointments (psychologist_id, patient_id, appointment_date) 
                VALUES (:psychologist_id, :patient_id, :appointment_date)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':psychologist_id' => $psychologist_id,
            ':patient_id' => $user['id'],
            ':appointment_date' => $appointment_date
        ]);

        header('Content-Type: application/json');
        echo json_encode(['success' => 'Appointment scheduled successfully.']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['psychologist_id'])) {
        $psychologist_id = (int) $_GET['psychologist_id'];

        // Fetch availability for the psychologist
        $sql = "SELECT day_of_week, hour_start, hour_end, price 
                FROM availability 
                WHERE user_id = :psychologist_id AND unavailable = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':psychologist_id' => $psychologist_id]);
        $availability = $stmt->fetchAll();

        header('Content-Type: application/json');
        echo json_encode($availability);
        exit;
    }
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'An unexpected error occurred: ' . $e->getMessage()]);
    exit;
}
?>

<body>
    <div class="container my-5">
        <div class="card shadow-lg p-4">
            <h2 class="text-center text-primary mb-4"><i class="fas fa-calendar-alt"></i> Gerenciar Agendamentos</h2>
        </div>
    </div>
</body>