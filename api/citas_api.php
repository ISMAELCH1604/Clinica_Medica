<?php
require_once '../conexion.php';
require_once '../includes/seguridad.php';
header('Content-Type: application/json; charset=utf-8');

requerirLoginAPI();

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

try {
    switch ($accion) {

        case 'listar':
            $filtro = $_GET['filtro'] ?? 'todas';
            $where  = '';
            if ($filtro === 'hoy')             $where = "WHERE DATE(c.fecha_hora) = CURDATE()";
            elseif ($filtro === 'programadas') $where = "WHERE c.estado = 'programada'";
            elseif ($filtro === 'completadas') $where = "WHERE c.estado = 'completada'";

            $sql = "
                SELECT c.id_cita, c.fecha_hora, c.estado,
                       CONCAT(p.nombre,' ',p.apellido) AS paciente,
                       CONCAT(o.nombre,' ',o.apellido) AS odontologo,
                       o.especialidad, cs.nombre AS consultorio
                FROM citas c
                INNER JOIN pacientes   p  ON p.id_paciente    = c.id_paciente
                INNER JOIN odontologos o  ON o.id_odontologo  = c.id_odontologo
                INNER JOIN consultorios cs ON cs.id_consultorio = c.id_consultorio
                $where
                ORDER BY c.fecha_hora DESC
                LIMIT 200
            ";
            echo json_encode(['ok' => true, 'datos' => $pdo->query($sql)->fetchAll()]);
            break;

        case 'opciones':
            $pacientes    = $pdo->query("SELECT id_paciente AS id, CONCAT(nombre,' ',apellido) AS nombre FROM pacientes ORDER BY apellido")->fetchAll();
            $odontologos  = $pdo->query("SELECT id_odontologo AS id, CONCAT(nombre,' ',apellido,' — ',especialidad) AS nombre FROM odontologos ORDER BY apellido")->fetchAll();
            $consultorios = $pdo->query("SELECT id_consultorio AS id, nombre FROM consultorios WHERE activo = TRUE ORDER BY nombre")->fetchAll();
            echo json_encode([
                'ok' => true,
                'pacientes' => $pacientes,
                'odontologos' => $odontologos,
                'consultorios' => $consultorios
            ]);
            break;

        case 'agendar':
            $fecha = $_POST['fecha_hora'] ?? '';
            if (strtotime($fecha) < time()) {
                echo json_encode(['ok' => false, 'error' => 'La fecha debe ser futura.']);
                break;
            }

            $pdo->prepare("SET @usuario_actual = ?")->execute([$_SESSION['id_usuario']]);

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
                echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'cambiar_estado':
            $id    = (int)($_POST['id_cita'] ?? 0);
            $nuevo = $_POST['nuevo_estado'] ?? '';

            if (!in_array($nuevo, ['completada','cancelada','no_asistio'])) {
                echo json_encode(['ok' => false, 'error' => 'Estado no válido.']);
                break;
            }

            $pdo->prepare("SET @usuario_actual = ?")->execute([$_SESSION['id_usuario']]);

            try {
                $stmt = $pdo->prepare("UPDATE citas SET estado = ? WHERE id_cita = ?");
                $stmt->execute([$nuevo, $id]);
                echo json_encode(['ok' => true, 'mensaje' => 'Estado actualizado']);
            } catch (PDOException $e) {
                echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            }
            break;
        ##
                // ---- GUARDAR EXPEDIENTE CLÍNICO ----
        case 'guardar_expediente':
            $id_cita = (int)$_POST['id_cita'];
            $diagnostico = trim($_POST['diagnostico']);
            $historial = trim($_POST['historial']);

            if ($id_cita <= 0 || $diagnostico === '') {
                echo json_encode(['ok' => false, 'error' => 'El diagnóstico es obligatorio.']);
                break;
            }

            // Verificar si ya existe para actualizarlo o crear uno nuevo
            $stmt = $pdo->prepare("SELECT id_expediente FROM expedientes_clinicos WHERE id_cita = ?");
            $stmt->execute([$id_cita]);
            $existe = $stmt->fetchColumn();

            try {
                if ($existe) {
                    $stmt = $pdo->prepare("UPDATE expedientes_clinicos SET diagnostico = ?, historial = ? WHERE id_cita = ?");
                    $stmt->execute([$diagnostico, $historial, $id_cita]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO expedientes_clinicos (id_cita, diagnostico, historial) VALUES (?, ?, ?)");
                    $stmt->execute([$id_cita, $diagnostico, $historial]);
                }
                echo json_encode(['ok' => true, 'mensaje' => 'Expediente guardado correctamente']);
            } catch (PDOException $e) {
                echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            }
            break;

        // ---- OBTENER EXPEDIENTE DE UNA CITA ----
        case 'obtener_expediente_cita':
            $id_cita = (int)$_GET['id_cita'];
            $stmt = $pdo->prepare("SELECT diagnostico, historial FROM expedientes_clinicos WHERE id_cita = ?");
            $stmt->execute([$id_cita]);
            $expediente = $stmt->fetch();
            echo json_encode(['ok' => true, 'expediente' => $expediente ?: null]);
            break;
        ##
                // ---- OBTENER LOS TRATAMIENTOS DE UNA CITA ----
        case 'obtener_tratamientos':
            $id_cita = (int)$_GET['id_cita'];
            
            // Traer todos los tratamientos disponibles para el select
            $tratamientos = $pdo->query("SELECT id_tratamiento, nombre, costo FROM tratamientos ORDER BY nombre")->fetchAll();
            
            // Traer los tratamientos que YA tiene esta cita
            $stmt = $pdo->prepare("SELECT id_tratamiento FROM detalle_tratamiento_cita WHERE id_cita = ?");
            $stmt->execute([$id_cita]);
            $asignados = $stmt->fetchAll(PDO::FETCH_COLUMN);

            echo json_encode(['ok' => true, 'tratamientos' => $tratamientos, 'asignados' => $asignados]);
            break;

        // ---- GUARDAR TRATAMIENTOS EN UNA CITA ----
        case 'guardar_tratamientos':
            $id_cita = (int)$_POST['id_cita'];
            $tratamientos = $_POST['tratamientos'] ?? []; // Array con los IDs

            try {
                $pdo->beginTransaction();

                // 1. Borrar los tratamientos que tenía (para actualizar)
                $stmt = $pdo->prepare("DELETE FROM detalle_tratamiento_cita WHERE id_cita = ?");
                $stmt->execute([$id_cita]);

                // 2. Insertar los nuevos
                if (!empty($tratamientos)) {
                    $stmt = $pdo->prepare("INSERT INTO detalle_tratamiento_cita (id_cita, id_tratamiento) VALUES (?, ?)");
                    foreach ($tratamientos as $id_trat) {
                        $stmt->execute([$id_cita, (int)$id_trat]);
                    }
                }

                $pdo->commit();
                echo json_encode(['ok' => true, 'mensaje' => 'Tratamientos guardados correctamente']);
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            }
            break;
        ##
                // ---- OBTENER DISPONIBILIDAD DE HORARIOS ----
        case 'disponibilidad':
            $fecha = $_GET['fecha'] ?? date('Y-m-d');
            $id_consultorio = (int)$_GET['id_consultorio'];

            // 1. Definir los bloques de horario de la clínica (8 AM a 5 PM)
            $horariosBase = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
            
            // 2. Buscar las citas ya agendadas para ese consultorio en esa fecha
            // Usamos TIME_FORMAT para que devuelva solo "HH:MM" y no los segundos
            $stmt = $pdo->prepare("
                SELECT TIME_FORMAT(fecha_hora, '%H:%i') AS hora_ocupada 
                FROM citas 
                WHERE id_consultorio = ? 
                  AND DATE(fecha_hora) = ? 
                  AND estado != 'cancelada'
            ");
            $stmt->execute([$id_consultorio, $fecha]);
            $ocupados = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // 3. Armar la respuesta
            $disponibilidad = [];
            foreach ($horariosBase as $hora) {
                $disponibilidad[] = [
                    'hora' => $hora,
                    // Si la hora está en la lista de ocupados, disponible = false
                    'disponible' => !in_array($hora, $ocupados)
                ];
            }

            echo json_encode(['ok' => true, 'disponibilidad' => $disponibilidad]);
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}