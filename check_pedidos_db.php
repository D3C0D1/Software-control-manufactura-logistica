<?php
header('Content-Type: text/html');

require_once 'php/db_connection.php';

try {
    // Verificar si existen pedidos
    $result = $conn->query("SELECT COUNT(*) as total FROM pedidos");
    $row = $result->fetch_assoc();
    echo "<h3>Total pedidos en la base de datos: {$row['total']}</h3>";
    
    // Verificar pedidos específicos
    $result = $conn->query("SELECT id, numero_guia, area_id, estado_id FROM pedidos WHERE id IN (368, 369, 370)");
    echo "<h3>Pedidos 368, 369, 370:</h3>";
    while ($row = $result->fetch_assoc()) {
        echo "- Pedido {$row['id']}: Guía {$row['numero_guia']}, Area {$row['area_id']}, Estado {$row['estado_id']}<br>";
    }
    
    // Verificar pedidos en areas de Control de Calidad
    $result = $conn->query("SELECT id, numero_guia, area_id, estado_id FROM pedidos WHERE area_id IN (17, 18, 19)");
    echo "<h3>Pedidos en Control de Calidad (areas 17, 18, 19):</h3>";
    while ($row = $result->fetch_assoc()) {
        echo "- Pedido {$row['id']}: Guía {$row['numero_guia']}, Area {$row['area_id']}, Estado {$row['estado_id']}<br>";
    }
    
    // Verificar si existe la tabla messages_devoluciones
    $result = $conn->query("SHOW TABLES LIKE 'messages_devoluciones'");
    if ($result->num_rows > 0) {
        echo "<h3>Tabla messages_devoluciones existe</h3>";
        $result = $conn->query("SELECT COUNT(*) as total FROM messages_devoluciones WHERE tipo = 'devolucion'");
        $row = $result->fetch_assoc();
        echo "Total devoluciones: {$row['total']}<br>";
    } else {
        echo "<h3>⚠️ Tabla messages_devoluciones NO existe</h3>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>