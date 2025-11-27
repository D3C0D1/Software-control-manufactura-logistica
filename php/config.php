<?php
// Configuración de entorno

// Detectar si estamos en entorno local o producción
if (!function_exists('isLocalEnvironment')) {
    function isLocalEnvironment() {
        // Considerar CLI como entorno local para permitir scripts de diagnóstico
        if (php_sapi_name() === 'cli') {
            return true;
        }
        $localHosts = ['localhost', '127.0.0.1', '::1'];
        $serverName = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? '';
        return in_array($serverName, $localHosts) || strpos($serverName, '.local') !== false;
    }
}

// Detectar si el stack local es AMPPS
if (!function_exists('isAMPPS')) {
    function isAMPPS() {
        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $serverSoft = $_SERVER['SERVER_SOFTWARE'] ?? '';
        // Rutas típicas de instalación de AMPPS
        $amppsPaths = [
            '/Applications/AMPPS',                 // macOS
            'C:\\Program Files\\Ampps',         // Windows (64-bit)
            'C:\\Ampps'                          // Windows (portable/custom)
        ];
        foreach ($amppsPaths as $p) {
            if (@file_exists($p)) {
                return true;
            }
        }
        // Heurísticas adicionales
        if (stripos($docRoot, 'AMPPS') !== false) { return true; }
        if (stripos($serverSoft, 'AMPPS') !== false) { return true; }
        return false;
    }
}

// Configuración de desarrollo
define('DEVELOPMENT', true); // Cambiar a false en producción

// Configurar rutas base según el entorno
if (isLocalEnvironment()) {
    // Determinar el stack local (AMPPS vs MAMP)
    $localHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (isAMPPS()) {
        // Configuración local (AMPPS)
        define('BASE_URL', 'http://' . $localHost);
        define('BASE_PATH', '');
        // AMPPS normalmente usa MySQL en el puerto 3306 y root/mysql por defecto
        define('DB_HOST', 'localhost');
        define('DB_PORT', 3306);
        define('DB_USER', 'root');
        define('DB_PASS', 'mysql');
        define('DB_NAME', 'glamcity_db');
    } else {
        // Configuración local (MAMP)
        // Mantener compatibilidad con el puerto típico de MAMP
        if (strpos($localHost, ':') === false) { $localHost .= ':8888'; }
        define('BASE_URL', 'http://' . $localHost);
        define('BASE_PATH', '/glamcity-');
        // MAMP: MySQL suele estar en 8889 con usuario/clave root/root
        define('DB_HOST', 'localhost');
        define('DB_PORT', 8889);
        define('DB_USER', 'root');
        define('DB_PASS', 'root');
        define('DB_NAME', 'glamcity_db');
    }
} else {
    // Configuración de producción (Hostinger)
    define('BASE_URL', 'https://' . $_SERVER['HTTP_HOST']);
    define('BASE_PATH', '');
    define('DB_HOST', 'localhost'); // Hostinger usa localhost
    define('DB_USER', 'u469305563_glamcity_db'); // Cambiar por el usuario real de Hostinger
    define('DB_PASS', 'A0347a1312#'); // Cambiar por la contraseña real
    define('DB_NAME', 'u469305563_glamcity_db'); // Cambiar por el nombre real de la BD
    $servername = "localhost";

}

// Función para obtener URL absoluta
function getAbsoluteUrl($relativePath) {
    return BASE_URL . BASE_PATH . '/' . ltrim($relativePath, '/');
}

// Función para obtener ruta del archivo
function getFilePath($relativePath) {
    return $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . '/' . ltrim($relativePath, '/');
}

// Configuración de zona horaria
date_default_timezone_set('America/Bogota');

// Configuración de errores según el entorno
if (isLocalEnvironment()) {
    // En local, mostrar errores para debugging
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    // En producción, ocultar errores y registrarlos
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    ini_set('error_log', getFilePath('logs/php_errors.log'));
}
?>