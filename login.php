<?php
session_start();
require_once 'conexion.php';

$error = '';

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
            $_SESSION['id_usuario']     = $u['id_usuario'];
            $_SESSION['nombre_usuario'] = $u['nombre_usuario'];
            $_SESSION['rol']            = $u['rol'];
            header('Location: modulos/dashboard.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión — Clínica Dental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4">🦷 Clínica Dental</h3>
                    <h5 class="text-center text-muted mb-4">Iniciar sesión</h5>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Usuario</label>
                            <input type="text" name="usuario" class="form-control" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Entrar</button>
                    </form>

                    <p class="text-center text-muted mt-3 mb-0" style="font-size: 0.85rem;">
                        Usuario de prueba: <strong>admin</strong> / <strong>admin1234</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>