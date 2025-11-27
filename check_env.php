<?php
// Página de diagnóstico de entorno y conexión a base de datos
// Uso: abrir en navegador para ver detecciones y conexión activa

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/db_connection.php';

$isLocal = function_exists('isLocalEnvironment') ? isLocalEnvironment() : false;
$isAmpps = function_exists('isAMPPS') ? isAMPPS() : false;
$dbHost = defined('DB_HOST') ? DB_HOST : '(no definido)';
$dbPort = defined('DB_PORT') ? DB_PORT : '(no definido)';
$dbUser = defined('DB_USER') ? DB_USER : '(no definido)';
$dbName = defined('DB_NAME') ? DB_NAME : '(no definido)';

// Obtener info de conexión activa
$connInfo = [
    'server_version' => null,
    'server_host' => null,
    'server_port' => null,
    'db' => null,
    'host_info' => null,
];

try {
    if (isset($conn) && ($conn instanceof mysqli) && !$conn->connect_error) {
        $connInfo['host_info'] = method_exists($conn, 'host_info') ? $conn->host_info : null;
        $rs = $conn->query("SELECT @@hostname AS host, @@port AS port, @@version AS version, DATABASE() AS db");
        if ($rs && $row = $rs->fetch_assoc()) {
            $connInfo['server_host'] = $row['host'] ?? null;
            $connInfo['server_port'] = $row['port'] ?? null;
            $connInfo['server_version'] = $row['version'] ?? null;
            $connInfo['db'] = $row['db'] ?? null;
        }
        if ($rs) { $rs->close(); }
    }
} catch (Throwable $e) {
    // Ignorar errores y mostrar solo lo disponible
}

?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Diagnóstico de Entorno</title>
  <style>
    body { font-family: -apple-system, Segoe UI, Roboto, sans-serif; margin: 24px; }
    h1 { margin-bottom: 8px; }
    code { background: #f5f5f7; padding: 2px 6px; border-radius: 4px; }
    .ok { color: #087f5b; }
    .warn { color: #e67700; }
    .err { color: #c92a2a; }
    .card { border: 1px solid #e9ecef; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(280px,1fr)); gap: 16px; }
    .kv { margin: 6px 0; }
    .kv .k { color: #666; width: 160px; display: inline-block; }
  </style>
</head>
<body>
  <h1>🔎 Diagnóstico de Entorno</h1>
  <p class="kv"><span class="k">Entorno local:</span> <strong><?php echo $isLocal ? 'Sí' : 'No'; ?></strong></p>
  <p class="kv"><span class="k">Detectado AMPPS:</span> <strong><?php echo $isAmpps ? 'Sí' : 'No'; ?></strong></p>

  <div class="grid">
    <div class="card">
      <h2>Configuración DB</h2>
      <p class="kv"><span class="k">DB_HOST:</span> <code><?php echo htmlspecialchars($dbHost); ?></code></p>
      <p class="kv"><span class="k">DB_PORT:</span> <code><?php echo htmlspecialchars((string)$dbPort); ?></code></p>
      <p class="kv"><span class="k">DB_USER:</span> <code><?php echo htmlspecialchars($dbUser); ?></code></p>
      <p class="kv"><span class="k">DB_NAME:</span> <code><?php echo htmlspecialchars($dbName); ?></code></p>
    </div>
    <div class="card">
      <h2>Conexión Activa</h2>
      <?php if ($conn instanceof mysqli && !$conn->connect_error): ?>
        <p class="kv"><span class="k">Host info:</span> <code><?php echo htmlspecialchars((string)$connInfo['host_info']); ?></code></p>
        <p class="kv"><span class="k">Server host:</span> <code><?php echo htmlspecialchars((string)$connInfo['server_host']); ?></code></p>
        <p class="kv"><span class="k">Server port:</span> <code><?php echo htmlspecialchars((string)$connInfo['server_port']); ?></code></p>
        <p class="kv"><span class="k">Server version:</span> <code><?php echo htmlspecialchars((string)$connInfo['server_version']); ?></code></p>
        <p class="kv"><span class="k">Base actual:</span> <code><?php echo htmlspecialchars((string)$connInfo['db']); ?></code></p>
        <p class="ok">✅ Conexión establecida correctamente.</p>
      <?php else: ?>
        <p class="err">❌ No hay conexión activa o se produjo un error.</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <h2>Rutas</h2>
    <p class="kv"><span class="k">BASE_URL:</span> <code><?php echo defined('BASE_URL') ? BASE_URL : '(no definido)'; ?></code></p>
    <p class="kv"><span class="k">BASE_PATH:</span> <code><?php echo defined('BASE_PATH') ? BASE_PATH : '(no definido)'; ?></code></p>
    <p class="kv"><span class="k">DOCUMENT_ROOT:</span> <code><?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? ''); ?></code></p>
  </div>

  <p><a href="check_tables.php">Ver tablas disponibles →</a></p>
</body>
</html>