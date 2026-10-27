<?php
require_once '../conexion.php';
require_once '../includes/seguridad.php';
header('Content-Type: application/json; charset=utf-8');

// Solo administrador y auditor pueden ver la bitácora
requerirRolAPI(['administrador', 'auditor']);

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

try {
    switch ($accion) {

        case 'listar':
            $condiciones = [];
            $params = [];

            if (!empty($_GET['id_usuario'])) {
                $condiciones[] = "b.id_usuario = ?";
                $params[] = (int)$_GET['id_usuario'];
            }
            if (!empty($_GET['tabla'])) {
                $condiciones[] = "b.tabla_afectada = ?";
                $params[] = $_GET['tabla'];
            }
            if (!empty($_GET['operacion'])) {
                $condiciones[] = "b.operacion = ?";
                $params[] = $_GET['operacion'];
            }
            if (!empty($_GET['desde'])) {
                $condiciones[] = "DATE(b.fecha_hora) >= ?";
                $params[] = $_GET['desde'];
            }
            if (!empty($_GET['hasta'])) {
                $condiciones[] = "DATE(b.fecha_hora) <= ?";
                $params[] = $_GET['hasta'];
            }

            $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

            $sql = "
                SELECT b.id_bitacora, b.fecha_hora,
                       COALESCE(u.nombre_usuario, '(sistema)') AS usuario,
                       u.rol, b.tabla_afectada, b.operacion,
                       b.valor_anterior, b.valor_nuevo
                FROM bitacora_auditoria b
                LEFT JOIN usuarios u ON u.id_usuario = b.id_usuario
                $where
                ORDER BY b.fecha_hora DESC
                LIMIT 500
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['ok' => true, 'datos' => $stmt->fetchAll()]);
            break;

        case 'estadisticas':
            $total = $pdo->query("SELECT COUNT(*) FROM bitacora_auditoria")->fetchColumn();
            $hoy   = $pdo->query("SELECT COUNT(*) FROM bitacora_auditoria WHERE DATE(fecha_hora) = CURDATE()")->fetchColumn();

            $porOperacion = $pdo->query("
                SELECT operacion, COUNT(*) AS total
                FROM bitacora_auditoria GROUP BY operacion
            ")->fetchAll();

            $porTabla = $pdo->query("
                SELECT tabla_afectada, COUNT(*) AS total
                FROM bitacora_auditoria
                GROUP BY tabla_afectada ORDER BY total DESC LIMIT 8
            ")->fetchAll();

            $porUsuario = $pdo->query("
                SELECT COALESCE(u.nombre_usuario, '(sistema)') AS usuario,
                       u.rol, COUNT(*) AS total
                FROM bitacora_auditoria b
                LEFT JOIN usuarios u ON u.id_usuario = b.id_usuario
                GROUP BY u.nombre_usuario, u.rol ORDER BY total DESC
            ")->fetchAll();

            echo json_encode([
                'ok' => true,
                'total' => $total,
                'hoy' => $hoy,
                'por_operacion' => $porOperacion,
                'por_tabla' => $porTabla,
                'por_usuario' => $porUsuario
            ]);
            break;

        case 'opciones':
            $usuarios = $pdo->query("
                SELECT DISTINCT id_usuario, nombre_usuario, rol
                FROM usuarios ORDER BY nombre_usuario
            ")->fetchAll();

            $tablas = $pdo->query("
                SELECT DISTINCT tabla_afectada FROM bitacora_auditoria ORDER BY tabla_afectada
            ")->fetchAll(PDO::FETCH_COLUMN);

            echo json_encode([
                'ok' => true,
                'usuarios' => $usuarios,
                'tablas' => $tablas,
                'operaciones' => ['INSERT', 'UPDATE', 'DELETE']
            ]);
            break;

        ##
        case 'exportar_excel':
            header('Content-Type: application/vnd.ms-excel; charset=utf-8');
            header('Content-Disposition: attachment; filename="Bitacora_Auditoria_' . date('Ymd_His') . '.xls"');
            header('Pragma: no-cache');
            header('Expires: 0');

            echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
            echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>';
            echo '<body>';
            echo '<table border="1">';
            echo '<tr>
                    <th style="background-color: #f2f2f2;">ID</th>
                    <th style="background-color: #f2f2f2;">Fecha y Hora</th>
                    <th style="background-color: #f2f2f2;">Usuario</th>
                    <th style="background-color: #f2f2f2;">Rol</th>
                    <th style="background-color: #f2f2f2;">Módulo</th>
                    <th style="background-color: #f2f2f2;">Acción</th>
                    <th style="background-color: #f2f2f2;">Detalle del Cambio</th>
                  </tr>';

            $nombresCampos = [
                'id_paciente' => 'Paciente', 'id_cita' => 'Cita', 'id_factura' => 'Factura',
                'id_pago' => 'Pago', 'id_usuario' => 'Usuario Afectado', 'id_odontologo' => 'Odontólogo',
                'id_consultorio' => 'Consultorio', 'nombre_usuario' => 'Nombre de Usuario',
                'estado' => 'Estado', 'fecha_hora' => 'Fecha/Hora', 'total' => 'Total (Q)',
                'monto' => 'Monto (Q)', 'metodo_pago' => 'Método de Pago', 'observaciones' => 'Observaciones',
                'diagnostico' => 'Diagnóstico', 'nombre' => 'Nombre', 'apellido' => 'Apellido'
            ];

            $nombresTablas = [
                'citas' => 'Cita', 'pacientes' => 'Paciente', 'facturas' => 'Factura',
                'pagos' => 'Pago', 'usuarios' => 'Usuario', 'odontologos' => 'Odontólogo',
                'consultorios' => 'Consultorio', 'tratamientos' => 'Tratamiento',
                'expedientes_clinicos' => 'Expediente Clínico', 'detalle_tratamiento_cita' => 'Detalle de Tratamiento'
            ];

            $stmt = $pdo->query("
                SELECT b.id_bitacora, b.fecha_hora,
                       COALESCE(u.nombre_usuario, 'Sistema Automático') AS usuario,
                       u.rol, b.tabla_afectada, b.operacion,
                       b.valor_anterior, b.valor_nuevo
                FROM bitacora_auditoria b
                LEFT JOIN usuarios u ON u.id_usuario = b.id_usuario
                ORDER BY b.fecha_hora DESC LIMIT 5000
            ");
            
            while ($r = $stmt->fetch()) {
                $accion = '';
                if($r['operacion'] == 'INSERT') $accion = 'CREACIÓN';
                if($r['operacion'] == 'UPDATE') $accion = 'ACTUALIZACIÓN';
                if($r['operacion'] == 'DELETE') $accion = 'ELIMINACIÓN';

                $entidad = $nombresTablas[$r['tabla_afectada']] ?? $r['tabla_afectada'];
                
                $viejo = json_decode($r['valor_anterior'] ?? '{}', true) ?: [];
                $nuevo = json_decode($r['valor_nuevo'] ?? '{}', true) ?: [];
                
                // Buscar el ID afectado para darle contexto (Ej. Cita #8)
                $idRegistro = '';
                foreach ($nuevo as $k => $v) { if (strpos($k, 'id_') === 0) { $idRegistro = " #" . $v; break; } }
                if (!$idRegistro && !empty($viejo)) {
                    foreach ($viejo as $k => $v) { if (strpos($k, 'id_') === 0) { $idRegistro = " #" . $v; break; } }
                }

                $prefijo = $entidad . $idRegistro;
                $detalle = '';

                if ($r['operacion'] === 'INSERT') {
                    $partes = [];
                    foreach ($nuevo as $clave => $valor) {
                        if ($clave === 'password_cambiada' || strpos($clave, 'id_') === 0) continue;
                        $nom = $nombresCampos[$clave] ?? ucfirst(str_replace('_', ' ', $clave));
                        $partes[] = "{$nom}: {$valor}";
                    }
                    $detalle = empty($partes) ? "Se creó un nuevo registro en el módulo." : "Se creó con los datos: " . implode(" | ", $partes);
                
                } elseif ($r['operacion'] === 'DELETE') {
                    $detalle = "Se eliminó permanentemente este registro del sistema.";
                
                } else {
                    // UPDATE
                    $cambios = [];
                    foreach ($nuevo as $clave => $valorNuevo) {
                        if ($clave === 'password_cambiada' || strpos($clave, 'id_') === 0) continue;
                        $valorViejo = $viejo[$clave] ?? null;
                        
                        if ($valorViejo !== $valorNuevo) {
                            $nombreAmigable = $nombresCampos[$clave] ?? ucfirst(str_replace('_', ' ', $clave));
                            $vViejo = ($valorViejo === null || $valorViejo === '') ? 'vacío' : $valorViejo;
                            $vNuevo = ($valorNuevo === null || $valorNuevo === '') ? 'vacío' : $valorNuevo;
                            $cambios[] = "{$nombreAmigable}: de '{$vViejo}' a '{$vNuevo}'";
                        }
                    }
                    
                    if (empty($cambios)) {
                        // Si no hay cambios, en lugar de poner "sin cambios", le damos una explicación lógica
                        if ($r['usuario'] === 'Sistema Automático') {
                            $detalle = "El sistema realizó una actualización automática de mantenimiento.";
                        } else {
                            $detalle = "El usuario guardó el registro, pero no modificó ningún dato visible.";
                        }
                    } else {
                        $detalle = "Se actualizó: " . implode(" | ", $cambios);
                    }
                }

                $textoCelda = "{$prefijo}: {$detalle}";

                echo '<tr>';
                echo '<td>' . $r['id_bitacora'] . '</td>';
                echo '<td>' . $r['fecha_hora'] . '</td>';
                echo '<td>' . htmlspecialchars($r['usuario']) . '</td>';
                echo '<td>' . htmlspecialchars($r['rol'] ?? '-') . '</td>';
                echo '<td>' . htmlspecialchars($r['tabla_afectada']) . '</td>';
                echo '<td>' . $accion . '</td>';
                echo '<td>' . htmlspecialchars($textoCelda) . '</td>';
                echo '</tr>';
            }
            echo '</table></body></html>';
            exit;
        ##

        default:
            echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}