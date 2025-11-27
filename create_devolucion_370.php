<?php
header('Content-Type: text/html');

require_once 'php/db_connection.php';

try {
    // Crear tabla si no existe
    $sql_create_table = "CREATE TABLE IF NOT EXISTS mensajes_devolucion (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pedido_id INT NOT NULL,
        usuario_id INT NOT NULL,
        area_origen INT NOT NULL,
        area_destino INT NOT NULL,
        usuario VARCHAR(255) NOT NULL,
        mensaje TEXT NOT NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
    )";
    $conn->query($sql_create_table);
    
    // Verificar si el pedido 370 ya tiene devoluciones
    $sql_check = "SELECT COUNT(*) as count FROM mensajes_devolucion WHERE pedido_id = 370";
    $result = $conn->query($sql_check);
    $count = $result->fetch_assoc()['count'];
    
    if ($count == 0) {
        // Insertar mensaje de devolución de prueba para el pedido 370
        $sql_insert = "INSERT INTO mensajes_devolucion (pedido_id, usuario_id, area_origen, area_destino, usuario, mensaje, fecha_creacion) 
                       VALUES (370, 1, 14, 18, 'Usuario de Prueba', 'Devolución de prueba: El producto no cumple con las especificaciones de calidad requeridas.', NOW())";
        
        if ($conn->query($sql_insert)) {
            echo "<h2>✅ Devolución de prueba creada exitosamente</h2>";
            echo "<p><strong>Pedido ID:</strong> 370</p>";
            echo "<p><strong>Mensaje:</strong> Devolución de prueba: El producto no cumple con las especificaciones de calidad requeridas.</p>";
            echo "<p>Ahora el pedido 370 debería mostrar el indicador de devolución en el modal.</p>";
            echo "<a href='control_calidad.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir a Control de Calidad</a>";
        } else {
            echo "<h2>❌ Error al crear la devolución</h2>";
            echo "<p>Error: " . $conn->error . "</p>";
        }
    } else {
        echo "<h2>ℹ️ El pedido 370 ya tiene devoluciones</h2>";
        echo "<p>Número de devoluciones existentes: $count</p>";
        echo "<a href='control_calidad.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir a Control de Calidad</a>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}

$conn->close();
?>