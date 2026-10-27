<?php
require_once '../conexion.php';
require_once '../includes/seguridad.php';
header('Content-Type: application/json; charset=utf-8');

requerirRolAPI(['administrador', 'auditor']);

$tipo = $_GET['tipo'] ?? '';
$desde = $_GET['desde'] ?? date('Y-m-01'); // Por defecto inicio de mes
$hasta = $_GET['hasta'] ?? date('Y-m-d');  // Por defecto hoy

try {
    $datos = [];
    $titulo = '';

        switch ($tipo) {
        case 'facturacion':
            $titulo = 'Reporte de Facturación y Cobros';
            $stmt = $pdo->prepare("CALL sp_reporte_facturacion(?, ?)");
            $stmt->execute([$desde, $hasta]);
            $datos = $stmt->fetchAll();
            break;

        case 'citas':
            $titulo = 'Reporte de Agenda de Citas';
            $stmt = $pdo->prepare("CALL sp_reporte_citas(?, ?)");
            $stmt->execute([$desde, $hasta]);
            $datos = $stmt->fetchAll();
            break;

        case 'calendario':
            $titulo = 'Calendario de Citas Pendientes';
            $stmt = $pdo->prepare("CALL sp_reporte_calendario(?, ?)");
            $stmt->execute([$desde, $hasta]);
            $datos = $stmt->fetchAll();
            break;

        case 'pacientes':
            $titulo = 'Reporte de Nuevos Pacientes';
            $stmt = $pdo->prepare("
                SELECT id_paciente, CONCAT(nombre,' ',apellido) AS paciente, dpi, telefono, DATE(fecha_registro) AS fecha_registro
                FROM pacientes WHERE DATE(fecha_registro) BETWEEN ? AND ? ORDER BY fecha_registro DESC
            ");
            $stmt->execute([$desde, $hasta]);
            $datos = $stmt->fetchAll();
            break;
            
        case 'productividad':
            $titulo = 'Reporte de Productividad Médica';
            $stmt = $pdo->prepare("CALL sp_reporte_productividad(?, ?)");
            $stmt->execute([$desde, $hasta]);
            $datos = $stmt->fetchAll();
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Tipo de reporte no válido']);
            exit;
    }

    echo json_encode(['ok' => true, 'titulo' => $titulo, 'datos' => $datos]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}