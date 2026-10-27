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
    <style>
        :root {
            --bg-body: #f3f6fa; /* Gris azulado muy suave para el fondo */
            --primary-color: #0d6efd;
            --sidebar-bg: #ffffff;
            --text-dark: #2c3e50;
        }
        html, body {
            height: 100%;
            margin: 0;
        }
        body {
            background-color: var(--bg-body);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            overflow-x: hidden;
            display: flex; 
            width: 100%;
        }
        
        /* Sidebar Moderno */
        .sidebar {
            min-height: 100vh;
            background-color: var(--sidebar-bg);
            box-shadow: 2px 0 15px rgba(0,0,0,0.03); /* Sombra muy sutil */
            transition: all 0.3s;
        }
        .sidebar .brand {
            padding: 24px 20px;
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #f0f0f0;
        }
        .sidebar .nav-link {
            color: #6c757d;
            padding: 12px 20px;
            margin: 4px 15px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
        }
        .sidebar .nav-link:hover {
            background-color: #f8f9fa;
            color: var(--primary-color);
        }
        .sidebar .nav-link.active {
            background-color: rgba(13, 110, 253, 0.1); /* Fondo azul suave */
            color: var(--primary-color);
        }
        .sidebar .nav-link i {
            margin-right: 12px;
            font-size: 1.2rem;
        }
        .sidebar .user-profile {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 20px;
            border-top: 1px solid #f0f0f0;
            background: #fff;
            border-radius: 0 0 0 15px;
        }
        
        /* Área de contenido */
        .main-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            flex-grow: 1;
            width: calc(100% - 260px);
        }
        .main-content {
            padding: 30px 40px;
            flex-grow: 1;
        }
        
        /* Tarjetas premium */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            transition: transform 0.2s, box-shadow 0.2s;
            background: #ffffff;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.06);
        }
        
        /* Ocultar sidebar en móvil */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-wrapper { width: 100%; }
            .main-content { padding: 20px; }
        }
    </style>
</head>
<body>
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

            <?php if (in_array($rol, ['administrador', 'auditor'])): ?>
                <li class="nav-item"><a class="nav-link" href="bitacora.php"><i class="bi bi-shield-check"></i> Auditoría</a></li>
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
                <span class="navbar-brand fw-bold text-primary">🦷 Clínica Dental</span>
                <a href="../logout.php" class="btn btn-outline-danger btn-sm">Salir</a>
            </div>
        </nav>

        <!-- Aquí empieza el contenido de cada página -->
        <main class="main-content">