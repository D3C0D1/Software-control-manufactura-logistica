<?php
header('Content-Type: text/html; charset=utf-8');

require_once 'php/db_connection.php';

echo "<h2>Creando Pedidos de Prueba para Control de Calidad</h2>";

try {
    // Verificar áreas de Control de Calidad
    echo "<h3>1. Verificando áreas:</h3>";
    $result = $conn->query("SELECT id, nombre_area FROM areas WHERE id IN (16, 17, 18)");
    $areas = [];
    while ($row = $result->fetch_assoc()) {
        $areas[] = $row;
        echo "Área ID {$row['id']}: {$row['nombre_area']}<br>";
    }
    
    if (empty($areas)) {
        echo "<strong>No se encontraron áreas 16, 17, 18. Creándolas...</strong><br>";
        
        // Crear áreas si no existen
        $areas_to_create = [
            [16, 'Control de Calidad - Recepción'],
            [17, 'Control de Calidad - Revisión'],
            [18, 'Control de Calidad - Aprobación']
        ];
        
        foreach ($areas_to_create as $area) {
            $stmt = $conn->prepare("INSERT IGNORE INTO areas (id, nombre_area) VALUES (?, ?)");
            $stmt->bind_param('is', $area[0], $area[1]);
            $stmt->execute();
            echo "Área creada: ID {$area[0]} - {$area[1]}<br>";
        }
    }
    
    // Crear estados si no existen
    echo "<h3>2. Verificando estados:</h3>";
    $result = $conn->query("SELECT id, nombre_estado FROM estados_pedido LIMIT 3");
    $estados = [];
    while ($row = $result->fetch_assoc()) {
        $estados[] = $row['id'];
        echo "Estado ID {$row['id']}: {$row['nombre_estado']}<br>";
    }
    
    if (empty($estados)) {
        echo "<strong>No se encontraron estados. Creando estados básicos...</strong><br>";
        $estados_to_create = [
            [1, 'Pendiente'],
            [2, 'En Proceso'],
            [3, 'Completado']
        ];
        
        foreach ($estados_to_create as $estado) {
            $stmt = $conn->prepare("INSERT IGNORE INTO estados_pedido (id, nombre_estado) VALUES (?, ?)");
            $stmt->bind_param('is', $estado[0], $estado[1]);
            $stmt->execute();
            echo "Estado creado: ID {$estado[0]} - {$estado[1]}<br>";
            $estados[] = $estado[0];
        }
    }
    
    // Crear pedidos de prueba
    echo "<h3>3. Creando pedidos de prueba:</h3>";
    
    $pedidos_test = [
        [
            'numero_guia' => 'CC001-' . date('Ymd'),
            'nombre_cliente' => 'Cliente Test 1',
            'correo_cliente' => 'test1@example.com',
            'area_id' => 17,
            'estado_id' => $estados[0] ?? 1,
            'prioridad' => 'alta',
            'notas' => 'Pedido de prueba para Control de Calidad - Alta prioridad'
        ],
        [
            'numero_guia' => 'CC002-' . date('Ymd'),
            'nombre_cliente' => 'Cliente Test 2',
            'correo_cliente' => 'test2@example.com',
            'area_id' => 17,
            'estado_id' => $estados[1] ?? 2,
            'prioridad' => 'media',
            'notas' => 'Pedido de prueba para Control de Calidad - Prioridad media'
        ],
        [
            'numero_guia' => 'CC003-' . date('Ymd'),
            'nombre_cliente' => 'Cliente Test 3',
            'correo_cliente' => 'test3@example.com',
            'area_id' => 18,
            'estado_id' => $estados[0] ?? 1,
            'prioridad' => 'baja',
            'notas' => 'Pedido de prueba para Control de Calidad - Prioridad baja'
        ]
    ];
    
    foreach ($pedidos_test as $pedido) {
        $stmt = $conn->prepare("
            INSERT INTO pedidos (numero_guia, nombre_cliente, correo_cliente, area_id, estado_id, prioridad, notas, fecha_creacion) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param('sssiiss', 
            $pedido['numero_guia'],
            $pedido['nombre_cliente'],
            $pedido['correo_cliente'],
            $pedido['area_id'],
            $pedido['estado_id'],
            $pedido['prioridad'],
            $pedido['notas']
        );
        
        if ($stmt->execute()) {
            $pedido_id = $conn->insert_id;
            echo "✓ Pedido creado: ID {$pedido_id} - {$pedido['numero_guia']} - {$pedido['nombre_cliente']}<br>";
            
            // Crear mensaje de devolución para el primer pedido
            if ($pedido['numero_guia'] === 'CC001-' . date('Ymd')) {
                $msg_stmt = $conn->prepare("
                    INSERT INTO mensajes (pedido_id, usuario_id, mensaje, tipo, fecha_creacion) 
                    VALUES (?, 1, 'Producto devuelto por defecto de calidad', 'devolucion', NOW())
                ");
                $msg_stmt->bind_param('i', $pedido_id);
                if ($msg_stmt->execute()) {
                    echo "  → Mensaje de devolución agregado<br>";
                }
            }
        } else {
            echo "✗ Error creando pedido {$pedido['numero_guia']}: " . $stmt->error . "<br>";
        }
    }
    
    // Verificar pedidos creados
    echo "<h3>4. Verificando pedidos en Control de Calidad:</h3>";
    $result = $conn->query("
        SELECT p.id, p.numero_guia, p.nombre_cliente, p.area_id, a.nombre_area, p.prioridad
        FROM pedidos p
        LEFT JOIN areas a ON p.area_id = a.id
        WHERE p.area_id IN (16, 17, 18)
        ORDER BY p.fecha_creacion DESC
        LIMIT 10
    ");
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Número Guía</th><th>Cliente</th><th>Área</th><th>Prioridad</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['numero_guia']}</td>";
        echo "<td>{$row['nombre_cliente']}</td>";
        echo "<td>{$row['nombre_area']}</td>";
        echo "<td>{$row['prioridad']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✓ Pedidos de prueba creados exitosamente</h3>";
    echo "<p><a href='control_calidad.php'>Ir a Control de Calidad</a></p>";
    
} catch (Exception $e) {
    echo "<h3>✗ Error: " . $e->getMessage() . "</h3>";
}

$conn->close();
?>