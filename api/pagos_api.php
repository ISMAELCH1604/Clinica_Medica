<?php
require_once '../conexion.php';
require_once '../includes/seguridad.php';
header('Content-Type: application/json; charset=utf-8');

requerirLoginAPI();

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

try {
    switch ($accion) {

        case 'listar':
            $filtro = $_GET['filtro'] ?? 'todos';
            $where  = '';
            if ($filtro === 'hoy')      $where = "WHERE DATE(p.fecha_pago) = CURDATE()";
            elseif ($filtro === 'mes')  $where = "WHERE MONTH(p.fecha_pago) = MONTH(CURDATE()) AND YEAR(p.fecha_pago) = YEAR(CURDATE())";

            $sql = "
                SELECT p.id_pago, p.id_factura, p.monto, p.metodo_pago, p.fecha_pago,
                       CONCAT(pa.nombre, ' ', pa.apellido) AS paciente
                FROM pagos p
                INNER JOIN facturas f   ON f.id_factura = p.id_factura
                INNER JOIN citas c      ON c.id_cita = f.id_cita
                INNER JOIN pacientes pa ON pa.id_paciente = c.id_paciente
                $where
                ORDER BY p.fecha_pago DESC
                LIMIT 200
            ";
            echo json_encode(['ok' => true, 'datos' => $pdo->query($sql)->fetchAll()]);
            break;

        case 'facturas_pendientes':
            $sql = "
                SELECT f.id_factura, f.total,
                       COALESCE(SUM(p.monto), 0) AS pagado,
                       f.total - COALESCE(SUM(p.monto), 0) AS saldo,
                       f.fecha_emision,
                       CONCAT(pa.nombre, ' ', pa.apellido) AS paciente
                FROM facturas f
                INNER JOIN citas c      ON c.id_cita = f.id_cita
                INNER JOIN pacientes pa ON pa.id_paciente = c.id_paciente
                LEFT  JOIN pagos p      ON p.id_factura = f.id_factura
                WHERE f.estado = 'pendiente'
                GROUP BY f.id_factura, f.total, f.fecha_emision, pa.nombre, pa.apellido
                HAVING saldo > 0
                ORDER BY f.fecha_emision DESC
            ";
            echo json_encode(['ok' => true, 'datos' => $pdo->query($sql)->fetchAll()]);
            break;

        case 'registrar':
            $id_factura = (int)($_POST['id_factura'] ?? 0);
            $monto      = (float)($_POST['monto'] ?? 0);
            $metodo     = $_POST['metodo_pago'] ?? '';

            if ($id_factura <= 0 || $monto <= 0) {
                echo json_encode(['ok' => false, 'error' => 'Datos incompletos o inválidos.']);
                break;
            }
            if (!in_array($metodo, ['efectivo', 'tarjeta', 'transferencia'])) {
                echo json_encode(['ok' => false, 'error' => 'Método de pago no válido.']);
                break;
            }

            $pdo->prepare("SET @usuario_actual = ?")->execute([$_SESSION['id_usuario']]);

            try {
                $stmt = $pdo->prepare("CALL sp_registrar_pago(?, ?, ?)");
                $stmt->execute([$id_factura, $monto, $metodo]);
                $res = $stmt->fetch();

                echo json_encode([
                    'ok' => true,
                    'id_pago' => $res['id_pago_creado'],
                    'saldo_restante' => $res['saldo_restante'],
                    'mensaje' => 'Pago registrado correctamente'
                ]);
            } catch (PDOException $e) {
                echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
            }
            break;

        case 'resumen':
            $hoy   = $pdo->query("SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE DATE(fecha_pago) = CURDATE()")->fetchColumn();
            $mes   = $pdo->query("SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE MONTH(fecha_pago) = MONTH(CURDATE()) AND YEAR(fecha_pago) = YEAR(CURDATE())")->fetchColumn();
            $total = $pdo->query("SELECT COALESCE(SUM(monto), 0) FROM pagos")->fetchColumn();

            $porMetodo = $pdo->query("
                SELECT metodo_pago, COUNT(*) AS cantidad, SUM(monto) AS total
                FROM pagos GROUP BY metodo_pago
            ")->fetchAll();

            echo json_encode([
                'ok' => true,
                'hoy' => $hoy,
                'mes' => $mes,
                'total' => $total,
                'por_metodo' => $porMetodo
            ]);
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}