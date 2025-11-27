<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>🔍 Verificación de datos reales en mensajes_devolucion</h2>";

require_once 'php/db_connection.php';

try {
    // 1. Verificar si la tabla existe
    $sql_check = "SHOW TABLES LIKE 'mensajes_devolucion'";
    $result = $conn->query($sql_check);
    
    if ($result->num_rows == 0) {
        echo "<p style='color: red;'>❌ La tabla mensajes_devolucion NO existe</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Tabla mensajes_devolucion existe</p>";
    
    // 2. Mostrar estructura actual
    echo "<h3>📋 Estructura de la tabla:</h3>";
    $sql_desc = "DESCRIBE mensajes_devolucion";
    $result_desc = $conn->query($sql_desc);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result_desc->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td><td>{$row['Default']}</td></tr>";
    }
    echo "</table>";
    
    // 3. Contar todos los registros
    $sql_count = "SELECT COUNT(*) as total FROM mensajes_devolucion";
    $result_count = $conn->query($sql_count);
    $total = $result_count->fetch_assoc()['total'];
    echo "<h3>📊 Total de registros: {$total}</h3>";
    
    if ($total > 0) {
        // 4. Mostrar todos los registros
        echo "<h3>📄 Todos los registros:</h3>";
        $sql_all = "SELECT * FROM mensajes_devolucion ORDER BY fecha_creacion DESC";
        $result_all = $conn->query($sql_all);
        
        echo "<table border='1' style='border-collapse: collapse; font-size: 12px;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Pedido ID</th><th>Usuario ID</th><th>Área Origen</th><th>Área Destino</th><th>Usuario</th><th>Mensaje</th><th>Fecha</th></tr>";
        
        while ($row = $result_all->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td><strong>{$row['pedido_id']}</strong></td>";
            echo "<td>" . ($row['usuario_id'] ?? 'NULL') . "</td>";
            echo "<td>" . ($row['area_origen'] ?? 'NULL') . "</td>";
            echo "<td>" . ($row['area_destino'] ?? 'NULL') . "</td>";
            echo "<td>" . ($row['usuario'] ?? 'NULL') . "</td>";
            echo "<td>" . substr($row['mensaje'], 0, 100) . "...</td>";
            echo "<td>{$row['fecha_creacion']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // 5. Mostrar pedidos únicos
        echo "<h3>🎯 Pedidos únicos con devoluciones:</h3>";
        $sql_pedidos = "SELECT DISTINCT pedido_id, COUNT(*) as cantidad FROM mensajes_devolucion GROUP BY pedido_id ORDER BY pedido_id";
        $result_pedidos = $conn->query($sql_pedidos);
        
        echo "<ul>";
        while ($row = $result_pedidos->fetch_assoc()) {
            echo "<li><strong>Pedido {$row['pedido_id']}</strong> - {$row['cantidad']} mensaje(s)</li>";
        }
        echo "</ul>";
        
    } else {
        echo "<p style='color: orange;'>⚠️ No hay registros en la tabla mensajes_devolucion</p>";
        
        // Verificar si hay pedidos en el sistema
        echo "<h3>🔍 Verificando pedidos en el sistema:</h3>";
        $sql_pedidos_check = "SHOW TABLES LIKE 'pedidos'";
        $result_pedidos_check = $conn->query($sql_pedidos_check);
        
        if ($result_pedidos_check->num_rows > 0) {
            $sql_count_pedidos = "SELECT COUNT(*) as total FROM pedidos";
            $result_count_pedidos = $conn->query($sql_count_pedidos);
            $total_pedidos = $result_count_pedidos->fetch_assoc()['total'];
            echo "<p>📦 Total de pedidos en el sistema: {$total_pedidos}</p>";
            
            if ($total_pedidos > 0) {
                echo "<p>📋 Algunos pedidos del sistema:</p>";
                $sql_sample_pedidos = "SELECT id, numero_pedido, estado_actual FROM pedidos ORDER BY id DESC LIMIT 10";
                $result_sample = $conn->query($sql_sample_pedidos);
                
                echo "<ul>";
                while ($row = $result_sample->fetch_assoc()) {
                    echo "<li>Pedido ID: {$row['id']} - Número: {$row['numero_pedido']} - Estado: {$row['estado_actual']}</li>";
                }
                echo "</ul>";
            }
        } else {
            echo "<p style='color: red;'>❌ No existe la tabla pedidos</p>";
        }
    }
    
    // 6. Probar la consulta exacta de get_messages.php con un pedido real
    if ($total > 0) {
        echo "<h3>🧪 Probando consulta de get_messages.php:</h3>";
        
        // Obtener un pedido_id que tenga devoluciones
        $sql_test_pedido = "SELECT pedido_id FROM mensajes_devolucion LIMIT 1";
        $result_test = $conn->query($sql_test_pedido);
        
        if ($result_test->num_rows > 0) {
            $test_pedido_id = $result_test->fetch_assoc()['pedido_id'];
            echo "<p>🎯 Probando con pedido ID: {$test_pedido_id}</p>";
            
            // Ejecutar la misma consulta que usa get_messages.php
            $sql_test = "SELECT md.id, md.pedido_id, md.area_origen, 
                               COALESCE(a.nombre_area, 'Área no especificada') as nombre_area, 
                               COALESCE(md.usuario, 'Usuario no especificado') as usuario, 
                               md.mensaje, md.fecha_creacion 
                        FROM mensajes_devolucion md
                        LEFT JOIN areas a ON md.area_origen = a.id
                        WHERE md.pedido_id = ? 
                        ORDER BY md.fecha_creacion DESC";
            
            $stmt = $conn->prepare($sql_test);
            if ($stmt) {
                $stmt->bind_param("i", $test_pedido_id);
                $stmt->execute();
                $result_test_query = $stmt->get_result();
                
                if ($result_test_query->num_rows > 0) {
                    echo "<p style='color: green;'>✅ La consulta devuelve {$result_test_query->num_rows} resultado(s)</p>";
                    
                    echo "<table border='1' style='border-collapse: collapse;'>";
                    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Pedido</th><th>Área</th><th>Usuario</th><th>Mensaje</th><th>Fecha</th></tr>";
                    
                    while ($row = $result_test_query->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$row['id']}</td>";
                        echo "<td>{$row['pedido_id']}</td>";
                        echo "<td>{$row['nombre_area']}</td>";
                        echo "<td>{$row['usuario']}</td>";
                        echo "<td>" . substr($row['mensaje'], 0, 50) . "...</td>";
                        echo "<td>{$row['fecha_creacion']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    
                    echo "<p><a href='php/get_messages.php?tipo=devolucion&pedido_id={$test_pedido_id}' target='_blank'>🔗 Probar get_messages.php con este pedido</a></p>";
                    
                } else {
                    echo "<p style='color: red;'>❌ La consulta no devuelve resultados</p>";
                }
                
                $stmt->close();
            } else {
                echo "<p style='color: red;'>❌ Error preparando la consulta: " . $conn->error . "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

$conn->close();
echo "<hr><p><strong>Verificación completada</strong></p>";
?>