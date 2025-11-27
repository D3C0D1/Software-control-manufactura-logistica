<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'php/get_sms_config.php';

// Configuración general del sitio
$config = [
    'nombre_empresa' => 'Glamcity',
    'color_primario' => '#4a90e2',
    'color_secundario' => '#f5a623',
    'color_fondo' => '#f4f7fc',
    'color_texto' => '#333333'
];

// Obtener configuración SMS
$sms_config = getSMSConfig();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body class="dashboard-page">
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <h3><?php echo htmlspecialchars($config['nombre_empresa']); ?></h3>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
                <a href="pqrs_management.php" class="nav-item"><i class="fas fa-envelope-open-text"></i><span>Gestión PQRS</span></a>
                <a href="admin_users.php" class="nav-item"><i class="fas fa-users"></i><span>Usuarios</span></a>
                <a href="reports.php" class="nav-item"><i class="fas fa-chart-bar"></i><span>Reportes</span></a>
                <a href="configuracion.php" class="nav-item active"><i class="fas fa-cog"></i><span>Configuración</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="php/logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i><span>Cerrar Sesión</span></a>
            </div>
        </aside>
        <main class="main-content">
            <header class="main-header">
                <h1>Configuración del Sitio</h1>
                <?php if (isset($_SESSION['config_message'])): ?>
                    <div class="alert alert-<?php echo $_SESSION['config_type']; ?>">
                        <?php 
                        echo htmlspecialchars($_SESSION['config_message']); 
                        unset($_SESSION['config_message'], $_SESSION['config_type']);
                        ?>
                    </div>
                <?php endif; ?>
            </header>

            <div class="content-panel">
                <h2>Configuración General</h2>
                <form action="php/update_config.php" method="POST">
                    <div class="form-group">
                        <label for="nombre_empresa">Nombre de la Empresa</label>
                        <input type="text" id="nombre_empresa" name="nombre_empresa" value="<?php echo htmlspecialchars($config['nombre_empresa']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="color_primario">Color Primario</label>
                        <input type="color" id="color_primario" name="color_primario" value="<?php echo htmlspecialchars($config['color_primario']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="color_secundario">Color Secundario</label>
                        <input type="color" id="color_secundario" name="color_secundario" value="<?php echo htmlspecialchars($config['color_secundario']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="color_fondo">Color de Fondo</label>
                        <input type="color" id="color_fondo" name="color_fondo" value="<?php echo htmlspecialchars($config['color_fondo']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="color_texto">Color de Texto</label>
                        <input type="color" id="color_texto" name="color_texto" value="<?php echo htmlspecialchars($config['color_texto']); ?>">
                    </div>
                    <button type="submit" class="btn-submit">Guardar Cambios Generales</button>
                </form>
            </div>

            <div class="content-panel" style="margin-top: 20px;">
                <h2>Configuración SMS</h2>
                <form action="php/update_sms_config.php" method="POST">
                    <div class="form-group">
                        <label for="onurix_client_id">Client ID de Onurix</label>
                        <input type="text" id="onurix_client_id" name="onurix_client_id" value="<?php echo htmlspecialchars($sms_config['onurix_client_id']); ?>" required>
                        <small>ID del cliente proporcionado por Onurix SMS</small>
                    </div>
                    <div class="form-group">
                        <label for="onurix_api_key">API Key de Onurix</label>
                        <input type="password" id="onurix_api_key" name="onurix_api_key" value="<?php echo htmlspecialchars($sms_config['onurix_api_key']); ?>" required>
                        <small>Clave API proporcionada por Onurix SMS</small>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="sms_enabled" <?php echo $sms_config['sms_enabled'] == '1' ? 'checked' : ''; ?>>
                            Habilitar envío de SMS
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="sms_finalize_enabled" <?php echo ($sms_config['sms_finalize_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            Enviar SMS al finalizar pedido
                        </label>
                        <small>Controla la notificación al cliente al mover el pedido a Finalizados.</small>
                    </div>
                    <div class="form-group">
                        <label for="sms_template">Plantilla del mensaje SMS</label>
                        <textarea id="sms_template" name="sms_template" rows="3" required><?php echo htmlspecialchars($sms_config['sms_template']); ?></textarea>
                        <small>Placeholders soportados: {nombre_cliente}, {numero_guia}</small>
                    </div>
                    <div class="form-group">
                        <label for="sms_finalize_template">Plantilla SMS al finalizar</label>
                        <textarea id="sms_finalize_template" name="sms_finalize_template" rows="3" placeholder="¡Hola {nombre_cliente}! Tu pedido #{numero_guia} ya está listo para recoger."><?php echo htmlspecialchars($sms_config['sms_finalize_template'] ?? ''); ?></textarea>
                        <small>Placeholders soportados: {nombre_cliente}, {numero_guia}</small>
                    </div>
                    <hr>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="sms_proxy_enabled" <?php echo ($sms_config['sms_proxy_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
                            Usar Webhook/Proxy de SMS (Hosting B)
                        </label>
                        <small>Si está habilitado, los SMS se enviarán a través de <strong>impactusdigital.com.co/archivo.php</strong>.</small>
                    </div>

                    <div class="form-group">
                        <label for="sms_proxy_url">URL del Webhook (Hosting B):</label>
                        <input type="text" id="sms_proxy_url" name="sms_proxy_url" value="<?php echo htmlspecialchars($sms_config['sms_proxy_url'] ?? ''); ?>" placeholder="https://impactusdigital.com.co/archivo.php">
                        <small>Ruta pública del script puente que reenvía a Onurix.</small>
                    </div>

                    <div class="form-group">
                        <label for="sms_proxy_token">Token de Autenticación (opcional):</label>
                        <input type="password" id="sms_proxy_token" name="sms_proxy_token" value="<?php echo htmlspecialchars($sms_config['sms_proxy_token'] ?? ''); ?>" placeholder="Ej: 64 caracteres aleatorios">
                        <small>Si se deja vacío, el proxy aceptará peticiones sin encabezado Authorization.</small>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="sms_proxy_send_credentials" <?php echo ($sms_config['sms_proxy_send_credentials'] ?? '0') == '1' ? 'checked' : ''; ?>>
                            Incluir credenciales de Onurix en la petición al proxy
                        </label>
                        <small>Si activas, se enviarán client y key al proxy; si no, el proxy debe tener sus propias credenciales.</small>
                    </div>
                    <button type="submit" class="btn-submit">Guardar Configuración SMS</button>
                </form>
            </div>
        </main>
    </div>
    <style>
        .content-panel {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .content-panel h2 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #4a90e2;
            padding-bottom: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }
        .form-group input[type="text"], 
        .form-group input[type="password"], 
        .form-group input[type="color"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .form-group input[type="color"] {
            height: 48px;
            padding: 5px;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .checkbox-label input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }
        .btn-submit {
            background-color: var(--primary-color);
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }
        .btn-submit:hover {
            background-color: #3a7ac8;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</body>
</html>