<?php
session_start();
// Forzar salida JSON si la conexión falla
if (!defined('RETURN_JSON')) {
    define('RETURN_JSON', true);
}
require 'db_connection.php';
require_once 'get_sms_config.php';
require_once 'get_sms_config.php';

header('Content-Type: application/json');

// Comprobación defensiva de la conexión
if (!isset($conn) || !is_object($conn)) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['pedido_id']) || !isset($input['numero_telefono']) || !isset($input['mensaje'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $pedido_id = $input['pedido_id'];
    $numero_telefono = $input['numero_telefono'];
    $mensaje = trim($input['mensaje']);
    
    if (empty($mensaje)) {
        echo json_encode(['success' => false, 'message' => 'El mensaje no puede estar vacío']);
        exit;
    }
    
    // Validar formato del número de teléfono (básico)
    if (!preg_match('/^[+]?[0-9]{10,15}$/', str_replace([' ', '-', '(', ')'], '', $numero_telefono))) {
        echo json_encode(['success' => false, 'message' => 'Formato de número de teléfono inválido']);
        exit;
    }
    
    // Verificar que el pedido existe
    $stmt = $conn->prepare("SELECT id FROM pedidos WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error preparando consulta de pedido']);
        exit;
    }
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();

    $pedidoExiste = false;
    if (method_exists($stmt, 'get_result')) {
        $result = $stmt->get_result();
        if ($result && ($result instanceof mysqli_result)) {
            $pedidoExiste = ($result->num_rows > 0);
        }
    } else {
        // Fallback sin mysqlnd
        $stmt->store_result();
        $pedidoExiste = ($stmt->num_rows > 0);
    }

    if (!$pedidoExiste) {
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
        exit;
    }
    
    // Asegurar tabla de notificaciones SMS
    try {
        $pdo = getPDOConnection();
        ensureSMSNotificationsSchema($pdo);
    } catch (Exception $e) {
        error_log('No se pudo asegurar la tabla sms_notifications: ' . $e->getMessage());
    }

    // Asegurar tabla de notificaciones SMS
    try {
        $pdo = getPDOConnection();
        ensureSMSNotificationsSchema($pdo);
    } catch (Exception $e) {
        error_log('No se pudo asegurar la tabla sms_notifications: ' . $e->getMessage());
    }

    // Insertar SMS en la base de datos
    $sql = "INSERT INTO sms_notifications (pedido_id, numero_telefono, mensaje, estado, fecha_creacion) 
            VALUES (?, ?, ?, 'pendiente', NOW())";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error preparando inserción de SMS']);
        exit;
    }
    $stmt->bind_param("iss", $pedido_id, $numero_telefono, $mensaje);
    
    if ($stmt->execute()) {
        $sms_id = $conn->insert_id;
        
        // Aquí se integraría con un servicio de SMS real como Twilio, Nexmo, etc.
        // Por ahora, simularemos el envío
        $sms_enviado = enviarSMS($numero_telefono, $mensaje);
        
        if ($sms_enviado) {
            // Actualizar estado a enviado
            $update_sql = "UPDATE sms_notifications SET estado = 'enviado', fecha_envio = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            if ($update_stmt) {
                $update_stmt->bind_param("i", $sms_id);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                error_log("No se pudo preparar actualización de estado 'enviado' para SMS $sms_id");
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'SMS enviado exitosamente',
                'sms_id' => $sms_id
            ]);
        } else {
            // Actualizar estado a fallido
            $update_sql = "UPDATE sms_notifications SET estado = 'fallido' WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            if ($update_stmt) {
                $update_stmt->bind_param("i", $sms_id);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                error_log("No se pudo preparar actualización de estado 'fallido' para SMS $sms_id");
            }
            
            echo json_encode([
                'success' => false, 
                'message' => 'Error al enviar SMS'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al guardar SMS en la base de datos'
        ]);
    }
    
    if (isset($stmt) && ($stmt instanceof mysqli_stmt)) {
        $stmt->close();
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

if (isset($conn) && ($conn instanceof mysqli)) {
    $conn->close();
}

// Función para enviar SMS (integración con servicio externo)
function enviarSMS($numero, $mensaje) {
    // IMPORTANTE: Aquí debes integrar con un servicio real de SMS
    // Ejemplos: Twilio, Nexmo, AWS SNS, etc.
    
    // Por ahora, simulamos el envío (siempre exitoso para pruebas)
    // En producción, reemplaza esto con la API real
    
    /*
    // Ejemplo con Twilio:
    require_once 'vendor/autoload.php';
    use Twilio\Rest\Client;
    
    $sid = 'tu_account_sid';
    $token = 'tu_auth_token';
    $twilio_number = 'tu_numero_twilio';
    
    $twilio = new Client($sid, $token);
    
    try {
        $message = $twilio->messages->create(
            $numero,
            [
                'from' => $twilio_number,
                'body' => $mensaje
            ]
        );
        return true;
    } catch (Exception $e) {
        error_log('Error enviando SMS: ' . $e->getMessage());
        return false;
    }
    */
    
    // Simulación para pruebas
    sleep(1); // Simular tiempo de envío
    return true; // Cambiar a false para simular fallo
}
?>