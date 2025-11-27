<?php
require_once 'php/db_connection.php';

echo "<h2>Creando pedido de prueba con devolución para Diseño</h2>";

// Crear un pedido de prueba en el área de Diseño (área_id = 2)
$sql_pedido = "INSERT INTO pedidos (numero_guia, nombre_cliente, correo_cliente, numero_cliente, area_id, estado_id, fecha_creacion, prioridad, notas) 
               VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)";

$numero_guia = 'TEST-DISENO-' . time();
$nombre_cliente = 'Cliente Prueba Devolución';
$correo_cliente = 'prueba@devolucion.com';
$numero_cliente = '1234567890';
$area_id = 2; // Diseño - En Proceso
$estado_id = 2; // En proceso
$prioridad = 'alta';
$notas = 'Pedido de prueba para verificar indicador de devolución';

$stmt = $conn->prepare($sql_pedido);
$stmt->bind_param('ssssiiis', $numero_guia, $nombre_cliente, $correo_cliente, $numero_cliente, $area_id, $estado_id, $prioridad, $notas);

if ($stmt->execute()) {
    $pedido_id = $conn->insert_id;
    echo "<p style='color: green;'>✓ Pedido creado con ID: $pedido_id</p>";
    
    // Crear un mensaje de devolución para este pedido
    $sql_devolucion = "INSERT INTO mensajes_devolucion (pedido_id, area_origen_id, area_destino_id, mensaje, fecha_creacion, usuario_id) 
                       VALUES (?, ?, ?, ?, NOW(), ?)";
    
    $area_origen_id = 5; // Confección
    $area_destino_id = 2; // Diseño
    $mensaje_devolucion = 'El diseño necesita correcciones en los colores y tipografía';
    $usuario_id = 1; // Usuario de prueba
    
    $stmt_dev = $conn->prepare($sql_devolucion);
    $stmt_dev->bind_param('iiisi', $pedido_id, $area_origen_id, $area_destino_id, $mensaje_devolucion, $usuario_id);
    
    if ($stmt_dev->execute()) {
        echo "<p style='color: green;'>✓ Mensaje de devolución creado</p>";
        echo "<p><strong>Detalles del pedido:</strong></p>";
        echo "<ul>";
        echo "<li>ID: $pedido_id</li>";
        echo "<li>Guía: $numero_guia</li>";
        echo "<li>Cliente: $nombre_cliente</li>";
        echo "<li>Área: Diseño (ID: $area_id)</li>";
        echo "<li>Estado: En Proceso (ID: $estado_id)</li>";
        echo "<li>Prioridad: $prioridad</li>";
        echo "</ul>";
        
        echo "<p><strong>Mensaje de devolución:</strong> $mensaje_devolucion</p>";
        
        // Verificar que el pedido tiene devoluciones
        $sql_check = "SELECT p.id, p.numero_guia, p.nombre_cliente, 
                             CASE WHEN md.pedido_id IS NOT NULL THEN 1 ELSE 0 END as tiene_devoluciones
                      FROM pedidos p 
                      LEFT JOIN mensajes_devolucion md ON p.id = md.pedido_id 
                      WHERE p.id = ?";
        
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param('i', $pedido_id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        
        if ($row = $result->fetch_assoc()) {
            echo "<p><strong>Verificación:</strong></p>";
            echo "<ul>";
            echo "<li>Pedido ID: " . $row['id'] . "</li>";
            echo "<li>Tiene devoluciones: " . ($row['tiene_devoluciones'] ? 'SÍ' : 'NO') . "</li>";
            echo "</ul>";
            
            if ($row['tiene_devoluciones']) {
                echo "<p style='color: green; font-weight: bold;'>✓ El pedido ahora debería mostrar el indicador de devolución en diseno.php</p>";
            } else {
                echo "<p style='color: red; font-weight: bold;'>✗ Error: El pedido no muestra que tiene devoluciones</p>";
            }
        }
        
        $stmt_check->close();
    } else {
        echo "<p style='color: red;'>✗ Error creando mensaje de devolución: " . $conn->error . "</p>";
    }
    
    $stmt_dev->close();
} else {
    echo "<p style='color: red;'>✗ Error creando pedido: " . $conn->error . "</p>";
}

$stmt->close();
$conn->close();

echo "<p><a href='diseno.php' target='_blank'>→ Ir a Diseño para ver el pedido</a></p>";
echo "<p><a href='test_devolucion_diseno.php' target='_blank'>→ Probar endpoint get_pedidos.php</a></p>";
?>