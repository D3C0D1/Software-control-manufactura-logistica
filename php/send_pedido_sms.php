<?php
session_start();
// Forzar salida JSON si la conexión falla
if (!defined('RETURN_JSON')) {
    define('RETURN_JSON', true);
}
require_once 'db_connection.php';
require_once 'get_sms_config.php';

header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

// Verificar conexión a la base de datos
if (!isset($conn) || !is_object($conn)) {
    echo json_encode(['success' => false, 'message' => 'Conexión a base de datos no disponible']);
    exit;
}

// Obtener datos del POST en formato JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['pedido_id']) || !isset($input['numero_telefono'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $pedido_id = $input['pedido_id'];
    $numero_telefono = $input['numero_telefono'];
    $mensaje_input = isset($input['mensaje']) ? trim($input['mensaje']) : '';
    
    // Validar formato del número de teléfono (básico)
    if (!preg_match('/^[+]?[0-9]{10,15}$/', str_replace([' ', '-', '(', ')'], '', $numero_telefono))) {
        echo json_encode(['success' => false, 'message' => 'Formato de número de teléfono inválido']);
        exit;
    }
    
    // Verificar que el pedido existe
    $stmt = $conn->prepare("SELECT id, numero_guia, nombre_cliente FROM pedidos WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error preparando consulta de pedido']);
        exit;
    }
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();

    // Obtener resultados de forma compatible con entornos sin mysqlnd
    $pedido = null;
    if (method_exists($stmt, 'get_result')) {
        $result = $stmt->get_result();
        if ($result && ($result instanceof mysqli_result)) {
            if ($result->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
                exit;
            }
            $pedido = $result->fetch_assoc();
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al obtener el pedido']);
            exit;
        }
    } else {
        // Fallback: store_result + bind_result
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
            exit;
        }
        $id_out = null;
        $numero_guia_out = null;
        $nombre_cliente_out = null;
        $stmt->bind_result($id_out, $numero_guia_out, $nombre_cliente_out);
        $stmt->fetch();
        $pedido = [
            'id' => $id_out,
            'numero_guia' => $numero_guia_out,
            'nombre_cliente' => $nombre_cliente_out
        ];
    }
    $numero_guia = $pedido['numero_guia'];
    $nombre_cliente = $pedido['nombre_cliente'] ?? 'cliente';
    
    // Obtener configuración SMS desde la base de datos
    $sms_config = getSMSConfig();
    // Asegurar tabla de notificaciones SMS
    try {
        $pdo = getPDOConnection();
        ensureSMSNotificationsSchema($pdo);
    } catch (Exception $e) {
        error_log('No se pudo asegurar la tabla sms_notifications: ' . $e->getMessage());
    }
    
    // Verificar si el SMS está habilitado
    if ($sms_config['sms_enabled'] != '1' || ($sms_config['sms_finalize_enabled'] ?? '1') != '1') {
        error_log("SMS deshabilitado en configuración para pedido $numero_guia");
        echo json_encode(['success' => false, 'message' => 'Envío de SMS deshabilitado en configuración']);
        exit;
    }
    
    // Construir mensaje desde plantilla de finalización si no se proporcionó uno
    $mensaje_final = $mensaje_input;
    $finalize_template = isset($sms_config['sms_finalize_template']) ? (string)$sms_config['sms_finalize_template'] : '';
    if (trim($finalize_template) !== '') {
        $mensaje_final = str_replace(
            ['{numero_guia}', '{nombre_cliente}'],
            [$numero_guia, $nombre_cliente],
            $finalize_template
        );
    }
    // Considerar vacío solo cadena vacía, no valores como "0"
    if ($mensaje_final === '') {
        $mensaje_final = "¡Hola $nombre_cliente! Tu pedido #$numero_guia ya está listo para recoger.";
    }

    // Verificar credenciales según modo (Proxy vs directo)
    $useProxy = (($sms_config['sms_proxy_enabled'] ?? '0') === '1') && !empty($sms_config['sms_proxy_url']);
    $requireCreds = !$useProxy || (($sms_config['sms_proxy_send_credentials'] ?? '0') === '1');
    if ($requireCreds && (empty($sms_config['onurix_client_id']) || empty($sms_config['onurix_api_key']))) {
        error_log("Credenciales SMS no configuradas para pedido $numero_guia");
        echo json_encode(['success' => false, 'message' => 'Credenciales de Onurix no configuradas']);
        exit;
    }
    
    // Insertar SMS en la base de datos
    $sql = "INSERT INTO sms_notifications (pedido_id, numero_telefono, mensaje, estado, fecha_creacion) 
            VALUES (?, ?, ?, 'pendiente', NOW())";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error preparando inserción de SMS']);
        exit;
    }
    $stmt->bind_param("iss", $pedido_id, $numero_telefono, $mensaje_final);
    
    if ($stmt->execute()) {
        $sms_id = $conn->insert_id;
        
        // Enviar SMS usando la API de Onurix
        require_once '../vendor/autoload.php';
        
        $client_id = $sms_config['onurix_client_id'];
        $api_key = $sms_config['onurix_api_key'];
        
        // Inicializar cliente Guzzle y enviar según modo
        try {
            $client = new \GuzzleHttp\Client();

            if ($useProxy) {
                // Envío vía Proxy/Webhook (Hosting B)
                $proxyPayload = [
                    'phone' => $numero_telefono,
                    'sms' => $mensaje_final,
                    'pedido_id' => $pedido_id,
                    'numero_guia' => $numero_guia,
                    'context' => 'finalize'
                ];
                if (($sms_config['sms_proxy_send_credentials'] ?? '0') === '1') {
                    $proxyPayload['client'] = $client_id;
                    $proxyPayload['key'] = $api_key;
                }

                $headers = [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ];
                if (!empty($sms_config['sms_proxy_token'])) {
                    $headers['Authorization'] = 'Bearer ' . $sms_config['sms_proxy_token'];
                }

                error_log("Enviando SMS vía Proxy a {$sms_config['sms_proxy_url']} para guía $numero_guia");
                $response = $client->post($sms_config['sms_proxy_url'], [
                    'headers' => $headers,
                    'json' => $proxyPayload,
                    'timeout' => 30
                ]);

                $statusCode = $response->getStatusCode();
                $rawBody = $response->getBody()->getContents();
                $responseBody = json_decode($rawBody, true);
                error_log("Respuesta Proxy (HTTP $statusCode): " . substr($rawBody, 0, 300));

                $success = is_array($responseBody) ? ($responseBody['success'] ?? false) : false;
                if ($statusCode === 200 && $success) {
                    $update_sql = "UPDATE sms_notifications SET estado = 'enviado', fecha_envio = NOW() WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    if ($update_stmt) { $update_stmt->bind_param("i", $sms_id); $update_stmt->execute(); }
                    echo json_encode([
                        'success' => true,
                        'message' => $responseBody['message'] ?? 'SMS enviado exitosamente',
                        'sms_id' => $sms_id,
                        'proxy' => true
                    ]);
                } else {
                    $error_msg = is_array($responseBody) ? ($responseBody['message'] ?? 'Error desconocido (proxy)') : ('HTTP ' . $statusCode);
                    $update_sql = "UPDATE sms_notifications SET estado = 'fallido', error = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    if ($update_stmt) { $update_stmt->bind_param("si", $error_msg, $sms_id); $update_stmt->execute(); }
                    echo json_encode(['success' => false, 'message' => 'Error al enviar SMS vía proxy: ' . $error_msg]);
                }
            } else {
                // Envío directo a Onurix
                $request_body = [
                    'client' => $client_id,
                    'key' => $api_key,
                    'phone' => $numero_telefono,
                    'sms' => $mensaje_final
                ];
                error_log("Intentando enviar SMS directo a Onurix para guía $numero_guia");
                $response = $client->post('https://www.onurix.com/api/v1/sms/send', [
                    'form_params' => $request_body,
                    'timeout' => 30
                ]);
                $statusCode = $response->getStatusCode();
                $rawBody = $response->getBody()->getContents();
                $responseBody = json_decode($rawBody, true);
                error_log("Respuesta de Onurix para pedido $numero_guia: Status $statusCode, Body: " . json_encode($responseBody));

                if ($statusCode === 200 && is_array($responseBody) && isset($responseBody['data']['id'])) {
                    $update_sql = "UPDATE sms_notifications SET estado = 'enviado', fecha_envio = NOW() WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    if ($update_stmt) { $update_stmt->bind_param("i", $sms_id); $update_stmt->execute(); }
                    echo json_encode(['success' => true, 'message' => 'SMS enviado exitosamente', 'sms_id' => $sms_id]);
                } else {
                    $error_msg = (is_array($responseBody) && isset($responseBody['msg'])) ? $responseBody['msg'] : 'Error desconocido';
                    $update_sql = "UPDATE sms_notifications SET estado = 'fallido', error = ? WHERE id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    if ($update_stmt) { $update_stmt->bind_param("si", $error_msg, $sms_id); $update_stmt->execute(); }
                    echo json_encode(['success' => false, 'message' => 'Error al enviar SMS: ' . $error_msg]);
                }
            }
            
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            error_log("Error de conexión al enviar SMS para pedido $numero_guia: " . $e->getMessage());
            
            // Actualizar estado a fallido
            $update_sql = "UPDATE sms_notifications SET estado = 'fallido', error = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $error_msg = "Error de conexión: " . $e->getMessage();
            if ($update_stmt) {
                $update_stmt->bind_param("si", $error_msg, $sms_id);
                $update_stmt->execute();
            } else {
                error_log("No se pudo preparar actualización de estado 'fallido' para SMS $sms_id: $error_msg");
            }
            
            echo json_encode([
                'success' => false, 
                'message' => $error_msg
            ]);
        } catch (Exception $e) {
            error_log("Error general al enviar SMS para pedido $numero_guia: " . $e->getMessage());
            
            // Actualizar estado a fallido
            $update_sql = "UPDATE sms_notifications SET estado = 'fallido', error = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $error_msg = "Error general: " . $e->getMessage();
            if ($update_stmt) {
                $update_stmt->bind_param("si", $error_msg, $sms_id);
                $update_stmt->execute();
            } else {
                error_log("No se pudo preparar actualización de estado 'fallido' para SMS $sms_id: $error_msg");
            }
            
            echo json_encode([
                'success' => false, 
                'message' => $error_msg
            ]);
        }
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al guardar SMS en la base de datos'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>