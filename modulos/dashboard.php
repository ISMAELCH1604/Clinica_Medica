<?php
require_once '../conexion.php';
require_once '../includes/seguridad.php';
requerirLogin();
require_once '../includes/header.php';

$rol = strtolower($_SESSION['rol'] ?? '');

// CONSULTAS SEGÚN EL ROL.

$totalPacientes = 0; $citasHoy = 0; $saldoPorCobrar = 0; $ingresosMes = 0;
$citasHoyLista = [];

if (in_array($rol, ['administrador', 'recepcion'])) {
    // Métricas financieras y globales (Solo Admin y Recepción)
    $totalPacientes = $pdo->query("SELECT COUNT(*) FROM pacientes")->fetchColumn();
    $citasHoy = $pdo->query("SELECT COUNT(*) FROM citas WHERE DATE(fecha_hora) = CURDATE()")->fetchColumn();
    $saldoPorCobrar = $pdo->query("SELECT COALESCE(SUM(saldo),0) FROM v_facturas_saldo WHERE estado = 'pendiente'")->fetchColumn();
    $ingresosMes = $pdo->query("SELECT COALESCE(SUM(monto),0) FROM pagos WHERE MONTH(fecha_pago) = MONTH(CURDATE()) AND YEAR(fecha_pago) = YEAR(CURDATE())")->fetchColumn();
    
} elseif ($rol === 'odontologo') {
    // Métricas clínicas (Solo Odontólogo)
    // Contamos cuántas citas tiene hoy este odontólogo específico
    $id_odontologo = $_SESSION['id_usuario']; // Nota: Asegúrate de vincular bien el id_usuario con el id_odontologo
    // Por simplicidad para la prueba, traemos todas las citas de hoy
    $citasHoy = $pdo->query("SELECT COUNT(*) FROM citas WHERE DATE(fecha_hora) = CURDATE()")->fetchColumn();
    
} elseif ($rol === 'auditor') {
    // Métricas de seguridad (Solo Auditor)
    $totalPacientes = $pdo->query("SELECT COUNT(*) FROM bitacora_auditoria")->fetchColumn(); // Reutilizamos la variable para contar logs
}

