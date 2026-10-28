<?php
require_once 'conexion.php';
require_once 'includes/seguridad.php';

$error = '';
$aviso = '';

// Aviso si la sesión expiró
if (isset($_GET['expirada'])) {
    $aviso = 'Tu sesión expiró por inactividad. Vuelve a iniciar sesión.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario  = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($usuario === '' || $password === '') {
        $error = 'Por favor completa todos los campos.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nombre_usuario = ? AND activo = TRUE");
        $stmt->execute([$usuario]);
        $u = $stmt->fetch();

        if ($u && password_verify($password, $u['password_hash'])) {
            // Login exitoso: regenerar ID de sesión (evita session fixation)
            session_regenerate_id(true);

            $_SESSION['id_usuario']       = $u['id_usuario'];
            $_SESSION['nombre_usuario']   = $u['nombre_usuario'];
            $_SESSION['rol']              = $u['rol'];
            $_SESSION['ultima_actividad'] = time();

            // Actualizar último acceso (opcional pero recomendado)
            $pdo->prepare("UPDATE usuarios SET fecha_creacion = fecha_creacion WHERE id_usuario = ?")
                ->execute([$u['id_usuario']]);

            header('Location: modulos/dashboard.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
            // Retardo para dificultar ataques de fuerza bruta
            usleep(500000); // 0.5 segundos
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clínica Dental</title>
    <link rel="icon" type="image/svg+xml" href="img/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --azul-profundo: #0b2a5b;
            --azul-clinica:  #0a63e6;
            --azul-cielo:    #2fa8ff;
            --azul-suave:    #eaf3ff;
            --tinta:         #10213f;
            --tinta-suave:   #5a6b87;
            --linea:         #d6e2f3;
            --fondo:         #ffffff;
        }

        html, body { height: 100%; }

        body {
            margin: 0;
            font-family: 'Figtree', system-ui, sans-serif;
            color: var(--tinta);
            background: var(--fondo);
        }

        .login-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr);
        }

        /* ---------- Panel de marca ---------- */
        .brand-panel {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3.5rem 4rem;
            color: #fff;
            background:
                radial-gradient(120% 90% at 0% 100%, var(--azul-cielo) 0%, rgba(47,168,255,0) 55%),
                linear-gradient(155deg, var(--azul-profundo) 0%, var(--azul-clinica) 100%);
        }

        /* Curvas que recuerdan la onda azul de los anuncios de la clínica */
        .brand-panel::before,
        .brand-panel::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }
        .brand-panel::before {
            width: 640px; height: 640px;
            right: -260px; bottom: -300px;
            background: rgba(255,255,255,.07);
        }
        .brand-panel::after {
            width: 420px; height: 420px;
            right: -120px; bottom: -190px;
            background: rgba(255,255,255,.08);
        }

        .brand-mark {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: .85rem;
        }
        .brand-mark .mark-icon {
            width: 48px; height: 48px;
            display: grid; place-items: center;
            border-radius: 14px;
            background: rgba(255,255,255,.16);
            border: 1px solid rgba(255,255,255,.28);
        }
        .brand-mark .mark-name {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-weight: 600;
            font-size: 1.05rem;
            line-height: 1.2;
            letter-spacing: .01em;
        }
        .brand-mark .mark-name span {
            display: block;
            font-family: 'Figtree', sans-serif;
            font-weight: 400;
            font-size: .85rem;
            opacity: .8;
        }

        .brand-message {
            position: relative;
            z-index: 1;
            max-width: 30rem;
        }
        .brand-message h2 {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-weight: 700;
            font-size: clamp(2rem, 3.4vw, 3.1rem);
            line-height: 1.08;
            letter-spacing: -.02em;
            margin: 0 0 1.1rem;
        }
        .brand-message p {
            font-size: 1.05rem;
            line-height: 1.55;
            opacity: .86;
            margin: 0;
            max-width: 26rem;
        }

        .brand-foot {
            position: relative;
            z-index: 1;
            font-size: .85rem;
            opacity: .7;
        }

        /* Panel del formulario */
        .form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.5rem;
            background: var(--fondo);
        }
        .form-wrap {
            width: 100%;
            max-width: 25rem;
        }

        .form-wrap .system-name {
            font-size: .95rem;
            color: var(--tinta-suave);
            line-height: 1.45;
            margin: 0 0 .6rem;
        }
        .form-wrap h1 {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-weight: 700;
            font-size: 2.1rem;
            letter-spacing: -.02em;
            color: var(--azul-profundo);
            margin: 0 0 2rem;
        }

        .form-label {
            font-weight: 600;
            font-size: .92rem;
            color: var(--tinta);
            margin-bottom: .4rem;
        }

        .field {
            position: relative;
        }
        .field svg {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 20px; height: 20px;
            color: #8ea2c2;
            pointer-events: none;
            transition: color .15s ease;
        }
        .field .form-control {
            height: 3.15rem;
            padding-left: 2.85rem;
            border: 1.5px solid var(--linea);
            border-radius: 12px;
            background: #f8fbff;
            font-size: 1rem;
            color: var(--tinta);
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
        }
        .field .form-control:hover {
            border-color: #b5c9e6;
        }
        .field .form-control:focus {
            background: #fff;
            border-color: var(--azul-clinica);
            box-shadow: 0 0 0 4px rgba(10, 99, 230, .15);
        }
        .field:focus-within svg {
            color: var(--azul-clinica);
        }

        .btn-entrar {
            height: 3.15rem;
            margin-top: .5rem;
            border: 0;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.02rem;
            color: #fff;
            background: linear-gradient(135deg, var(--azul-clinica) 0%, #0b54c4 100%);
            box-shadow: 0 8px 18px -8px rgba(10, 99, 230, .65);
            transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
        }
        .btn-entrar:hover {
            color: #fff;
            filter: brightness(1.06);
            box-shadow: 0 12px 22px -8px rgba(10, 99, 230, .7);
        }
        .btn-entrar:active {
            transform: translateY(1px);
        }
        .btn-entrar:focus-visible {
            outline: 3px solid rgba(10, 99, 230, .35);
            outline-offset: 2px;
            color: #fff;
        }

        .msg {
            display: flex;
            gap: .65rem;
            align-items: flex-start;
            padding: .85rem 1rem;
            border-radius: 12px;
            font-size: .93rem;
            line-height: 1.4;
            margin-bottom: 1.4rem;
            border: 1px solid transparent;
        }
        .msg svg { flex: 0 0 20px; width: 20px; height: 20px; margin-top: 1px; }
        .msg-aviso  { background: #fff7e6; border-color: #f6dca6; color: #7a5200; }
        .msg-error  { background: #fdecee; border-color: #f5c2c8; color: #8f1d2c; }

        /* Responsive  */
        @media (max-width: 899.98px) {
            .login-shell { grid-template-columns: 1fr; grid-template-rows: auto 1fr; }
            .brand-panel { padding: 1.75rem 1.5rem 2.25rem; gap: 1.5rem; }
            .brand-message h2 { font-size: 1.7rem; margin-bottom: .6rem; }
            .brand-message p { font-size: .98rem; }
            .brand-foot { display: none; }
            .brand-panel::before { width: 380px; height: 380px; right: -180px; bottom: -220px; }
            .brand-panel::after  { width: 240px; height: 240px; right: -80px;  bottom: -130px; }
            .form-panel { align-items: flex-start; padding-top: 2rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; }
        }
    </style>
</head>
<body>
<div class="login-shell">

    <!-- Panel de marca -->
    <aside class="brand-panel" aria-hidden="false">
        <div class="brand-mark">
            <div class="mark-icon">
                <i class="bi bi-heart-pulse" style="font-size: 1.6rem; color: #ffffff;"></i>
            </div>
            <div class="mark-name">
                Clínica Dental
                <!-- <span>Hospital FAG</span> -->
            </div>
        </div>

        <div class="brand-message">
            <h2>Sistema de Gestión de Citas y Facturación Clínica Dental</h2>
            <p>"Cuidamos tu sonrisa, protegemos tu confianza."</p>
        </div>

        <div class="brand-foot">Acceso exclusivo para el personal de la clínica</div>
    </aside>

    <!-- Panel del formulario -->
    <main class="form-panel">
        <div class="form-wrap">
            <p class="system-name"></p>
            <h1>Iniciar sesión</h1>

            <?php if ($aviso): ?>
                <div class="msg msg-aviso" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    <span><?= htmlspecialchars($aviso) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="msg msg-error" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.01"/></svg>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label" for="usuario">Usuario</label>
                    <div class="field">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20c.8-3.6 4-5.5 8-5.5s7.2 1.9 8 5.5"/></svg>
                        <input type="text" id="usuario" name="usuario" class="form-control" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Contraseña</label>
                    <div class="field">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/></svg>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-entrar w-100">Entrar</button>
            </form>

            <!-- <p class="text-center text-muted mt-3 mb-0" style="font-size: 0.85rem;">
                Usuario de prueba: <strong>admin</strong> / <strong>admin1234</strong>
            </p> -->
        </div>
    </main>

</div>
</body>
</html>