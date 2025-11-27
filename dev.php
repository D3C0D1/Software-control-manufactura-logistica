<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array(($_SESSION['rol_id'] ?? 0), [1, 2])) {
    header("Location: login.php?error=unauthorized");
    exit();
}

require_once 'php/db_connection.php';
require_once 'php/get_sms_config.php';

$sms_config = getSMSConfig();

// Mensajes de sesión
$notice = $_SESSION['config_message'] ?? '';
$notice_type = $_SESSION['config_type'] ?? '';
unset($_SESSION['config_message'], $_SESSION['config_type']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Herramientas de Desarrollo - Glamcity</title>
    <link rel="stylesheet" href="css/config.css" />
    <link rel="stylesheet" href="vendor/fontawesome/css/all.min.css" />
    <style>
        .dev-container { max-width: 900px; margin: 20px auto; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .card-header { padding: 16px 20px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px; }
        .card-body { padding: 20px; }
        .form-group { margin-bottom: 16px; }
        .alert { margin: 10px 0; padding: 10px 12px; border-radius: 6px; }
        .alert-success { background: #e6f7ea; color: #1e6b2d; }
        .alert-error { background: #fdecea; color: #9f1c1c; }
        .badge { display: inline-block; background: #f5f5f5; border: 1px solid #eee; padding: 4px 8px; border-radius: 6px; margin-left: 6px; }
        .actions { display: flex; gap: 8px; }
        .btn { padding: 8px 12px; border-radius: 6px; border: none; cursor: pointer; }
        .btn-primary { background: #4a90e2; color: #fff; }
        .btn-secondary { background: #f0f0f0; }
    </style>
</head>
<body>
    <div class="dev-container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-tools"></i>
                <h2>Herramientas de Desarrollo</h2>
            </div>
            <div class="card-body">
                <p>Controla el comportamiento del sistema de mensajería y del envío de SMS en creación de pedidos.</p>
                <p>Estado actual: SMS <span class="badge"><?php echo $sms_config['sms_enabled'] == '1' ? 'HABILITADO' : 'DESHABILITADO'; ?></span></p>

                <?php if ($notice): ?>
                    <div class="alert alert-<?php echo $notice_type === 'success' ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($notice); ?>
                    </div>
                <?php endif; ?>

                <form action="php/update_sms_config.php" method="POST">
                    <input type="hidden" name="onurix_client_id" value="<?php echo htmlspecialchars($sms_config['onurix_client_id']); ?>" />
                    <input type="hidden" name="onurix_api_key" value="<?php echo htmlspecialchars($sms_config['onurix_api_key']); ?>" />
                    <input type="hidden" name="sms_template" value="<?php echo htmlspecialchars($sms_config['sms_template']); ?>" />

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="sms_enabled" <?php echo $sms_config['sms_enabled'] == '1' ? 'checked' : ''; ?> />
                            Habilitar envío de SMS
                        </label>
                        <small>Si deshabilitas, no se utilizará la API de SMS al crear un pedido.</small>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="sms_finalize_enabled" <?php echo ($sms_config['sms_finalize_enabled'] ?? '1') == '1' ? 'checked' : ''; ?> />
                            Enviar SMS al finalizar pedido
                        </label>
                        <small>Controla la notificación al cliente al mover el pedido a Finalizados.</small>
                    </div>

                    <div class="actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                        <a href="recepcion.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver a Recepción</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>