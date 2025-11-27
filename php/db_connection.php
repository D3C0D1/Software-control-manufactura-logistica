<?php
// Incluir configuración de entorno
require_once __DIR__ . '/config.php';

// Configurar timeout para evitar bloqueos
ini_set('default_socket_timeout', 10);
ini_set('mysql.connect_timeout', 10);

// Configurar reporte de errores de mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servername = DB_HOST;
$dbport   = defined('DB_PORT') ? DB_PORT : 3306;
$username = DB_USER;
$password = DB_PASS;
$dbname = DB_NAME;

// Variables globales para las conexiones
$conn = null;
$pdo = null;

// Intentos de conexión: TCP (puerto), luego socket local
$attempts = [
    [$servername, $dbport],
    [$servername, null],
    ['127.0.0.1', $dbport],
    ['localhost', 3306],
    ['localhost', 8889],
];

$usedAttempt = null;
foreach ($attempts as [$host, $port]) {
    try {
        if ($port === null) {
            $conn = new mysqli($host, $username, $password, $dbname);
        } else {
            $conn = new mysqli($host, $username, $password, $dbname, $port);
        }
        // Configurar timeout de conexión
        if ($conn instanceof mysqli) {
            $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
        }
        // Verificar conexión
        if ($conn && !$conn->connect_error) {
            $usedAttempt = [$host, ($port ?? 'default')];
            break;
        }
    } catch (Throwable $e) {
        // Continuar con el siguiente intento
        error_log("mysqli intento fallido ($host:" . ($port ?? 'default') . "): " . $e->getMessage());
        $conn = null;
    }
}

if (!$conn || $conn->connect_error) {
    error_log("Database connection error: no se pudo conectar tras intentos");
    $msg = (defined('DEVELOPMENT') && DEVELOPMENT)
        ? "Error de conexión: no se pudo conectar a la base de datos"
        : "Error de conexión a la base de datos";
    if (defined('RETURN_JSON') && RETURN_JSON) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    } else {
        die($msg);
    }
}

// Configurar zona horaria en MySQL
$conn->query("SET time_zone = '-05:00'"); // Colombia UTC-5

// Crear conexión PDO solo si es necesaria
function getPDOConnection() {
    global $pdo;
    if ($pdo === null) {
        $servername = DB_HOST;
        $username = DB_USER;
        $password = DB_PASS;
        $dbname = DB_NAME;
        // Asegurar que el puerto esté definido dentro del alcance de la función
        $dbport = defined('DB_PORT') ? DB_PORT : 3306;
        
        try {
            $pdo = new PDO(
                "mysql:host=$servername;port=$dbport;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 10
                ]
            );
            $pdo->exec("SET time_zone = '-05:00'");
        } catch(PDOException $e) {
            error_log("PDO connection error: " . $e->getMessage());
            throw new Exception("Error de conexión PDO");
        }
    }
    return $pdo;
}
?>