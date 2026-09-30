<?php
require_once 'conexion.php';

echo "<h1>Conexión exitosa ✅</h1>";

// Contar pacientes para verificar que la base tiene datos
$total = $pdo->query("SELECT COUNT(*) FROM pacientes")->fetchColumn();
echo "<p>Pacientes registrados: <strong>$total</strong></p>";

// Mostrar la versión de MySQL
$version = $pdo->query("SELECT VERSION()")->fetchColumn();
echo "<p>Versión de MySQL: $version</p>";