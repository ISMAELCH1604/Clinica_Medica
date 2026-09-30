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

        // ---- LISTAR con filtros dinámicos ----
        case 'listar':
            $condiciones = [];
            $params = [];

            if (!empty($_GET['id_usuario'])) {
                $condiciones[] = "b.id_usuario = ?";
                $params[] = (int)$_GET['id_usuario'];
            }
            if (!empty($_GET['tabla'])) {
                $condiciones[] = "b.tabla_afectada = ?";
                $params[] = $_GET['tabla'];
            }
            if (!empty($_GET['operacion'])) {
                $condiciones[] = "b.operacion = ?";
                $params[] = $_GET['operacion'];
            }
            if (!empty($_GET['desde'])) {
                $condiciones[] = "DATE(b.fecha_hora) >= ?";
                $params[] = $_GET['desde'];
            }
            if (!empty($_GET['hasta'])) {
                $condiciones[] = "DATE(b.fecha_hora) <= ?";
                $params[] = $_GET['hasta'];
            }

            $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

            $sql = "
                SELECT b.id_bitacora,
                       b.fecha_hora,
                       COALESCE(u.nombre_usuario, '(sistema)') AS usuario,
                       u.rol,
                       b.tabla_afectada,
                       b.operacion,
                       b.valor_anterior,
                       b.valor_nuevo
                FROM bitacora_auditoria b
                LEFT JOIN usuarios u ON u.id_usuario = b.id_usuario
                $where
                ORDER BY b.fecha_hora DESC
                LIMIT 500
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['ok' => true, 'datos' => $stmt->fetchAll()]);
            break;

        // ---- Estadísticas ----
        case 'estadisticas':
            $total = $pdo->query("SELECT COUNT(*) FROM bitacora_auditoria")->fetchColumn();
            $hoy   = $pdo->query("SELECT COUNT(*) FROM bitacora_auditoria WHERE DATE(fecha_hora) = CURDATE()")->fetchColumn();

            $porOperacion = $pdo->query("
                SELECT operacion, COUNT(*) AS total
                FROM bitacora_auditoria
                GROUP BY operacion
            ")->fetchAll();

            $porTabla = $pdo->query("
                SELECT tabla_afectada, COUNT(*) AS total
                FROM bitacora_auditoria
                GROUP BY tabla_afectada
                ORDER BY total DESC
                LIMIT 8
            ")->fetchAll();

            $porUsuario = $pdo->query("
                SELECT COALESCE(u.nombre_usuario, '(sistema)') AS usuario,
                       u.rol,
                       COUNT(*) AS total
                FROM bitacora_auditoria b
                LEFT JOIN usuarios u ON u.id_usuario = b.id_usuario
                GROUP BY u.nombre_usuario, u.rol
                ORDER BY total DESC
            ")->fetchAll();

            echo json_encode([
                'ok' => true,
                'total' => $total,
                'hoy' => $hoy,
                'por_operacion' => $porOperacion,
                'por_tabla' => $porTabla,
                'por_usuario' => $porUsuario
            ]);
            break;

        // ---- Opciones para filtros ----
        case 'opciones':
            $usuarios = $pdo->query("
                SELECT DISTINCT id_usuario, nombre_usuario, rol 
                FROM usuarios ORDER BY nombre_usuario
            ")->fetchAll();

            $tablas = $pdo->query("
                SELECT DISTINCT tabla_afectada FROM bitacora_auditoria
                ORDER BY tabla_afectada
            ")->fetchAll(PDO::FETCH_COLUMN);

            echo json_encode([
                'ok' => true,
                'usuarios' => $usuarios,
                'tablas' => $tablas,
                'operaciones' => ['INSERT', 'UPDATE', 'DELETE']
            ]);
            break;

        // ---- Exportar CSV ----
        case 'exportar_csv':
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="bitacora_' . date('Ymd_His') . '.csv"');

            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel
            fputcsv($out, ['ID', 'Fecha', 'Usuario', 'Rol', 'Tabla', 'Operación', 'Valor anterior', 'Valor nuevo']);

            $stmt = $pdo->query("
                SELECT b.id_bitacora, b.fecha_hora,
                       COALESCE(u.nombre_usuario, '(sistema)') AS usuario,
                       u.rol, b.tabla_afectada, b.operacion,
                       b.valor_anterior, b.valor_nuevo
                FROM bitacora_auditoria b
                LEFT JOIN usuarios u ON u.id_usuario = b.id_usuario
                ORDER BY b.fecha_hora DESC
                LIMIT 5000
            ");
            while ($r = $stmt->fetch()) {
                fputcsv($out, [
                    $r['id_bitacora'],
                    $r['fecha_hora'],
                    $r['usuario'],
                    $r['rol'],
                    $r['tabla_afectada'],
                    $r['operacion'],
                    $r['valor_anterior'],
                    $r['valor_nuevo'],
                ]);
            }
            fclose($out);
            exit;

        default:
            echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
    }
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}