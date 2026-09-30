<?php
session_start();
require_once '../conexion.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

try {
    switch ($accion) {

        // ---- LISTAR citas con detalle ----
        case 'listar':
            $filtro = $_GET['filtro'] ?? 'todas';
            $where  = '';
            if ($filtro === 'hoy') {
                $where = "WHERE DATE(c.fecha_hora) = CURDATE()";
            } elseif ($filtro === 'programadas') {
                $where = "WHERE c.estado = 'programada'";
            } elseif ($filtro === 'completadas') {
                $where = "WHERE c.estado = 'completada'";
            }

            $sql = "
                SELECT c.id_cita, c.fecha_hora, c.estado,
                       CONCAT(p.nombre,' ',p.apellido) AS paciente,
                       CONCAT(o.nombre,' ',o.apellido) AS odontologo,
                       o.especialidad, cs.nombre AS consultorio
                FROM citas c
                JOIN pacientes   p  ON p.id_paciente   = c.id_paciente
                JOIN odontologos o  ON o.id_odontologo = c.id_odontologo
                JOIN consultorios cs ON cs.id_consultorio = c.id_consultorio
                $where
                ORDER BY c.fecha_hora DESC
                LIMIT 200
            ";
            echo json_encode(['ok' => true, 'datos' => $pdo->query($sql)->fetchAll()]);
            break;

        // ---- Listas auxiliares para los selects ----
        case 'opciones':
            $pacientes   = $pdo->query("SELECT id_paciente AS id, CONCAT(nombre,' ',apellido) AS nombre FROM pacientes ORDER BY apellido")->fetchAll();
            $odontologos = $pdo->query("SELECT id_odontologo AS id, CONCAT(nombre,' ',apellido,' — ',especialidad) AS nombre FROM odontologos ORDER BY apellido")->fetchAll();
            $consultorios = $pdo->query("SELECT id_consultorio AS id, nombre FROM consultorios WHERE activo = TRUE ORDER BY nombre")->fetchAll();
            echo json_encode(['ok' => true, 'pacientes' => $pacientes, 'odontologos' => $odontologos, 'consultorios' => $consultorios]);
            break;

        // ---- AGENDAR cita llamando al procedimiento almacenado ----
        case 'agendar':
            // Validaciones básicas
            $fecha = $_POST['fecha_hora'] ?? '';
            if (strtotime($fecha) < time()) {
                echo json_encode(['ok' => false, 'error' => 'La fecha debe ser futura.']);
                break;
            }

            try {
                $stmt = $pdo->prepare("CALL sp_agendar_cita(?, ?, ?, ?)");
                $stmt->execute([
                    (int)$_POST['id_paciente'],
                    (int)$_POST['id_odontologo'],
                    (int)$_POST['id_consultorio'],
                    $fecha
                ]);
                $res = $stmt->fetch();
                echo json_encode(['ok' => true, 'id_cita' => $res['id_cita_creada'], 'mensaje' => 'Cita agendada']);
            } catch (PDOException $e) {
                // El procedimiento lanza SIGNAL con mensajes legibles
                echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            }
            break;

        // ---- CAMBIAR ESTADO (dispara triggers de auditoría) ----
        case 'cambiar_estado':
            $id     = (int)($_POST['id_cita'] ?? 0);
            $nuevo  = $_POST['nuevo_estado'] ?? '';

            if (!in_array($nuevo, ['completada','cancelada','no_asistio'])) {
                echo json_encode(['ok' => false, 'error' => 'Estado no válido.']);
                break;
            }

            // Setear el usuario en la sesión para que el trigger de auditoría registre quién lo hizo
            $stmt = $pdo->prepare("SET @usuario_actual = ?");
            $stmt->execute([$_SESSION['id_usuario']]);

            try {
                $stmt = $pdo->prepare("UPDATE citas SET estado = ? WHERE id_cita = ?");
                $stmt->execute([$nuevo, $id]);
                echo json_encode(['ok' => true, 'mensaje' => 'Estado actualizado']);
            } catch (PDOException $e) {
                echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
    }
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}