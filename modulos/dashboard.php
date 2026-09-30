<?php
require_once '../conexion.php';
require_once '../includes/header.php';

// Estadísticas rápidas
$totalPacientes   = $pdo->query("SELECT COUNT(*) FROM pacientes")->fetchColumn();
$totalCitas       = $pdo->query("SELECT COUNT(*) FROM citas")->fetchColumn();
$citasHoy         = $pdo->query("SELECT COUNT(*) FROM citas WHERE DATE(fecha_hora) = CURDATE()")->fetchColumn();
$facturasPendientes = $pdo->query("SELECT COUNT(*) FROM facturas WHERE estado = 'pendiente'")->fetchColumn();
$saldoTotal       = $pdo->query("SELECT COALESCE(SUM(saldo),0) FROM v_facturas_saldo WHERE estado <> 'anulada'")->fetchColumn();
?>

<h2 class="mb-4">Panel principal</h2>

<div class="row g-3">
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card border-primary shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Pacientes</h6>
                <p class="display-6 mb-0"><?= $totalPacientes ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card border-info shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Citas totales</h6>
                <p class="display-6 mb-0"><?= $totalCitas ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card border-warning shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Citas hoy</h6>
                <p class="display-6 mb-0"><?= $citasHoy ?></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card border-danger shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Facturas pendientes</h6>
                <p class="display-6 mb-0"><?= $facturasPendientes ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Saldo total por cobrar</h5>
                <p class="display-5 text-success mb-0">Q <?= number_format($saldoTotal, 2) ?></p>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>