<?php
require_once 'includes/seguridad.php';

if (isset($_SESSION['id_usuario'])) {
    header('Location: modulos/dashboard.php');
} else {
    header('Location: login.php');
}
exit;