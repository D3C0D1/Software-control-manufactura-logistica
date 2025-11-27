<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$rol_id = $_SESSION['rol_id'];
$area_id = $_SESSION['area_id'] ?? 0;

$messages = [];

// Verificar si se solicita historial de devoluciones
if (isset($_GET['tipo']) && $_GET['tipo'] === 'devolucion' && isset($_GET['pedido_id'])) {
    $pedido_id = intval($_GET['pedido_id']);
    
    // Consulta con JOIN para obtener el nombre del área
    $sql = "SELECT md.id, md.pedido_id, md.area_origen, a.nombre_area, md.usuario, md.mensaje, md.fecha_creacion 
            FROM mensajes_devolucion md
            LEFT JOIN areas a ON md.area_origen = a.id
            WHERE md.pedido_id = ? 
            ORDER BY md.fecha_creacion DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error preparando consulta: ' . $conn->error]);
        $conn->close();
        exit;
    }
    
    $stmt->bind_param("i", $pedido_id);
    if (!$stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error ejecutando consulta: ' . $stmt->error]);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    $result = $stmt->get_result();
    $messages = [];
    
    while ($row = $result->fetch_assoc()) {
        // Mapear los campos para mantener compatibilidad con el frontend
        $messages[] = [
            'id' => $row['id'],
            'pedido_id' => $row['pedido_id'],
            'area_origen' => $row['area_origen'],
            'area_nombre' => $row['nombre_area'] ?: 'Área no especificada',
            'usuario' => $row['usuario'],
            'mensaje' => $row['mensaje'],
            'fecha_creacion' => $row['fecha_creacion']
        ];
    }
    
    $stmt->close();
    
    header('Content-Type: application/json');
    if (count($messages) > 0) {
        echo json_encode(['success' => true, 'messages' => $messages]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron mensajes de devolución para este pedido']);
    }
    
    $conn->close();
    exit;
}

if ($rol_id == 1) { // Admin
    if (isset($_GET['area_id']) && !empty($_GET['area_id'])) {
        $selected_area_id = intval($_GET['area_id']);
        
        // Obtener el user_id del empleado de esa área
        $stmt_user = $conn->prepare("SELECT id FROM usuarios WHERE area_id = ?");
        $stmt_user->bind_param("i", $selected_area_id);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        $employee = $result_user->fetch_assoc();

        if ($employee) {
            $employee_id = $employee['id'];
            $sql = "SELECT m.*, u.usuario AS sender_name FROM chat_messages m JOIN usuarios u ON m.sender_id = u.id WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?) ORDER BY m.timestamp ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiii", $user_id, $employee_id, $employee_id, $user_id);
        } else {
            // No hay empleado en el área, no devolver mensajes
            $stmt = null;
        }
    } else {
        $stmt = null; // No se ha seleccionado canal
    }
} else { // Empleado
    $admin_id = 1; // Asumimos que el ID del admin es 1
    $sql = "SELECT m.*, u.usuario AS sender_name FROM chat_messages m JOIN usuarios u ON m.sender_id = u.id WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?) ORDER BY m.timestamp ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $user_id, $admin_id, $admin_id, $user_id);
}

if (isset($stmt) && $stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($messages);

$conn->close();
?>