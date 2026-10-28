<?php
require_once __DIR__ . '/seguridad.php';
requerirLogin();

$rol    = strtolower($_SESSION['rol'] ?? '');
$nombre = $_SESSION['nombre_usuario'] ?? '';
?>
<!DOCTYPE html>
<html lang="es" style="height: 100%;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clínica Dental | Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/estilos.css">
    <!-- Bootstrap del menú móvil -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="d-flex">

    <!-- MENÚ PARA CELULAR (Se oculta en PC)        -->

    <div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="menuMovil" style="width: 260px;">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold text-primary"><i class="bi bi-heart-pulse"></i> Clínica Dental</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0 d-flex flex-column">
            <ul class="nav flex-column mt-2 flex-grow-1">
                <li class="nav-item"><a class="nav-link text-dark fw-medium" href="dashboard.php"><i class="bi bi-grid-1x2 me-2"></i> Dashboard</a></li>
                
                <?php if (in_array($rol, ['administrador', 'recepcion', 'odontologo'])): ?>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="pacientes.php"><i class="bi bi-people me-2"></i> Pacientes</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="citas.php"><i class="bi bi-calendar-event me-2"></i> Agenda de Citas</a></li>
                <?php endif; ?>

                <?php if (in_array($rol, ['administrador', 'recepcion'])): ?>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="facturas.php"><i class="bi bi-receipt me-2"></i> Facturación</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="pagos.php"><i class="bi bi-cash-coin me-2"></i> Pagos</a></li>
                <?php endif; ?>

                <?php if ($rol === 'administrador'): ?>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="usuarios.php"><i class="bi bi-person-gear me-2"></i> Usuarios</a></li>
                <?php endif; ?>

                <?php if (in_array($rol, ['administrador', 'auditor'])): ?>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="bitacora.php"><i class="bi bi-shield-check me-2"></i> Auditoría</a></li>
                    <li class="nav-item"><a class="nav-link text-dark fw-medium" href="reportes.php"><i class="bi bi-file-earmark-bar-graph me-2"></i> Reportes</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- SIDEBAR PARA PC (Se oculta en celular)     -->
    <aside class="sidebar d-none d-md-flex flex-column position-sticky top-0" style="width: 260px; height: 100vh;">
        <a href="dashboard.php" class="brand">
            <i class="bi bi-heart-pulse text-primary"></i> Clínica Dental
        </a>
        <!-- <ul class="nav flex-column mt-3 flex-grow-1">
            <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-grid-1x2"></i> Dashboard</a></li> -->
            
            <?php if (in_array($rol, ['administrador', 'recepcion', 'odontologo'])): ?>
                <li class="nav-item"><a class="nav-link" href="pacientes.php"><i class="bi bi-people"></i> Pacientes</a></li>
                <li class="nav-item"><a class="nav-link" href="citas.php"><i class="bi bi-calendar-event"></i> Agenda de Citas</a></li>
            <?php endif; ?>

            <?php if (in_array($rol, ['administrador', 'recepcion'])): ?>
                <li class="nav-item"><a class="nav-link" href="facturas.php"><i class="bi bi-receipt"></i> Facturación</a></li>
                <li class="nav-item"><a class="nav-link" href="pagos.php"><i class="bi bi-cash-coin"></i> Pagos</a></li>
            <?php endif; ?>

            <?php if ($rol === 'administrador'): ?>
                <li class="nav-item"><a class="nav-link" href="usuarios.php"><i class="bi bi-person-gear"></i> Usuarios</a></li>
            <?php endif; ?>

            <?php if (in_array($rol, ['administrador', 'auditor'])): ?>
                <li class="nav-item"><a class="nav-link" href="bitacora.php"><i class="bi bi-shield-check"></i> Auditoría</a></li>
                <li class="nav-item"><a class="nav-link" href="reportes.php"><i class="bi bi-file-earmark-bar-graph"></i> Reportes</a></li>
            <?php endif; ?>
        </ul>

        <div class="user-profile">
            <div class="d-flex align-items-center">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 42px; height: 42px; font-weight: bold; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(13,110,253,0.3);">
                    <?= strtoupper(substr($nombre, 0, 1)) ?>
                </div>
                <div class="lh-sm">
                    <div class="fw-bold text-dark"><?= htmlspecialchars($nombre) ?></div>
                    <div class="text-muted" style="font-size: 0.8rem;"><?= ucfirst($rol) ?></div>
                </div>
                <a href="../logout.php" class="ms-auto text-muted" title="Cerrar sesión">
                    <i class="bi bi-box-arrow-right fs-4"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="main-wrapper">
        <!-- Barra superior solo para móvil -->
        <nav class="navbar navbar-light bg-white shadow-sm m-3 mb-0 d-md-none rounded">
            <div class="container-fluid">
                <!-- ESTE ES EL BOTÓN MÁGICO DE BOOTSTRAP -->
                <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuMovil">
                    <i class="bi bi-list fs-2 text-primary"></i>
                </button>
                <span class="navbar-brand fw-bold text-primary mx-auto">Clínica Dental</span>
                <a href="../logout.php" class="text-danger"><i class="bi bi-box-arrow-right fs-4"></i></a>
            </div>
        </nav>

        <main class="main-content">