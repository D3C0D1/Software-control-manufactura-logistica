<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
ini_set("display_errors", 0);
error_reporting(0);

try {
    include "db_connection.php";
    
    if (!isset($_SESSION["user_id"])) {
        echo json_encode(["error" => "No autenticado"]);
        exit;
    }
    
    $user_id = $_SESSION["user_id"];
    $rol_id = $_SESSION["rol_id"];
    $area_id = $_SESSION["area_id"] ?? 0;
    
    $messages = [];
    
    // Verificar si se solicita historial de devoluciones
    if (isset($_GET["tipo"]) && $_GET["tipo"] === "devolucion" && isset($_GET["pedido_id"])) {
        $pedido_id = intval($_GET["pedido_id"]);
        
        if ($pedido_id <= 0) {
            echo json_encode(["success" => false, "message" => "ID de pedido inválido"]);
            exit;
        }
        
        // Verificar si las tablas existen y crearlas si es necesario
        $tables_check = $conn->query("SHOW TABLES LIKE \"mensajes_devolucion\"");
        if ($tables_check->num_rows == 0) {
            // Crear tabla automáticamente
            $create_table = "
            CREATE TABLE mensajes_devolucion (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pedido_id INT NOT NULL,
                area_origen INT,
                usuario VARCHAR(255),
                mensaje TEXT,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_pedido_id (pedido_id)
            )
            ";
            
            if (!$conn->query($create_table)) {
                echo json_encode(["success" => false, "message" => "Error creando tabla de devoluciones"]);
                exit;
            }
        }
        
        // Verificar que el pedido existe
        $pedido_check = $conn->prepare("SELECT id FROM pedidos WHERE id = ?");
        if (!$pedido_check) {
            echo json_encode(["success" => false, "message" => "Error preparando verificación de pedido"]);
            exit;
        }
        
        $pedido_check->bind_param("i", $pedido_id);
        if (!$pedido_check->execute()) {
            echo json_encode(["success" => false, "message" => "Error verificando pedido"]);
            $pedido_check->close();
            exit;
        }
        
        $pedido_result = $pedido_check->get_result();
        if ($pedido_result->num_rows == 0) {
            echo json_encode(["success" => false, "message" => "Pedido no encontrado"]);
            $pedido_check->close();
            exit;
        }
        $pedido_check->close();
        
        // Consulta con manejo de errores mejorado
        $sql = "SELECT md.id, md.pedido_id, md.area_origen, 
                       COALESCE(a.nombre_area, \"Área no especificada\") as nombre_area, 
                       COALESCE(md.usuario, \"Usuario no especificado\") as usuario, 
                       md.mensaje, md.fecha_creacion 
                FROM mensajes_devolucion md
                LEFT JOIN areas a ON md.area_origen = a.id
                WHERE md.pedido_id = ? 
                ORDER BY md.fecha_creacion DESC";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Error preparando consulta de mensajes"]);
            exit;
        }
        
        $stmt->bind_param("i", $pedido_id);
        if (!$stmt->execute()) {
            echo json_encode(["success" => false, "message" => "Error ejecutando consulta de mensajes"]);
            $stmt->close();
            exit;
        }
        
        $result = $stmt->get_result();
        $messages = [];
        
        while ($row = $result->fetch_assoc()) {
            $messages[] = [
                "id" => $row["id"],
                "pedido_id" => $row["pedido_id"],
                "area_origen" => $row["area_origen"],
                "area_nombre" => $row["nombre_area"],
                "usuario" => $row["usuario"],
                "mensaje" => $row["mensaje"],
                "fecha_creacion" => $row["fecha_creacion"]
            ];
        }
        
        $stmt->close();
        
        if (count($messages) > 0) {
            echo json_encode(["success" => true, "messages" => $messages]);
        } else {
            echo json_encode(["success" => false, "message" => "No se encontraron mensajes de devolución para este pedido"]);
        }
        
        $conn->close();
        exit;
    }

    // Resto del código original para chat normal
    if ($rol_id == 1) { // Admin
        if (isset($_GET["area_id"]) && !empty($_GET["area_id"])) {
            $selected_area_id = intval($_GET["area_id"]);
            
            $stmt_user = $conn->prepare("SELECT id FROM usuarios WHERE area_id = ?");
            if ($stmt_user) {
                $stmt_user->bind_param("i", $selected_area_id);
                $stmt_user->execute();
                $result_user = $stmt_user->get_result();
                $employee = $result_user->fetch_assoc();

                if ($employee) {
                    $employee_id = $employee["id"];
                    $sql = "SELECT m.*, u.usuario AS sender_name FROM chat_messages m JOIN usuarios u ON m.sender_id = u.id WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?) ORDER BY m.timestamp ASC";
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("iiii", $user_id, $employee_id, $employee_id, $user_id);
                    }
                } else {
                    $stmt = null;
                }
                $stmt_user->close();
            } else {
                $stmt = null;
            }
        } else {
            $stmt = null;
        }
    } else { // Empleado
        $admin_id = 1;
        $sql = "SELECT m.*, u.usuario AS sender_name FROM chat_messages m JOIN usuarios u ON m.sender_id = u.id WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?) ORDER BY m.timestamp ASC";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iiii", $user_id, $admin_id, $admin_id, $user_id);
        }
    }

    if (isset($stmt) && $stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        $stmt->close();
    }

    echo json_encode($messages);
    $conn->close();
    
} catch (Exception $e) {
    // Log del error para debugging
    error_log("Error en get_messages.php: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Error interno del servidor"]);
}
?>