// Traer la lista de citas de hoy (para admin, recepción y odontólogos)
if (in_array($rol, ['administrador', 'recepcion', 'odontologo'])) {
    $stmt = $pdo->query("
        SELECT c.fecha_hora, c.estado, CONCAT(p.nombre, ' ', p.apellido) AS paciente, 
               o.especialidad
        FROM citas c
        JOIN pacientes p ON p.id_paciente = c.id_paciente
        JOIN odontologos o ON o.id_odontologo = c.id_odontologo
        WHERE DATE(c.fecha_hora) = CURDATE() AND c.estado = 'programada'
        ORDER BY c.fecha_hora ASC
        LIMIT 4
    ");
    $citasHoyLista = $stmt->fetchAll();
}
?>

<style>
    .kpi-card { border: 1px solid #eef2f5; border-radius: 8px; background: #fff; transition: all 0.2s; }
    .kpi-card:hover { border-color: #d1d9e6; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
    .kpi-title { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; font-weight: 600; }
    .kpi-value { font-size: 1.8rem; font-weight: 700; color: #111827; margin: 0; }
    .data-table th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; font-weight: 600; border-bottom: 2px solid #e5e7eb; }
    .data-table td { vertical-align: middle; color: #374151; }
    .quick-action-btn { text-align: left; padding: 12px 16px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; color: #374151; font-weight: 500; transition: all 0.2s; display: flex; align-items: center; justify-content: space-between; text-decoration: none; }
    .quick-action-btn:hover { border-color: #0d6efd; color: #0d6efd; background: #f8fbff; }
</style>

<!-- Encabezado -->
<div class="d-flex justify-content-between align-items-end mb-4 border-bottom pb-3">
    <div>
        <h4 class="fw-bold mb-1" style="color: #111827;">Resumen General</h4>
        <p class="text-muted small mb-0">Hola <strong><?= htmlspecialchars($_SESSION['nombre_usuario']) ?></strong>, este es tu resumen para hoy <?= date('d/m/Y') ?>.</p>
    </div>
</div>

<!-- SECCIÓN 1: KPIs Dinámicos según el Rol -->
<div class="row g-3 mb-4">
    <?php if (in_array($rol, ['administrador', 'recepcion'])): ?>
        <!-- TARJETAS FINANCIERAS (Admin/Recepción) -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="kpi-card p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="kpi-title mb-1">Pacientes Totales</div><p class="kpi-value"><?= $totalPacientes ?></p></div>
                    <div class="text-primary opacity-50"><i class="bi bi-people fs-4"></i></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="kpi-card p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="kpi-title mb-1">Citas Hoy</div><p class="kpi-value"><?= $citasHoy ?></p></div>
                    <div class="text-info opacity-50"><i class="bi bi-calendar-event fs-4"></i></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="kpi-card p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="kpi-title mb-1">Saldo por Cobrar</div><p class="kpi-value text-danger">Q <?= number_format($saldoPorCobrar, 2) ?></p></div>
                    <div class="text-danger opacity-50"><i class="bi bi-wallet2 fs-4"></i></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="kpi-card p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="kpi-title mb-1">Ingresos del Mes</div><p class="kpi-value text-success">Q <?= number_format($ingresosMes, 2) ?></p></div>
                    <div class="text-success opacity-50"><i class="bi bi-graph-up-arrow fs-4"></i></div>
                </div>
            </div>
        </div>

    <?php elseif ($rol === 'odontologo'): ?>
        <!-- TARJETAS CLÍNICAS (Odontólogo) -->
        <div class="col-12 col-md-6">
            <div class="kpi-card p-4 text-center">
                <div class="text-primary mb-2"><i class="bi bi-calendar-check fs-1"></i></div>
                <p class="kpi-value mb-1"><?= $citasHoy ?></p>
                <div class="kpi-title">Citas para Hoy</div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="kpi-card p-4 text-center">
                <div class="text-success mb-2"><i class="bi bi-folder2-open fs-1"></i></div>
                <p class="kpi-value mb-1"><?= $totalPacientes ?></p>
                <div class="kpi-title">Pacientes en mi historial</div>
            </div>
        </div>

    <?php elseif ($rol === 'auditor'): ?>
        <!-- TARJETAS DE SEGURIDAD (Auditor) -->
        <div class="col-12">
            <div class="kpi-card p-4 d-flex align-items-center justify-content-between bg-dark text-white border-0">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-shield-lock me-2"></i> Modo Auditoría Activo</h5>
                    <p class="mb-0 text-white-50">Tienes acceso de solo lectura a los registros de seguridad.</p>
                </div>
                <a href="bitacora.php" class="btn btn-light text-dark fw-bold">Ir a la Bitácora</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- SECCIÓN 2: Contenido según el Rol -->
<div class="row g-4">
    
    <!-- COLUMNA IZQUIERDA: Agenda (Para Admin, Recepción y Doctores) -->
    <?php if (in_array($rol, ['administrador', 'recepcion', 'odontologo'])): ?>
    <div class="col-12 <?= $rol === 'odontologo' ? 'col-lg-12' : 'col-lg-8' ?>">
        <div class="card border-0 shadow-sm" style="border: 1px solid #eef2f5 !important;">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Agenda para Hoy</h6>
                <a href="citas.php" class="text-decoration-none small fw-semibold">Ver agenda completa <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="table data-table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Paciente</th>
                                <th>Especialidad</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($citasHoyLista)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-calendar-x fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                        No hay más citas programadas para hoy.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($citasHoyLista as $c): ?>
                                    <tr>
                                        <td class="fw-semibold text-primary"><?= date('h:i A', strtotime($c['fecha_hora'])) ?></td>
                                        <td class="fw-medium"><?= htmlspecialchars($c['paciente']) ?></td>
                                        <td class="text-muted small"><?= htmlspecialchars($c['especialidad']) ?></td>
                                        <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-2">Programada</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- COLUMNA DERECHA: Acciones Rápidas (Solo Admin y Recepción) -->
    <?php if (in_array($rol, ['administrador', 'recepcion'])): ?>
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border: 1px solid #eef2f5 !important;">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                <h6 class="fw-bold mb-0">Acciones Rápidas</h6>
            </div>
            <div class="card-body px-4">
                <div class="d-flex flex-column gap-2">
                    <a href="pacientes.php" class="quick-action-btn">
                        <span><i class="bi bi-person-plus text-primary me-2"></i> Registrar Paciente</span>
                        <i class="bi bi-chevron-right small text-muted"></i>
                    </a>
                    <a href="citas.php" class="quick-action-btn">
                        <span><i class="bi bi-calendar-plus text-primary me-2"></i> Agendar Cita</span>
                        <i class="bi bi-chevron-right small text-muted"></i>
                    </a>
                    <a href="facturas.php" class="quick-action-btn">
                        <span><i class="bi bi-receipt text-primary me-2"></i> Emitir Factura</span>
                        <i class="bi bi-chevron-right small text-muted"></i>
                    </a>
                    <a href="pagos.php" class="quick-action-btn">
                        <span><i class="bi bi-cash-coin text-primary me-2"></i> Registrar Pago</span>
                        <i class="bi bi-chevron-right small text-muted"></i>
                    </a>
                    <?php if ($rol === 'administrador'): ?>
                    <a href="usuarios.php" class="quick-action-btn">
                        <span><i class="bi bi-person-gear text-primary me-2"></i> Gestionar Usuarios</span>
                        <i class="bi bi-chevron-right small text-muted"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- VISTA DE AUDITOR: Panel de auditoría -->
    <?php if ($rol === 'auditor'): ?>
    <div class="col-12 col-lg-12">
        <div class="card border-0 shadow-sm" style="border: 1px solid #eef2f5 !important;">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                <h6 class="fw-bold mb-0">Información de Seguridad</h6>
            </div>
            <div class="card-body px-4 text-muted">
                <p>Como auditor, tu función principal es revisar la trazabilidad de las operaciones. El sistema no te permite modificar datos clínicos ni financieros para garantizar la transparencia.</p>
                <div class="alert alert-secondary border-0">
                    <i class="bi bi-info-circle me-2"></i> Los registros de auditoría son inmutables y se generan automáticamente con cada acción en el sistema.
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>