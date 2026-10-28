<?php
$clave = 'admin1234';   // La contraseña que quieres usar
echo "Contraseña: <strong>$clave</strong><br>";
echo "Hash: <br><code>" . password_hash($clave, PASSWORD_BCRYPT) . "</code>";