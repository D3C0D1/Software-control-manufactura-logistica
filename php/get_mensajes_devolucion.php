<?php
header('Content-Type: application/json');
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $pedido_id = $_GET['pedido_id'] ?? null;
    
    if ($pedido_id) {
        // Obtener mensajes de un pedido específico
        $sql = "SELECT md.*, md.usuario as nombre_completo, 
                       ao.nombre_area as area_origen_nombre
                FROM mensajes_devolucion md
                LEFT JOIN areas ao ON md.area_origen = ao.id
                WHERE md.pedido_id = ?
                ORDER BY md.fecha_creacion DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $pedido_id);
    } else {
        // Obtener todos los mensajes recientes
        $sql = "SELECT md.*, md.usuario as nombre_completo, p.numero_guia,
                       ao.nombre_area as area_origen_nombre
                FROM mensajes_devolucion md
                LEFT JOIN pedidos p ON md.pedido_id = p.id
                LEFT JOIN areas ao ON md.area_origen = ao.id
                ORDER BY md.fecha_creacion DESC
                LIMIT 50";
        $stmt = $conn->prepare($sql);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $mensajes = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'mensajes' => $mensajes]);
    
} catch (Exception $e) {
    error_log("Error en get_mensajes_devolucion.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

if (isset($stmt)) $stmt->close();
$conn->close();
?>