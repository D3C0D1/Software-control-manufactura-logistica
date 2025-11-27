<?php
require_once 'php/db_connection.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<h2>Verificación de Áreas</h2>";

// Verificar todas las áreas
$sql_areas = "SELECT id, nombre_area FROM areas ORDER BY id";
$result_areas = $conn->query($sql_areas);

echo "<h3>Áreas existentes:</h3>";
echo "<ul>";
while ($area = $result_areas->fetch_assoc()) {
    echo "<li>ID: {$area['id']} - {$area['nombre_area']}</li>";
}
echo "</ul>";

// Verificar pedido 370 específicamente
$sql_pedido = "SELECT p.id, p.area_id, a.nombre_area FROM pedidos p LEFT JOIN areas a ON p.area_id = a.id WHERE p.id = 370";
$result_pedido = $conn->query($sql_pedido);

echo "<h3>Pedido 370:</h3>";
if ($pedido = $result_pedido->fetch_assoc()) {
    echo "<p>ID: {$pedido['id']}</p>";
    echo "<p>Area ID: {$pedido['area_id']}</p>";
    echo "<p>Nombre Área: " . ($pedido['nombre_area'] ?? 'NO ENCONTRADA') . "</p>";
} else {
    echo "<p>Pedido 370 no encontrado</p>";
}

// Verificar si el área 17 existe
$sql_area17 = "SELECT * FROM areas WHERE id = 17";
$result_area17 = $conn->query($sql_area17);

echo "<h3>Área 17:</h3>";
if ($area17 = $result_area17->fetch_assoc()) {
    echo "<p>✅ Área 17 existe: {$area17['nombre_area']}</p>";
} else {
    echo "<p>❌ Área 17 NO existe</p>";
    
    // Crear el área 17 si no existe
    $sql_create = "INSERT INTO areas (id, nombre_area) VALUES (17, 'Control de Calidad Final')";
    if ($conn->query($sql_create)) {
        echo "<p>✅ Área 17 creada exitosamente</p>";
    } else {
        echo "<p>❌ Error al crear área 17: " . $conn->error . "</p>";
    }
}

$conn->close();
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background: #f5f5f5;
}
h2, h3 {
    color: #333;
}
p, li {
    color: #666;
    line-height: 1.6;
}
ul {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
</style>