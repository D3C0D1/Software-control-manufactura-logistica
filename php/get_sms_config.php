<?php
require_once 'db_connection.php';

function getSMSConfig() {
    $config = [];
    try {
        $pdo = getPDOConnection();
        // Asegurar que la tabla exista con el esquema esperado
        ensureSMSConfigSchema($pdo);
        $stmt = $pdo->query("SELECT config_name, config_value FROM sms_config");
        if ($stmt) {
            foreach ($stmt as $row) {
                $config[$row['config_name']] = $row['config_value'];
            }
        }
    } catch (Exception $e) {
        // Si falla la conexión, dejamos $config vacío y retornamos defaults
    }

    // Valores por defecto si no existen en la base de datos
    $defaults = [
        'onurix_client_id' => '7389',
        'onurix_api_key' => '',
        'sms_enabled' => '1',
        'sms_template' => 'Su pedido #{numero_guia} ha sido creado exitosamente. Gracias por confiar en nosotros.',
        'sms_finalize_enabled' => '1',
        'sms_finalize_template' => '¡Hola {nombre_cliente}! Tu pedido #{numero_guia} ya está listo para recoger. ¡Gracias por confiar en Glamcity!',
        // Configuración de Proxy/Webhook para envío de SMS vía Hosting B
        'sms_proxy_enabled' => '0',
        'sms_proxy_url' => (function_exists('isLocalEnvironment') && isLocalEnvironment())
            ? (function_exists('getAbsoluteUrl') ? getAbsoluteUrl('proxy/archivo.php') : 'http://localhost/proxy/archivo.php')
            : 'https://impactusdigital.com.co/archivo.php',
        'sms_proxy_token' => '',
        'sms_proxy_send_credentials' => '1'
    ];

    // Normalización: campos de texto no deben mostrar "0" en el formulario
    foreach (['sms_template','sms_finalize_template','sms_proxy_url','sms_proxy_token','onurix_api_key'] as $k) {
        if (isset($config[$k]) && $config[$k] === '0') {
            $config[$k] = '';
        }
    }

    $merged = array_merge($defaults, $config);

    // Si tras la fusión quedan vacíos, usar los defaults amigables
    foreach (['sms_template','sms_finalize_template','sms_proxy_url'] as $k) {
        if (!isset($merged[$k]) || trim($merged[$k]) === '') {
            $merged[$k] = $defaults[$k];
        }
    }

    return $merged;
}

