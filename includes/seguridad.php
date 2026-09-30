<?php
/**
 * seguridad.php — Funciones y validaciones de seguridad comunes
 * Incluir este archivo en TODOS los módulos y APIs.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Configuración segura de sesión ANTES de iniciarla
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);   // JavaScript no puede leer la cookie
    ini_set('session.cookie_samesite', 'Lax'); // Bloquea CSRF básico
    // ini_set('session.cookie_secure', 1);   // ← Activar en producción con HTTPS

    session_start();
}

/**
 * Verifica que haya sesión activa. Si no, redirige a login.
 * Llamar al inicio de cada módulo.
 */
function requerirLogin() {
    if (!isset($_SESSION['id_usuario'])) {
        header('Location: ' . urlLogin());
        exit;
    }
    verificarExpiracion();
}

/**
 * Verifica que haya sesión activa Y que el rol esté permitido.
 * Llamar para módulos restringidos.
 */
function requerirRol($rolesPermitidos = []) {
    requerirLogin();
    if (!in_array($_SESSION['rol'] ?? '', $rolesPermitidos)) {
        http_response_code(403);
        die('⛔ Acceso denegado: no tienes permisos para ver esta sección.');
    }
}

/**
 * Verifica que haya sesión para endpoints de API.
 * Devuelve JSON en vez de redirigir.
 */
function requerirLoginAPI() {
    if (!isset($_SESSION['id_usuario'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'No autorizado. Inicia sesión.']);
        exit;
    }
    verificarExpiracion();
}

/**
 * Verifica que el rol esté permitido en una API.
 */
function requerirRolAPI($rolesPermitidos = []) {
    requerirLoginAPI();
    if (!in_array($_SESSION['rol'] ?? '', $rolesPermitidos)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Permisos insuficientes.']);
        exit;
    }
}

/**
 * Expira la sesión si pasaron más de 60 minutos sin actividad.
 */
function verificarExpiracion() {
    $timeout = 60 * 60; // 60 minutos
    if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad']) > $timeout) {
        session_unset();
        session_destroy();
        header('Location: ' . urlLogin() . '?expirada=1');
        exit;
    }
    $_SESSION['ultima_actividad'] = time();
}

/**
 * Genera un token CSRF para formularios.
 */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida el token CSRF recibido.
 */
function validarCSRF($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Devuelve la URL correcta al login según el contexto (API o módulo).
 */
function urlLogin() {
    // Si estamos en /api/ o /modulos/, subir un nivel
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($script, '/api/') !== false || strpos($script, '/modulos/') !== false) {
        return '../login.php';
    }
    return 'login.php';
}