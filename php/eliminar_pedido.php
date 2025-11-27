<?php
session_start();
require_once 'db_connection.php';

// Verificar autenticación
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Verificar que solo Admin (1) y Operador (2) puedan eliminar
if (!in_array($_SESSION['rol_id'], [1, 2])) {
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar pedidos']);
    exit();
}

header('Content-Type: application/json');

/** @var mysqli $conn */
if (!isset($conn) || !($conn instanceof mysqli)) {
    echo json_encode(['success' => false, 'message' => 'Conexión a base de datos no disponible']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $pedido_id = isset($input['pedido_id']) ? intval($input['pedido_id']) : 0;
    
    if ($pedido_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de pedido inválido']);
        exit();
    }
    
    try {
        $conn->begin_transaction();
        
        // Verificar que el pedido existe
        $stmt_check = $conn->prepare("SELECT id, numero_guia FROM pedidos WHERE id = ?");
        $stmt_check->bind_param('i', $pedido_id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Pedido no encontrado');
        }
        
        $pedido = $result->fetch_assoc();
        
        // Marcar el pedido como eliminado (estado_id = 3) y mover a área 20 (Recepción)
        // Nota: usamos estado_id=3 para que las estadísticas y reportes identifiquen correctamente los eliminados.
        $area_eliminado = 20;
        $usuario_id = $_SESSION['user_id'];
        
        // Actualizar el pedido: establecer estado eliminado y actualizar área (para historial/coherencia)
        $stmt_update = $conn->prepare("UPDATE pedidos SET estado_id = 3, area_id = ?, fecha_actualizacion = NOW() WHERE id = ?");
        $stmt_update->bind_param('ii', $area_eliminado, $pedido_id);
        
        if (!$stmt_update->execute()) {
            throw new Exception('Error al actualizar el pedido');
        }
        
        // Cerrar historial actual si existe
        $stmt_close_historial = $conn->prepare("UPDATE historial_pedidos SET fecha_salida = NOW(), usuario_salida_id = ? WHERE pedido_id = ? AND fecha_salida IS NULL");
        $stmt_close_historial->bind_param('ii', $usuario_id, $pedido_id);
        $stmt_close_historial->execute();
        
        // Insertar nuevo registro en historial
        $stmt_historial = $conn->prepare("INSERT INTO historial_pedidos (pedido_id, area_id, usuario_entrada_id, fecha_entrada) VALUES (?, ?, ?, NOW())");
        $stmt_historial->bind_param('iii', $pedido_id, $area_eliminado, $usuario_id);
        
        if (!$stmt_historial->execute()) {
            throw new Exception('Error al registrar en historial');
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Pedido eliminado correctamente',
            'numero_guia' => $pedido['numero_guia']
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>