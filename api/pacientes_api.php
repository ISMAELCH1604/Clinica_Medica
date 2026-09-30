<?php
require_once '../conexion.php';
require_once '../includes/seguridad.php';
header('Content-Type: application/json; charset=utf-8');

requerirLoginAPI();

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

try {
    switch ($accion) {

        case 'listar':
            $buscar = '%' . ($_GET['buscar'] ?? '') . '%';
            $stmt = $pdo->prepare("
                SELECT id_paciente, nombre, apellido, dpi, fecha_nacimiento,
                       telefono, correo, direccion, fecha_registro
                FROM pacientes
                WHERE CONCAT(nombre, ' ', apellido) LIKE ?
                   OR dpi LIKE ?
                   OR telefono LIKE ?
                ORDER BY apellido, nombre
            ");
            $stmt->execute([$buscar, $buscar, $buscar]);
            echo json_encode(['ok' => true, 'datos' => $stmt->fetchAll()]);
            break;

        case 'obtener':
            $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE id_paciente = ?");
            $stmt->execute([$_GET['id'] ?? 0]);
            $p = $stmt->fetch();
            echo json_encode($p ? ['ok' => true, 'dato' => $p] : ['ok' => false, 'error' => 'No encontrado']);
            break;

        case 'crear':
            $stmt = $pdo->prepare("
                INSERT INTO pacientes
                    (nombre, apellido, dpi, fecha_nacimiento, telefono, correo, direccion)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                trim($_POST['nombre']),
                trim($_POST['apellido']),
                trim($_POST['dpi']),
                $_POST['fecha_nacimiento'],
                $_POST['telefono'] ?: null,
                $_POST['correo'] ?: null,
                $_POST['direccion'] ?: null,
            ]);
            echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId(), 'mensaje' => 'Paciente creado']);
            break;

        case 'actualizar':
            $stmt = $pdo->prepare("
                UPDATE pacientes SET
                    nombre = ?, apellido = ?, dpi = ?, fecha_nacimiento = ?,
                    telefono = ?, correo = ?, direccion = ?
                WHERE id_paciente = ?
            ");
            $stmt->execute([
                trim($_POST['nombre']),
                trim($_POST['apellido']),
                trim($_POST['dpi']),
                $_POST['fecha_nacimiento'],
                $_POST['telefono'] ?: null,
                $_POST['correo'] ?: null,
                $_POST['direccion'] ?: null,
                $_POST['id_paciente'],
            ]);
            echo json_encode(['ok' => true, 'mensaje' => 'Paciente actualizado']);
            break;

        case 'eliminar':
            $id = $_POST['id_paciente'] ?? 0;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE id_paciente = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode([
                    'ok' => false,
                    'error' => 'No se puede eliminar: el paciente tiene citas registradas.'
                ]);
                break;
            }

            $stmt = $pdo->prepare("DELETE FROM pacientes WHERE id_paciente = ?");
            $stmt->execute([$id]);
            echo json_encode(['ok' => true, 'mensaje' => 'Paciente eliminado']);
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    $msg = $e->getMessage();
    if (str_contains($msg, 'Duplicate entry')) {
        $msg = 'Ya existe un paciente con ese DPI o correo.';
    }
    echo json_encode(['ok' => false, 'error' => $msg]);
}