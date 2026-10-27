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
            $stmt = $pdo->prepare("
                SELECT f.id_factura, f.fecha_emision, CONCAT(p.nombre,' ',p.apellido) AS paciente,
                       f.total, COALESCE(SUM(pg.monto),0) AS pagado, 
                       (f.total - COALESCE(SUM(pg.monto),0)) AS saldo, f.estado
                FROM facturas f
                JOIN citas c ON c.id_cita = f.id_cita
                JOIN pacientes p ON p.id_paciente = c.id_paciente
                LEFT JOIN pagos pg ON pg.id_factura = f.id_factura
                WHERE DATE(f.fecha_emision) BETWEEN ? AND ?
                GROUP BY f.id_factura, f.fecha_emision, p.nombre, p.apellido, f.total, f.estado
                ORDER BY f.fecha_emision DESC
            ");
            $stmt->execute([$desde, $hasta]);
            $datos = $stmt->fetchAll();
            break;

        case 'citas':
            $titulo = 'Reporte de Agenda de Citas';
            $stmt = $pdo->prepare("
                SELECT c.id_cita, c.fecha_hora, CONCAT(p.nombre,' ',p.apellido) AS paciente,
                       CONCAT(o.nombre,' ',o.apellido) AS odontologo, c.estado
                FROM citas c
                JOIN pacientes p ON p.id_paciente = c.id_paciente
                JOIN odontologos o ON o.id_odontologo = c.id_odontologo
                WHERE DATE(c.fecha_hora) BETWEEN ? AND ?
                ORDER BY c.fecha_hora ASC
            ");
            $stmt->execute([$desde, $hasta]);
            $datos = $stmt->fetchAll();
            break;

        //Case calendario
        case 'calendario':
            $titulo = 'Calendario de Citas Pendientes';
            $stmt = $pdo->prepare("
                SELECT c.id_cita, c.fecha_hora, CONCAT(p.nombre,' ',p.apellido) AS paciente,
                       CONCAT(o.nombre,' ',o.apellido) AS odontologo, c.estado
                FROM citas c
                JOIN pacientes p ON p.id_paciente = c.id_paciente
                JOIN odontologos o ON o.id_odontologo = c.id_odontologo
                WHERE DATE(c.fecha_hora) BETWEEN ? AND ?
                  AND c.estado = 'programada'  -- SOLO PROGRAMADAS
                ORDER BY c.fecha_hora ASC
            ");
            $stmt->execute([$desde, $hasta]);
            $datos = $stmt->fetchAll();
            break;

        case 'pacientes':
            $titulo = 'Reporte de Nuevos Pacientes';
            $stmt = $pdo->prepare("
                SELECT id_paciente, CONCAT(nombre,' ',apellido) AS paciente, dpi, telefono, DATE(fecha_registro) AS fecha_registro
                FROM pacientes
                WHERE DATE(fecha_registro) BETWEEN ? AND ?
                ORDER BY fecha_registro DESC
            ");
            $stmt->execute([$desde, $hasta]);
            $datos = $stmt->fetchAll();
            break;
            
        case 'productividad':
            $titulo = 'Reporte de Productividad Médica';
            $stmt = $pdo->prepare("
                SELECT CONCAT(o.nombre,' ',o.apellido) AS odontologo, o.especialidad,
                       COUNT(c.id_cita) AS total_citas,
                       SUM(CASE WHEN c.estado = 'completada' THEN 1 ELSE 0 END) AS completadas,
                       SUM(CASE WHEN c.estado = 'no_asistio' THEN 1 ELSE 0 END) AS no_asistio
                FROM citas c
                JOIN odontologos o ON o.id_odontologo = c.id_odontologo
                WHERE DATE(c.fecha_hora) BETWEEN ? AND ?
                GROUP BY o.id_odontologo, o.nombre, o.apellido, o.especialidad
                ORDER BY completadas DESC
            ");
            $stmt->execute([$desde, $hasta]);
            $datos = $stmt->fetchAll();
            break;
        ##
               
        ##

        default:
            echo json_encode(['ok' => false, 'error' => 'Tipo de reporte no válido']);
            exit;
    }

    echo json_encode(['ok' => true, 'titulo' => $titulo, 'datos' => $datos]);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}