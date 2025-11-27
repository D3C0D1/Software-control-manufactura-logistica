<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar que el usuario sea admin o operador (solo ellos pueden enviar SMS)
if ($_SESSION['rol_id'] != 1 && $_SESSION['rol_id'] != 2) {
    echo json_encode(['success' => false, 'message' => 'No tiene permisos para enviar SMS. Solo admin y operador pueden enviar.']);
    exit;
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Datos no válidos']);
    exit;
}

$destination_area_name = trim($input['area_name'] ?? '');
$message = trim($input['message']);
$sender_id = $_SESSION['user_id'];
$sender_area_id = $_SESSION['area_id'] ?? null;

// Validar datos
if (empty($destination_area_name) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Área destino y mensaje son requeridos']);
    exit;
}

if (strlen($message) > 500) {
    echo json_encode(['success' => false, 'message' => 'El mensaje no puede exceder 500 caracteres']);
    exit;
}

// Admin y operadores no tienen área asignada (pueden enviar a cualquier área)
// Solo empleados necesitan tener área asignada
if (!$sender_area_id && $_SESSION['rol_id'] == 3) {
    echo json_encode(['success' => false, 'message' => 'Usuario empleado debe tener área asignada']);
    exit;
}

try {
    // Buscar el ID del área destino por nombre
    $stmt = $pdo->prepare("SELECT id FROM area_empleado WHERE nombre = ?");
    $stmt->execute([$destination_area_name]);
    $destination_area = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$destination_area) {
        echo json_encode(['success' => false, 'message' => 'Área destino no encontrada']);
        exit;
    }
    
    $destination_area_id = $destination_area['id'];
    
    // Verificar que empleados no envíen a su propia área (admin/operadores pueden enviar a cualquier área)
    if ($sender_area_id && $sender_area_id == $destination_area_id) {
        echo json_encode(['success' => false, 'message' => 'No puede enviar SMS a su propia área']);
        exit;
    }
    
    // Insertar el SMS en la nueva tabla sms_areas
    $stmt = $pdo->prepare("
        INSERT INTO sms_areas (sender_id, sender_area_id, destination_area_id, message, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([$sender_id, $sender_area_id, $destination_area_id, $message]);
    
    if ($stmt->rowCount() > 0) {
        // Obtener información del área destino para la respuesta
        $stmt = $pdo->prepare("SELECT nombre FROM area_empleado WHERE id = ?");
        $stmt->execute([$destination_area_id]);
        $area_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'message' => 'SMS enviado correctamente a ' . $area_info['nombre']
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Error al enviar el SMS'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>