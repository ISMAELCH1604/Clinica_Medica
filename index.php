<?php
session_start();

if (isset($_SESSION['id_usuario'])) {
    header('Location: modulos/dashboard.php');
} else {
    header('Location: login.php');
}
exit;