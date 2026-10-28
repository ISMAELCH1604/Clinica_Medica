<?php
require_once '../conexion.php';
require_once '../includes/seguridad.php';
header('Content-Type: application/json; charset=utf-8');

// SOLO el administrador puede gestionar usuarios
requerirRolAPI(['administrador']);

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

try {
    switch ($accion) {

        case 'listar':
            $sql = "
                SELECT u.id_usuario, u.nombre_usuario, u.rol, u.activo, u.fecha_creacion,
                       CONCAT(p.nombre, ' ', p.apellido) AS paciente,
                       CONCAT(o.nombre, ' ', o.apellido) AS odontologo
                FROM usuarios u
                LEFT JOIN pacientes p ON p.id_paciente = u.id_paciente
                LEFT JOIN odontologos o ON o.id_odontologo = u.id_odontologo
                ORDER BY u.id_usuario ASC
            ";
            echo json_encode(['ok' => true, 'datos' => $pdo->query($sql)->fetchAll()]);
            break;

        case 'opciones':
            // Traer pacientes y odontólogos para vincularlos (opcional)
            $pacientes = $pdo->query("SELECT id_paciente AS id, CONCAT(nombre,' ',apellido) AS nombre FROM pacientes")->fetchAll();
            $odontologos = $pdo->query("SELECT id_odontologo AS id, CONCAT(nombre,' ',apellido) AS nombre FROM odontologos")->fetchAll();
            echo json_encode(['ok' => true, 'pacientes' => $pacientes, 'odontologos' => $odontologos]);
            break;

        case 'crear':
            $usuario = trim($_POST['nombre_usuario'] ?? '');
            $password = $_POST['password'] ?? '';
            $rol = $_POST['rol'] ?? '';
            $id_paciente = !empty($_POST['id_paciente']) ? (int)$_POST['id_paciente'] : null;
            $id_odontologo = !empty($_POST['id_odontologo']) ? (int)$_POST['id_odontologo'] : null;

            if ($usuario === '' || $password === '' || $rol === '') {
                echo json_encode(['ok' => false, 'error' => 'Usuario, contraseña y rol son obligatorios.']);
                break;
            }

            // Encriptar contraseña de forma segura
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("
                INSERT INTO usuarios (id_paciente, id_odontologo, nombre_usuario, password_hash, rol, activo)
                VALUES (?, ?, ?, ?, ?, TRUE)
            ");
            $stmt->execute([$id_paciente, $id_odontologo, $usuario, $hash, $rol]);
            
            echo json_encode(['ok' => true, 'mensaje' => 'Usuario creado correctamente']);
            break;

        case 'cambiar_estado':
            $id = (int)($_POST['id_usuario'] ?? 0);
            $nuevo_estado = (int)($_POST['activo'] ?? 1);

            // Evitar que el admin se desactive a sí mismo
            if ($id === (int)$_SESSION['id_usuario'] && $nuevo_estado === 0) {
                echo json_encode(['ok' => false, 'error' => 'No puedes desactivar tu propio usuario.']);
                break;
            }

            $pdo->prepare("UPDATE usuarios SET activo = ? WHERE id_usuario = ?")->execute([$nuevo_estado, $id]);
            echo json_encode(['ok' => true, 'mensaje' => 'Estado actualizado']);
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    $msg = $e->getMessage();
    if (str_contains($msg, 'Duplicate entry')) {
        $msg = 'El nombre de usuario ya está en uso.';
    }
    echo json_encode(['ok' => false, 'error' => $msg]);
}