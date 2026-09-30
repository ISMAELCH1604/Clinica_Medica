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

        // ---- LISTAR con saldo ----
        case 'listar':
            $filtro = $_GET['filtro'] ?? 'todas';
            $where = '';
            if ($filtro === 'pendientes')      $where = "WHERE f.estado = 'pendiente'";
            elseif ($filtro === 'pagadas')     $where = "WHERE f.estado = 'pagada'";
            elseif ($filtro === 'anuladas')    $where = "WHERE f.estado = 'anulada'";

            $sql = "
                SELECT f.id_factura,
                    f.id_cita,
                    f.fecha_emision,
                    f.total,
                    COALESCE(SUM(p.monto), 0) AS pagado,
                    f.total - COALESCE(SUM(p.monto), 0) AS saldo,
                    f.estado AS estado_factura,
                    CONCAT(pa.nombre, ' ', pa.apellido) AS paciente,
                    DATE(c.fecha_hora) AS fecha_cita
                FROM facturas f
                INNER JOIN citas c      ON c.id_cita = f.id_cita
                INNER JOIN pacientes pa ON pa.id_paciente = c.id_paciente
                LEFT  JOIN pagos p      ON p.id_factura = f.id_factura
                $where
                GROUP BY f.id_factura,
                        f.id_cita,
                        f.fecha_emision,
                        f.total,
                        f.estado,
                        pa.nombre,
                        pa.apellido,
                        c.fecha_hora
                ORDER BY f.fecha_emision DESC
                LIMIT 200
            ";
            echo json_encode(['ok' => true, 'datos' => $pdo->query($sql)->fetchAll()]);
            break;

        // ---- Citas sin factura y con tratamientos asignados ----
        case 'citas_sin_factura':
            $sql = "
                SELECT c.id_cita, c.fecha_hora, c.estado,
                       CONCAT(p.nombre,' ',p.apellido) AS paciente,
                       COALESCE(SUM(t.costo),0) AS total_estimado,
                       COUNT(d.id_detalle) AS num_tratamientos
                FROM citas c
                JOIN pacientes p ON p.id_paciente = c.id_paciente
                LEFT JOIN detalle_tratamiento_cita d ON d.id_cita = c.id_cita
                LEFT JOIN tratamientos t ON t.id_tratamiento = d.id_tratamiento
                LEFT JOIN facturas f ON f.id_cita = c.id_cita
                WHERE f.id_factura IS NULL
                  AND c.estado IN ('programada','completada')
                GROUP BY c.id_cita, c.fecha_hora, c.estado, p.nombre, p.apellido
                HAVING num_tratamientos > 0
                ORDER BY c.fecha_hora DESC
            ";
            echo json_encode(['ok' => true, 'datos' => $pdo->query($sql)->fetchAll()]);
            break;

        // ---- EMITIR factura desde cita (usa procedimiento almacenado) ----
        case 'emitir':
            $id_cita = (int)($_POST['id_cita'] ?? 0);

            // Setear usuario para auditoría
            $pdo->prepare("SET @usuario_actual = ?")->execute([$_SESSION['id_usuario']]);

            try {
                $stmt = $pdo->prepare("CALL sp_emitir_factura_desde_cita(?)");
                $stmt->execute([$id_cita]);
                $res = $stmt->fetch();
                echo json_encode([
                    'ok' => true,
                    'id_factura' => $res['id_factura_creada'],
                    'total' => $res['total_facturado'],
                    'mensaje' => 'Factura emitida'
                ]);
            } catch (PDOException $e) {
                echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            }
            break;

        // ---- ANULAR factura ----
        case 'anular':
            $id = (int)($_POST['id_factura'] ?? 0);

            // Verificar que no tenga pagos
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pagos WHERE id_factura = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode([
                    'ok' => false,
                    'error' => 'No se puede anular: la factura ya tiene pagos registrados.'
                ]);
                break;
            }

            $pdo->prepare("SET @usuario_actual = ?")->execute([$_SESSION['id_usuario']]);
            $stmt = $pdo->prepare("UPDATE facturas SET estado = 'anulada' WHERE id_factura = ?");
            $stmt->execute([$id]);

            echo json_encode(['ok' => true, 'mensaje' => 'Factura anulada']);
            break;

        // ---- DETALLE de una factura: tratamientos + pagos ----
        case 'detalle':
            $id = (int)($_GET['id'] ?? 0);

            // Datos generales
            $stmt = $pdo->prepare("
                SELECT f.id_factura, f.id_cita, f.fecha_emision, f.total, f.estado,
                       CONCAT(p.nombre,' ',p.apellido) AS paciente,
                       p.dpi, p.telefono,
                       c.fecha_hora AS fecha_cita
                FROM facturas f
                JOIN citas c ON c.id_cita = f.id_cita
                JOIN pacientes p ON p.id_paciente = c.id_paciente
                WHERE f.id_factura = ?
            ");
            $stmt->execute([$id]);
            $factura = $stmt->fetch();
            if (!$factura) {
                echo json_encode(['ok' => false, 'error' => 'Factura no encontrada']);
                break;
            }

            // Tratamientos de la cita
            $stmt = $pdo->prepare("
                SELECT t.nombre, t.costo, d.observaciones
                FROM detalle_tratamiento_cita d
                JOIN tratamientos t ON t.id_tratamiento = d.id_tratamiento
                WHERE d.id_cita = ?
            ");
            $stmt->execute([$factura['id_cita']]);
            $tratamientos = $stmt->fetchAll();

            // Pagos
            $stmt = $pdo->prepare("
                SELECT id_pago, monto, metodo_pago, fecha_pago
                FROM pagos WHERE id_factura = ?
                ORDER BY fecha_pago
            ");
            $stmt->execute([$id]);
            $pagos = $stmt->fetchAll();

            $pagado = array_sum(array_column($pagos, 'monto'));

            echo json_encode([
                'ok' => true,
                'factura' => $factura,
                'tratamientos' => $tratamientos,
                'pagos' => $pagos,
                'pagado' => $pagado,
                'saldo' => $factura['total'] - $pagado
            ]);
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
    }
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}