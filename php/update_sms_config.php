<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit;
}

require_once 'get_sms_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = true;
    $errors = [];
    
    // Validar y actualizar cada configuración
    $configs_to_update = [
        'onurix_client_id' => $_POST['onurix_client_id'] ?? '',
        'onurix_api_key' => $_POST['onurix_api_key'] ?? '',
        'sms_enabled' => isset($_POST['sms_enabled']) ? '1' : '0',
        'sms_template' => $_POST['sms_template'] ?? '',
        'sms_finalize_enabled' => isset($_POST['sms_finalize_enabled']) ? '1' : '0',
        'sms_finalize_template' => $_POST['sms_finalize_template'] ?? '',
        // Proxy/Webhook hacia Hosting B
        'sms_proxy_enabled' => isset($_POST['sms_proxy_enabled']) ? '1' : '0',
        'sms_proxy_url' => $_POST['sms_proxy_url'] ?? '',
        'sms_proxy_token' => $_POST['sms_proxy_token'] ?? '',
        'sms_proxy_send_credentials' => isset($_POST['sms_proxy_send_credentials']) ? '1' : '0'
    ];
    
    foreach ($configs_to_update as $config_name => $config_value) {
        if (!updateSMSConfig($config_name, $config_value)) {
            $success = false;
            $errors[] = "Error al actualizar $config_name";
        }
    }
    
    if ($success) {
        $_SESSION['config_message'] = 'Configuración SMS actualizada exitosamente';
        $_SESSION['config_type'] = 'success';
    } else {
        $_SESSION['config_message'] = 'Error al actualizar la configuración: ' . implode(', ', $errors);
        $_SESSION['config_type'] = 'error';
    }
}

header("Location: ../configuracion.php");
exit;
?>