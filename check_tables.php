<?php
header('Content-Type: application/json');

require_once 'php/db_connection.php';

try {
    // Verificar qué tablas existen
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    
    echo "<h3>Tablas disponibles:</h3>";
    foreach ($tables as $table) {
        echo "- $table<br>";
    }
    
    // Verificar estructura de tabla estados_pedido si existe
    if (in_array('estados_pedido', $tables)) {
        echo "<h3>Estructura de estados_pedido:</h3>";
        $result = $conn->query("DESCRIBE estados_pedido");
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['Field']} ({$row['Type']})<br>";
        }
    }
    
    // Verificar estructura de tabla areas si existe
    if (in_array('areas', $tables)) {
        echo "<h3>Estructura de areas:</h3>";
        $result = $conn->query("DESCRIBE areas");
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['Field']} ({$row['Type']})<br>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>