function updateSMSConfig($config_name, $config_value) {
    try {
        $pdo = getPDOConnection();
        ensureSMSConfigSchema($pdo);

        // Upsert robusto sin depender de UNIQUE: verificar existencia y actualizar/insertar según corresponda
        $checkStmt = $pdo->prepare("SELECT id FROM sms_config WHERE config_name = :name LIMIT 1");
        if (!$checkStmt) { 
            error_log("updateSMSConfig prepare failed (SELECT) for '$config_name'");
            return false; 
        }
        $checkStmt->execute([':name' => $config_name]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing && isset($existing['id'])) {
            $updStmt = $pdo->prepare("UPDATE sms_config SET config_value = :value, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            if (!$updStmt) { 
                error_log("updateSMSConfig prepare failed (UPDATE) for '$config_name' id=" . $existing['id']);
                return false; 
            }
            return $updStmt->execute([':value' => $config_value, ':id' => $existing['id']]);
        } else {
            // Si la tabla no tiene AUTO_INCREMENT, insertar con ID manual (MAX(id)+1)
            $hasAI = smsConfigHasAutoIncrement(getPDOConnection());
            if ($hasAI) {
                $insStmt = $pdo->prepare("INSERT INTO sms_config (config_name, config_value, description) VALUES (:name, :value, NULL)");
                if (!$insStmt) { 
                    error_log("updateSMSConfig prepare failed (INSERT) for '$config_name'");
                    return false; 
                }
                return $insStmt->execute([':name' => $config_name, ':value' => $config_value]);
            } else {
                $insStmt = $pdo->prepare("INSERT INTO sms_config (id, config_name, config_value, description) 
                                           SELECT IFNULL(MAX(id), 0) + 1, :name, :value, NULL FROM sms_config");
                if (!$insStmt) {
                    error_log("updateSMSConfig prepare failed (INSERT with manual id) for '$config_name'");
                    return false;
                }
                $ok = $insStmt->execute([':name' => $config_name, ':value' => $config_value]);
                if (!$ok) {
                    error_log("updateSMSConfig execute failed (INSERT with manual id) for '$config_name'");
                }
                return $ok;
            }
        }
    } catch (Exception $e) {
        error_log('updateSMSConfig error: ' . $e->getMessage());
        return false;
    }
}

function ensureSMSConfigSchema(PDO $pdo) {
    // Crear tabla si no existe (esquema esperado)
    $pdo->exec("CREATE TABLE IF NOT EXISTS sms_config (
        id INT NOT NULL,
        config_name VARCHAR(50) NOT NULL,
        config_value TEXT NOT NULL,
        description VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Normalizar tabla existente importada de dumps antiguos (orden correcto: PK -> AUTO_INCREMENT -> índice único)
    try {
        // 1) Verificar PK en 'id' y añadirla si falta
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sms_config' AND CONSTRAINT_NAME = 'PRIMARY' AND COLUMN_NAME = 'id'");
        $stmt->execute();
        $pkCnt = (int)($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);
        if ($pkCnt === 0) {
            try {
                $pdo->exec("ALTER TABLE sms_config ADD PRIMARY KEY (id)");
            } catch (Exception $e) {
                // Si falla (por valores duplicados/nulos), resecuenciar IDs creando una PK temporal
                error_log('ensureSMSConfigSchema: ADD PRIMARY KEY failed, resecuenciando id: ' . $e->getMessage());
                $pdo->exec("ALTER TABLE sms_config ADD COLUMN _tmp_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
                $pdo->exec("ALTER TABLE sms_config DROP COLUMN id");
                $pdo->exec("ALTER TABLE sms_config CHANGE COLUMN _tmp_id id INT NOT NULL");
            }
        }

        // 2) Verificar si 'id' es AUTO_INCREMENT y ajustarlo si falta
        $stmt = $pdo->prepare("SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sms_config' AND COLUMN_NAME = 'id' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $isAuto = isset($row['EXTRA']) && stripos($row['EXTRA'], 'auto_increment') !== false;
        if (!$isAuto) {
            $pdo->exec("ALTER TABLE sms_config MODIFY COLUMN id INT NOT NULL AUTO_INCREMENT");
        }

        // 3) Verificar índice único en config_name
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sms_config' AND INDEX_NAME = 'uniq_config_name'");
        $stmt->execute();
        $uniqCnt = (int)($stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);
        if ($uniqCnt === 0) {
            $pdo->exec("ALTER TABLE sms_config ADD UNIQUE KEY uniq_config_name (config_name)");
        }
    } catch (Exception $e) {
        error_log('ensureSMSConfigSchema normalization warning: ' . $e->getMessage());
    }
}

function smsConfigHasAutoIncrement(PDO $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sms_config' AND COLUMN_NAME = 'id' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($row['EXTRA']) && stripos($row['EXTRA'], 'auto_increment') !== false;
    } catch (Exception $e) {
        return false;
    }
}

function ensureSMSNotificationsSchema(PDO $pdo) {
    // Crear tabla de notificaciones SMS si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS sms_notifications (
        id INT NOT NULL AUTO_INCREMENT,
        pedido_id INT NULL,
        numero_telefono VARCHAR(30) NOT NULL,
        mensaje TEXT NOT NULL,
        estado VARCHAR(20) NOT NULL DEFAULT 'pendiente',
        error TEXT NULL,
        fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        fecha_envio TIMESTAMP NULL DEFAULT NULL,
        PRIMARY KEY (id),
        KEY idx_pedido_id (pedido_id),
        KEY idx_estado (estado)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

function ensureSMSErrorLogsSchema(PDO $pdo) {
    // Crear tabla de historial de errores de SMS si no existe
    $pdo->exec("CREATE TABLE IF NOT EXISTS sms_error_logs (
        id INT NOT NULL AUTO_INCREMENT,
        pedido_id INT NULL,
        sms_id INT NULL,
        status_code INT NULL,
        error_message VARCHAR(255) NULL,
        endpoint VARCHAR(255) NULL,
        environment VARCHAR(50) NULL,
        request_body TEXT NULL,
        response_body TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_status_code (status_code),
        KEY idx_pedido_id (pedido_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}
?>