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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <link rel="stylesheet" href="../css/estilos.css">
</head>
<body class="d-flex"> <!-- IMPORTANTE: El body debe ser d-flex para que el sidebar y el main se alineen lado a lado -->
    <!-- SIDEBAR -->
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
                <a href="../logout.php" class="ms-auto text-muted" title="Cerrar sesión" style="transition: color 0.2s;" onmouseover="this.style.color='#dc3545'" onmouseout="this.style.color='#6c757d'">
                    <i class="bi bi-box-arrow-right fs-4"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="main-wrapper">
        <!-- Navbar superior para móviles -->
        <nav class="navbar navbar-light bg-white rounded shadow-sm m-3 mb-0 d-md-none">
            <div class="container-fluid">
                <span class="navbar-brand fw-bold text-primary">Clínica Dental</span>
                <a href="../logout.php" class="btn btn-outline-danger btn-sm">Salir</a>
            </div>
        </nav>

        <!-- AQUÍ EMPIEZA EL CONTENIDO DE CADA PÁGINA -->
        <main class="main-content">