<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}

if (isset($_POST['id'])) {
    $pedido_id = $_POST['id'];
    $new_area_id = 11; // ID para 'Recepción de Mensajería'
    $usuario_id = $_SESSION['user_id'];

    $conn->begin_transaction();

    try {
        // 1. Finalizar el registro de historial actual
        $sql_update_historial = "UPDATE historial_pedidos SET fecha_salida = NOW(), usuario_salida_id = ? WHERE pedido_id = ? AND fecha_salida IS NULL";
        $stmt_update = $conn->prepare($sql_update_historial);
        if ($stmt_update === false) throw new Exception('Error al preparar la actualización del historial.');
        $stmt_update->bind_param('ii', $usuario_id, $pedido_id);
        $stmt_update->execute();
        $stmt_update->close();

        // 2. Actualizar el área del pedido
        $sql_update_pedido = "UPDATE pedidos SET area_id = ? WHERE id = ?";
        $stmt_pedido = $conn->prepare($sql_update_pedido);
        if ($stmt_pedido === false) throw new Exception('Error al preparar la actualización del pedido.');
        $stmt_pedido->bind_param('ii', $new_area_id, $pedido_id);
        $stmt_pedido->execute();
        $stmt_pedido->close();

        // 3. Insertar el nuevo registro de historial (con fallback si id no es AUTO_INCREMENT)
        $sql_insert_historial = "INSERT INTO historial_pedidos (pedido_id, area_id, usuario_entrada_id, fecha_entrada) VALUES (?, ?, ?, NOW())";
        $stmt_insert = $conn->prepare($sql_insert_historial);
        if ($stmt_insert === false) throw new Exception('Error al preparar la inserción del historial.');
        $stmt_insert->bind_param('iii', $pedido_id, $new_area_id, $usuario_id);
        try {
            $stmt_insert->execute();
            $stmt_insert->close();
        } catch (mysqli_sql_exception $e) {
            if (strpos($e->getMessage(), "Field 'id' doesn't have a default value") !== false) {
                // Fallback: insertar con id manual
                $next_id = 1;
                try {
                    $res_max = $conn->query("SELECT IFNULL(MAX(id), 0) + 1 AS next_id FROM historial_pedidos");
                    if ($res_max) {
                        $row = $res_max->fetch_assoc();
                        if ($row && isset($row['next_id'])) {
                            $next_id = (int)$row['next_id'];
                        }
                        $res_max->close();
                    }
                } catch (mysqli_sql_exception $e2) {
                    error_log('enviar_a_mensajeria.php - Error calculando next_id de historial_pedidos: ' . $e2->getMessage());
                }

                $sql_insert_historial2 = "INSERT INTO historial_pedidos (id, pedido_id, area_id, usuario_entrada_id, fecha_entrada) VALUES (?, ?, ?, ?, NOW())";
                $stmt_insert2 = $conn->prepare($sql_insert_historial2);
                if ($stmt_insert2) {
                    $stmt_insert2->bind_param('iiii', $next_id, $pedido_id, $new_area_id, $usuario_id);
                    $stmt_insert2->execute();
                    $stmt_insert2->close();
                } else {
                    throw new Exception('Error al preparar inserción de historial con ID: ' . $conn->error);
                }
            } else {
                throw $e;
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Pedido enviado a mensajería y registrado en el historial.']);

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error al enviar a mensajería: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error al enviar el pedido: ' . $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'No se proporcionó ID de pedido.']);
}
?>